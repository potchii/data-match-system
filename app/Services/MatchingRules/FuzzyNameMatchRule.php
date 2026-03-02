<?php

namespace App\Services\MatchingRules;

use App\Config\FuzzyMatchingConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FuzzyNameMatchRule extends MatchRule
{
    protected float $threshold = 85.0;
    private FuzzyMatchingConfig $config;

    public function __construct(FuzzyMatchingConfig $config = null)
    {
        $this->config = $config ?? new FuzzyMatchingConfig();
    }

    public function name(): string
    {
        return 'fuzzy_name_match';
    }

    public function confidence(): float
    {
        return 70.0;
    }

    public function status(): string
    {
        return 'POSSIBLE DUPLICATE';
    }

    public function match(array $normalizedData, Collection $candidates, ?int $templateId = null): ?array
    {
        $startTime = microtime(true);
        $bestMatch = null;
        $bestConfidence = 0;
        $bestFieldBreakdown = [];

        foreach ($candidates as $candidate) {
            // Perform fuzzy name matching
            $lastNameSimilarity = $this->similarity(
                $normalizedData['last_name_normalized'],
                $candidate->last_name_normalized
            );

            $firstNameSimilarity = $this->similarity(
                $normalizedData['first_name_normalized'],
                $candidate->first_name_normalized
            );

            $avgScore = ($lastNameSimilarity + $firstNameSimilarity) / 2;

            // Skip if fuzzy name match doesn't meet threshold
            if ($avgScore < $this->threshold) {
                continue;
            }

            // Validate discriminators
            $dobResult = $this->validateDobMatch(
                $normalizedData['dob'] ?? null,
                $candidate->dob
            );

            // Reject if gender mismatch
            $genderResult = $this->validateGenderMatch(
                $normalizedData['gender'] ?? null,
                $candidate->gender
            );

            if (!$genderResult['valid']) {
                Log::debug('Candidate rejected due to gender mismatch', [
                    'candidate_id' => $candidate->id,
                ]);
                continue;
            }

            // Validate address/barangay
            $addressResult = $this->validateAddressMatch(
                $normalizedData['address'] ?? null,
                $normalizedData['barangay'] ?? null,
                $candidate->address,
                $candidate->barangay
            );

            // Validate template fields if template ID provided
            $templateResult = [];
            if ($templateId && !empty($normalizedData['template_fields'] ?? [])) {
                $templateResult = $this->validateTemplateFieldMatch(
                    $normalizedData['template_fields'],
                    $candidate,
                    $templateId
                );
            }

            // Calculate discriminator score adjustment
            $discriminatorAdjustment = $this->calculateDiscriminatorScore(
                $dobResult,
                $genderResult,
                $addressResult,
                $templateResult
            );

            // Calculate final confidence
            $baseScore = $this->confidence();
            $finalConfidence = $this->calculateFinalConfidence($baseScore, $discriminatorAdjustment);

            // Track best match
            if ($finalConfidence > $bestConfidence) {
                $bestConfidence = $finalConfidence;
                $bestMatch = $candidate;
                $bestFieldBreakdown = [
                    'name_similarity' => (int) $avgScore,
                    'dob_match' => [
                        'matched' => $dobResult['matched'] ?? false,
                        'bonus' => $dobResult['bonus'] ?? 0,
                        'penalty' => $dobResult['penalty'] ?? 0,
                    ],
                    'gender_match' => [
                        'matched' => $genderResult['matched'] ?? false,
                        'bonus' => $genderResult['bonus'] ?? 0,
                        'penalty' => $genderResult['penalty'] ?? 0,
                    ],
                    'address_match' => [
                        'matched' => $addressResult['matched'] ?? false,
                        'bonus' => $addressResult['bonus'] ?? 0,
                        'penalty' => $addressResult['penalty'] ?? 0,
                    ],
                    'template_fields' => [
                        'matched_count' => $templateResult['matched_count'] ?? 0,
                        'bonus' => $templateResult['bonus'] ?? 0,
                        'penalty' => $templateResult['penalty'] ?? 0,
                    ],
                    'discriminator_adjustment' => $discriminatorAdjustment,
                    'base_score' => (int) $baseScore,
                    'final_score' => $finalConfidence,
                ];
            }
        }

        $elapsedTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        if (!$bestMatch) {
            Log::debug('No fuzzy name match found', [
                'candidates_evaluated' => $candidates->count(),
                'elapsed_time_ms' => round($elapsedTime, 2),
            ]);
            return null;
        }

        Log::info('Fuzzy name match found', [
            'candidate_id' => $bestMatch->id,
            'confidence' => $bestConfidence,
            'candidates_evaluated' => $candidates->count(),
            'elapsed_time_ms' => round($elapsedTime, 2),
        ]);

        return [
            'record' => $bestMatch,
            'rule' => $this->name(),
            'confidence' => $bestConfidence,
            'field_breakdown' => $bestFieldBreakdown,
            'performance' => [
                'elapsed_time_ms' => round($elapsedTime, 2),
                'candidates_evaluated' => $candidates->count(),
            ],
        ];
    }

    /**
     * Validate DOB match between uploaded and candidate records
     *
     * @param string|null $uploadedDob DOB from uploaded record
     * @param string|null $candidateDob DOB from candidate record
     * @return array ['valid' => bool, 'bonus' => int, 'penalty' => int, 'matched' => bool]
     */
    public function validateDobMatch(?string $uploadedDob, ?string $candidateDob): array
    {
        $dobConfig = $this->config->getDiscriminatorConfig('dob');

        if (!$dobConfig['enabled']) {
            return ['valid' => true, 'bonus' => 0, 'penalty' => 0, 'matched' => false];
        }

        $normalizedUploaded = $this->normalizeDob($uploadedDob);
        $normalizedCandidate = $this->normalizeDob($candidateDob);

        // Both missing - no adjustment
        if ($normalizedUploaded === null && $normalizedCandidate === null) {
            Log::debug('DOB comparison: both missing', [
                'uploaded_dob' => $uploadedDob,
                'candidate_dob' => $candidateDob,
            ]);
            return ['valid' => true, 'bonus' => 0, 'penalty' => 0, 'matched' => false];
        }

        // One missing - apply penalty
        if ($normalizedUploaded === null || $normalizedCandidate === null) {
            $penalty = $dobConfig['penalty_missing_one'];
            Log::debug('DOB comparison: one missing', [
                'uploaded_dob' => $uploadedDob,
                'candidate_dob' => $candidateDob,
                'penalty' => $penalty,
            ]);
            return ['valid' => true, 'bonus' => 0, 'penalty' => $penalty, 'matched' => false];
        }

        // Both present - check for match
        if ($normalizedUploaded === $normalizedCandidate) {
            $bonus = $dobConfig['bonus_exact_match'];
            Log::debug('DOB comparison: exact match', [
                'dob' => $normalizedUploaded,
                'bonus' => $bonus,
            ]);
            return ['valid' => true, 'bonus' => $bonus, 'penalty' => 0, 'matched' => true];
        }

        // Check for partial match (same month/year, different day)
        if ($this->isPartialDobMatch($normalizedUploaded, $normalizedCandidate)) {
            $penalty = $dobConfig['penalty_partial_match'];
            Log::debug('DOB comparison: partial match', [
                'uploaded_dob' => $normalizedUploaded,
                'candidate_dob' => $normalizedCandidate,
                'penalty' => $penalty,
            ]);
            return ['valid' => true, 'bonus' => 0, 'penalty' => $penalty, 'matched' => false];
        }

        // No match
        Log::debug('DOB comparison: no match', [
            'uploaded_dob' => $normalizedUploaded,
            'candidate_dob' => $normalizedCandidate,
        ]);
        return ['valid' => true, 'bonus' => 0, 'penalty' => 0, 'matched' => false];
    }

    /**
     * Normalize DOB to YYYY-MM-DD format
     *
     * @param string|null $dob DOB in various formats
     * @return string|null Normalized DOB or null if invalid
     */
    private function normalizeDob(?string $dob): ?string
    {
        if ($dob === null || trim($dob) === '') {
            return null;
        }

        $dob = trim($dob);

        try {
            // Try parsing with common formats
            $formats = [
                'Y-m-d',
                'm/d/Y',
                'd-m-Y',
                'Y/m/d',
                'd/m/Y',
                'Y-m-d H:i:s',
                'm/d/Y H:i:s',
            ];

            $parsed = null;
            foreach ($formats as $format) {
                $parsed = Carbon::createFromFormat($format, $dob);
                if ($parsed) {
                    break;
                }
            }

            if (!$parsed) {
                Log::warning('Failed to parse DOB', ['dob' => $dob]);
                return null;
            }

            // Reject future dates
            if ($parsed->isFuture()) {
                Log::warning('DOB is in future', ['dob' => $dob, 'parsed' => $parsed->toDateString()]);
                return null;
            }

            return $parsed->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning('DOB normalization error', ['dob' => $dob, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Check if two DOBs match partially (same month/year, different day)
     *
     * @param string $dob1 Normalized DOB (YYYY-MM-DD)
     * @param string $dob2 Normalized DOB (YYYY-MM-DD)
     * @return bool True if same month/year but different day
     */
    private function isPartialDobMatch(string $dob1, string $dob2): bool
    {
        $parts1 = explode('-', $dob1);
        $parts2 = explode('-', $dob2);

        if (count($parts1) !== 3 || count($parts2) !== 3) {
            return false;
        }

        // Same year and month, different day
        return $parts1[0] === $parts2[0] && $parts1[1] === $parts2[1] && $parts1[2] !== $parts2[2];
    }

    /**
     * Validate gender match between uploaded and candidate records
     *
     * @param string|null $uploadedGender Gender from uploaded record
     * @param string|null $candidateGender Gender from candidate record
     * @return array ['valid' => bool, 'bonus' => int, 'penalty' => int, 'matched' => bool]
     */
    public function validateGenderMatch(?string $uploadedGender, ?string $candidateGender): array
    {
        $genderConfig = $this->config->getDiscriminatorConfig('gender');

        if (!$genderConfig['enabled']) {
            return ['valid' => true, 'bonus' => 0, 'penalty' => 0, 'matched' => false];
        }

        $normalizedUploaded = $this->normalizeGender($uploadedGender);
        $normalizedCandidate = $this->normalizeGender($candidateGender);

        // Both missing - no adjustment
        if ($normalizedUploaded === null && $normalizedCandidate === null) {
            Log::debug('Gender comparison: both missing', [
                'uploaded_gender' => $uploadedGender,
                'candidate_gender' => $candidateGender,
            ]);
            return ['valid' => true, 'bonus' => 0, 'penalty' => 0, 'matched' => false];
        }

        // One missing - apply penalty
        if ($normalizedUploaded === null || $normalizedCandidate === null) {
            $penalty = $genderConfig['penalty_missing_one'];
            Log::debug('Gender comparison: one missing', [
                'uploaded_gender' => $uploadedGender,
                'candidate_gender' => $candidateGender,
                'penalty' => $penalty,
            ]);
            return ['valid' => true, 'bonus' => 0, 'penalty' => $penalty, 'matched' => false];
        }

        // Both present - check for match
        if ($normalizedUploaded === $normalizedCandidate) {
            $bonus = $genderConfig['bonus_match'];
            Log::debug('Gender comparison: match', [
                'gender' => $normalizedUploaded,
                'bonus' => $bonus,
            ]);
            return ['valid' => true, 'bonus' => $bonus, 'penalty' => 0, 'matched' => true];
        }

        // Mismatch - reject if configured
        if ($genderConfig['reject_on_mismatch']) {
            Log::debug('Gender comparison: mismatch - rejecting', [
                'uploaded_gender' => $normalizedUploaded,
                'candidate_gender' => $normalizedCandidate,
            ]);
            return ['valid' => false, 'bonus' => 0, 'penalty' => 0, 'matched' => false];
        }

        // Mismatch but not rejecting - no adjustment
        Log::debug('Gender comparison: mismatch - not rejecting', [
            'uploaded_gender' => $normalizedUploaded,
            'candidate_gender' => $normalizedCandidate,
        ]);
        return ['valid' => true, 'bonus' => 0, 'penalty' => 0, 'matched' => false];
    }

    /**
     * Normalize gender to standard format (M, F, Other)
     *
     * @param string|null $gender Gender value in various formats
     * @return string|null Normalized gender (M, F, Other) or null if invalid
     */
    private function normalizeGender(?string $gender): ?string
    {
        if ($gender === null || trim($gender) === '') {
            return null;
        }

        $gender = strtoupper(trim($gender));

        // Map common variations to standard format
        $mapping = [
            'M' => 'M',
            'MALE' => 'M',
            'F' => 'F',
            'FEMALE' => 'F',
            'OTHER' => 'Other',
            'O' => 'Other',
        ];

        if (isset($mapping[$gender])) {
            return $mapping[$gender];
        }

        Log::warning('Unknown gender value', ['gender' => $gender]);
        return null;
    }

    /**
     * Validate address/barangay match between uploaded and candidate records
     *
     * @param string|null $uploadedAddress Address from uploaded record
     * @param string|null $uploadedBarangay Barangay from uploaded record
     * @param string|null $candidateAddress Address from candidate record
     * @param string|null $candidateBarangay Barangay from candidate record
     * @return array ['valid' => bool, 'bonus' => int, 'penalty' => int, 'matched' => bool]
     */
    public function validateAddressMatch(
        ?string $uploadedAddress,
        ?string $uploadedBarangay,
        ?string $candidateAddress,
        ?string $candidateBarangay
    ): array {
        $addressConfig = $this->config->getDiscriminatorConfig('address');

        if (!$addressConfig['enabled']) {
            return ['valid' => true, 'bonus' => 0, 'penalty' => 0, 'matched' => false];
        }

        $normalizedUploadedAddr = $this->normalizeAddress($uploadedAddress);
        $normalizedCandidateAddr = $this->normalizeAddress($candidateAddress);
        $normalizedUploadedBarangay = $this->normalizeAddress($uploadedBarangay);
        $normalizedCandidateBarangay = $this->normalizeAddress($candidateBarangay);

        $totalBonus = 0;
        $totalPenalty = 0;

        // Validate barangay match
        if ($normalizedUploadedBarangay === null && $normalizedCandidateBarangay === null) {
            // Both missing - no adjustment
            Log::debug('Barangay comparison: both missing');
        } elseif ($normalizedUploadedBarangay === null || $normalizedCandidateBarangay === null) {
            // One missing - apply penalty
            $totalPenalty += $addressConfig['penalty_missing_one'];
            Log::debug('Barangay comparison: one missing', ['penalty' => $addressConfig['penalty_missing_one']]);
        } elseif ($normalizedUploadedBarangay === $normalizedCandidateBarangay) {
            // Exact match - apply bonus
            $totalBonus += $addressConfig['bonus_exact_barangay'];
            Log::debug('Barangay comparison: exact match', ['bonus' => $addressConfig['bonus_exact_barangay']]);
        } else {
            // Mismatch - apply penalty
            $totalPenalty += $addressConfig['penalty_missing_one'];
            Log::debug('Barangay comparison: mismatch', ['penalty' => $addressConfig['penalty_missing_one']]);
        }

        // Validate address fuzzy match
        if ($normalizedUploadedAddr === null && $normalizedCandidateAddr === null) {
            // Both missing - no adjustment
            Log::debug('Address comparison: both missing');
        } elseif ($normalizedUploadedAddr === null || $normalizedCandidateAddr === null) {
            // One missing - apply penalty
            $totalPenalty += $addressConfig['penalty_missing_one'];
            Log::debug('Address comparison: one missing', ['penalty' => $addressConfig['penalty_missing_one']]);
        } elseif ($this->fuzzyMatchAddresses($normalizedUploadedAddr, $normalizedCandidateAddr)) {
            // Fuzzy match - apply bonus
            $totalBonus += $addressConfig['bonus_fuzzy_address'];
            Log::debug('Address comparison: fuzzy match', ['bonus' => $addressConfig['bonus_fuzzy_address']]);
        } else {
            // No match - apply penalty
            $totalPenalty += $addressConfig['penalty_fuzzy_fail'];
            Log::debug('Address comparison: no match', ['penalty' => $addressConfig['penalty_fuzzy_fail']]);
        }

        return [
            'valid' => true,
            'bonus' => $totalBonus,
            'penalty' => $totalPenalty,
            'matched' => $totalBonus > 0,
        ];
    }

    /**
     * Normalize address to lowercase, trimmed format
     *
     * @param string|null $address Address value
     * @return string|null Normalized address or null if empty
     */
    private function normalizeAddress(?string $address): ?string
    {
        if ($address === null || trim($address) === '') {
            return null;
        }

        // Lowercase, trim, remove extra spaces
        $normalized = strtolower(trim($address));
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return $normalized;
    }

    /**
     * Perform fuzzy matching on addresses with 80% threshold
     *
     * @param string $address1 Normalized address
     * @param string $address2 Normalized address
     * @return bool True if addresses match at 80% threshold
     */
    private function fuzzyMatchAddresses(string $address1, string $address2): bool
    {
        $threshold = $this->config->getAddressSimilarityThreshold();
        $similarity = $this->similarity($address1, $address2);

        return $similarity >= $threshold;
    }

    /**
     * Validate template field match between uploaded and candidate records
     *
     * @param array $uploadedFields Template fields from uploaded record
     * @param \App\Models\MainSystem $candidate Candidate record
     * @param int $templateId Template ID for field lookup
     * @return array ['bonus' => int, 'penalty' => int, 'matched_count' => int]
     */
    public function validateTemplateFieldMatch(
        array $uploadedFields,
        \App\Models\MainSystem $candidate,
        int $templateId
    ): array {
        $templateConfig = $this->config->getDiscriminatorConfig('template_fields');

        if (!$templateConfig['enabled'] || empty($uploadedFields)) {
            return ['bonus' => 0, 'penalty' => 0, 'matched_count' => 0];
        }

        try {
            $totalBonus = 0;
            $totalPenalty = 0;
            $matchedCount = 0;

            foreach ($uploadedFields as $fieldName => $uploadedValue) {
                if ($uploadedValue === null || trim((string) $uploadedValue) === '') {
                    continue;
                }

                try {
                    $candidateValue = $this->getCandidateTemplateFieldValue($candidate->id, $fieldName, $templateId);

                    if ($candidateValue === null) {
                        continue;
                    }

                    $normalizedUploaded = $this->normalizeTemplateFieldValue($uploadedValue);
                    $normalizedCandidate = $this->normalizeTemplateFieldValue($candidateValue);

                    if ($normalizedUploaded === $normalizedCandidate) {
                        // Exact match
                        $bonus = $templateConfig['bonus_exact_match'];
                        $totalBonus += $bonus;
                        $matchedCount++;
                        Log::debug('Template field exact match', [
                            'field' => $fieldName,
                            'bonus' => $bonus,
                        ]);
                    } elseif ($this->fuzzyMatchTemplateFields($normalizedUploaded, $normalizedCandidate)) {
                        // Fuzzy match
                        $bonus = $templateConfig['bonus_fuzzy_match'];
                        $totalBonus += $bonus;
                        $matchedCount++;
                        Log::debug('Template field fuzzy match', [
                            'field' => $fieldName,
                            'bonus' => $bonus,
                        ]);
                    } else {
                        // No match
                        $penalty = $templateConfig['penalty_no_match'];
                        $totalPenalty += $penalty;
                        Log::debug('Template field no match', [
                            'field' => $fieldName,
                            'penalty' => $penalty,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('Template field comparison error', [
                        'field' => $fieldName,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            // Apply caps
            $totalBonus = min($totalBonus, $templateConfig['max_bonus']);
            $totalPenalty = min($totalPenalty, $templateConfig['max_penalty']);

            return [
                'bonus' => $totalBonus,
                'penalty' => $totalPenalty,
                'matched_count' => $matchedCount,
            ];
        } catch (\Exception $e) {
            Log::warning('Template field validation error', [
                'candidate_id' => $candidate->id,
                'template_id' => $templateId,
                'error' => $e->getMessage(),
            ]);
            return ['bonus' => 0, 'penalty' => 0, 'matched_count' => 0];
        }
    }

    /**
     * Get template field value for candidate record
     *
     * @param int $mainSystemId Candidate record ID
     * @param string $fieldName Field name
     * @param int $templateId Template ID
     * @return string|null Field value or null if not found
     */
    private function getCandidateTemplateFieldValue(int $mainSystemId, string $fieldName, int $templateId): ?string
    {
        try {
            $value = \App\Models\TemplateFieldValue::where('main_system_id', $mainSystemId)
                ->where('template_id', $templateId)
                ->whereHas('templateField', function ($query) use ($fieldName) {
                    $query->where('name', $fieldName);
                })
                ->value('value');

            return $value ? (string) $value : null;
        } catch (\Exception $e) {
            Log::warning('Failed to retrieve template field value', [
                'main_system_id' => $mainSystemId,
                'field_name' => $fieldName,
                'template_id' => $templateId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Batch load template fields for multiple candidates
     * Optimized to use single query with IN clause instead of N queries
     *
     * @param array $candidateIds Array of candidate record IDs
     * @param int $templateId Template ID
     * @return array Grouped template fields by main_system_id
     */
    private function batchLoadTemplateFields(array $candidateIds, int $templateId): array
    {
        try {
            if (empty($candidateIds)) {
                return [];
            }

            $templateFields = \App\Models\TemplateFieldValue::whereIn('main_system_id', $candidateIds)
                ->where('template_id', $templateId)
                ->with('templateField')
                ->get()
                ->groupBy('main_system_id')
                ->map(function ($fields) {
                    return $fields->pluck('value', 'templateField.name')->toArray();
                })
                ->toArray();

            Log::debug('Batch loaded template fields', [
                'candidate_count' => count($candidateIds),
                'template_id' => $templateId,
                'fields_loaded' => count($templateFields),
            ]);

            return $templateFields;
        } catch (\Exception $e) {
            Log::warning('Failed to batch load template fields', [
                'candidate_count' => count($candidateIds),
                'template_id' => $templateId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Normalize template field value for comparison
     *
     * @param mixed $value Field value
     * @return string Normalized value
     */
    private function normalizeTemplateFieldValue($value): string
    {
        $normalized = strtolower(trim((string) $value));
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        return $normalized;
    }

    /**
     * Perform fuzzy matching on template field values with 80% threshold
     *
     * @param string $value1 Normalized field value
     * @param string $value2 Normalized field value
     * @return bool True if values match at 80% threshold
     */
    private function fuzzyMatchTemplateFields(string $value1, string $value2): bool
    {
        $threshold = $this->config->getAddressSimilarityThreshold();
        $similarity = $this->similarity($value1, $value2);

        return $similarity >= $threshold;
    }

    /**
     * Calculate total discriminator score adjustment
     *
     * @param array $dobResult DOB validation result
     * @param array $genderResult Gender validation result
     * @param array $addressResult Address validation result
     * @param array $templateResult Template field validation result
     * @return int Total bonus/penalty adjustment
     */
    public function calculateDiscriminatorScore(
        array $dobResult,
        array $genderResult,
        array $addressResult,
        array $templateResult
    ): int {
        $totalBonus = 0;
        $totalPenalty = 0;

        // Add DOB adjustments
        $totalBonus += $dobResult['bonus'] ?? 0;
        $totalPenalty += $dobResult['penalty'] ?? 0;

        // Add gender adjustments
        $totalBonus += $genderResult['bonus'] ?? 0;
        $totalPenalty += $genderResult['penalty'] ?? 0;

        // Add address adjustments
        $totalBonus += $addressResult['bonus'] ?? 0;
        $totalPenalty += $addressResult['penalty'] ?? 0;

        // Add template field adjustments
        $totalBonus += $templateResult['bonus'] ?? 0;
        $totalPenalty += $templateResult['penalty'] ?? 0;

        $adjustment = $totalBonus - $totalPenalty;

        Log::debug('Discriminator score calculated', [
            'total_bonus' => $totalBonus,
            'total_penalty' => $totalPenalty,
            'adjustment' => $adjustment,
        ]);

        return $adjustment;
    }

    /**
     * Calculate final confidence score with bounds and rounding
     *
     * @param float $baseScore Base confidence score
     * @param int $discriminatorAdjustment Discriminator adjustment (bonus - penalty)
     * @return int Final confidence score (0-100)
     */
    public function calculateFinalConfidence(float $baseScore, int $discriminatorAdjustment): int
    {
        $finalScore = $baseScore + $discriminatorAdjustment;

        // Apply bounds
        $minConfidence = $this->config->getMinConfidence();
        $maxConfidence = $this->config->getMaxConfidence();

        $finalScore = max($minConfidence, min($maxConfidence, $finalScore));

        // Round to nearest integer
        $finalScore = (int) round($finalScore);

        Log::debug('Final confidence calculated', [
            'base_score' => $baseScore,
            'discriminator_adjustment' => $discriminatorAdjustment,
            'final_score' => $finalScore,
        ]);

        return $finalScore;
    }
}

