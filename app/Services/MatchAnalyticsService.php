<?php

namespace App\Services;

use App\Models\MatchResult;
use App\Models\UploadBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MatchAnalyticsService
{
    /**
     * Calculate batch-level statistics
     * 
     * @param int $batchId
     * @return array Statistics including match rates, field population, quality score
     */
    public function calculateBatchStatistics(int $batchId): array
    {
        // Get total records count
        $totalRecords = MatchResult::where('batch_id', $batchId)->count();

        if ($totalRecords === 0) {
            return [
                'total_records' => 0,
                'matched' => 0,
                'possible_duplicates' => 0,
                'new_records' => 0,
                'average_confidence' => 0,
                'average_matched_fields' => 0,
                'average_mismatched_fields' => 0,
            ];
        }

        // Get match status distribution using aggregation
        $statusDistribution = MatchResult::where('batch_id', $batchId)
            ->select('match_status', DB::raw('count(*) as count'))
            ->groupBy('match_status')
            ->pluck('count', 'match_status')
            ->toArray();

        $matched = $statusDistribution['MATCHED'] ?? 0;
        $possibleDuplicates = $statusDistribution['POSSIBLE DUPLICATE'] ?? 0;
        $newRecords = $statusDistribution['NEW RECORD'] ?? 0;

        // Calculate average confidence excluding NEW RECORD entries
        $confidenceData = MatchResult::where('batch_id', $batchId)
            ->where('match_status', '!=', 'NEW RECORD')
            ->whereNotNull('confidence_score')
            ->selectRaw('AVG(confidence_score) as avg_confidence, COUNT(*) as count')
            ->first();

        $averageConfidence = $confidenceData && $confidenceData->count > 0 
            ? round($confidenceData->avg_confidence, 2) 
            : 0;

        // Calculate average matched and mismatched fields
        $fieldStats = MatchResult::where('batch_id', $batchId)
            ->whereNotNull('field_breakdown')
            ->get(['field_breakdown'])
            ->map(function ($result) {
                $breakdown = $result->field_breakdown;
                return [
                    'matched' => $breakdown['matched_fields'] ?? 0,
                    'total' => $breakdown['total_fields'] ?? 0,
                ];
            });

        $averageMatchedFields = $fieldStats->isNotEmpty() 
            ? round($fieldStats->avg('matched'), 2) 
            : 0;

        $averageMismatchedFields = $fieldStats->isNotEmpty() 
            ? round($fieldStats->map(fn($s) => $s['total'] - $s['matched'])->avg(), 2) 
            : 0;

        return [
            'total_records' => $totalRecords,
            'matched' => $matched,
            'possible_duplicates' => $possibleDuplicates,
            'new_records' => $newRecords,
            'average_confidence' => $averageConfidence,
            'average_matched_fields' => $averageMatchedFields,
            'average_mismatched_fields' => $averageMismatchedFields,
        ];
    }

    /**
     * Generate field population rates for core fields
     * 
     * @param int $batchId
     * @param int|null $templateId Optional template ID for template fields
     * @return array Field names with population counts and percentages
     */
    public function calculateFieldPopulationRates(int $batchId, ?int $templateId = null): array
    {
        $totalRecords = MatchResult::where('batch_id', $batchId)->count();

        if ($totalRecords === 0) {
            return [
                'core_fields' => [],
                'template_fields' => [],
            ];
        }

        // Core fields to check (from MainSystem model)
        $coreFields = [
            'uid', 'last_name', 'first_name', 'middle_name', 'suffix',
            'birthday', 'gender', 'civil_status', 'address', 'barangay'
        ];

        $coreFieldPopulation = [];

        // Use chunking for large batches
        if ($totalRecords > 10000) {
            $coreFieldPopulation = $this->calculateCoreFieldPopulationChunked($batchId, $coreFields, $totalRecords);
        } else {
            $coreFieldPopulation = $this->calculateCoreFieldPopulationStandard($batchId, $coreFields, $totalRecords);
        }

        // Calculate template field population if template provided
        $templateFieldPopulation = [];
        if ($templateId) {
            $templateFieldPopulation = $this->calculateTemplateFieldPopulation($batchId, $templateId, $totalRecords);
        }

        return [
            'core_fields' => $coreFieldPopulation,
            'template_fields' => $templateFieldPopulation,
        ];
    }

    /**
     * Calculate core field population using standard method (for batches <= 10,000)
     */
    protected function calculateCoreFieldPopulationStandard(int $batchId, array $coreFields, int $totalRecords): array
    {
        $population = [];

        foreach ($coreFields as $field) {
            // Count non-empty values from field_breakdown
            $count = MatchResult::where('batch_id', $batchId)
                ->whereNotNull('field_breakdown')
                ->get(['field_breakdown'])
                ->filter(function ($result) use ($field) {
                    $breakdown = $result->field_breakdown;
                    $coreFields = $breakdown['core_fields'] ?? [];
                    
                    if (!isset($coreFields[$field])) {
                        return false;
                    }

                    $uploaded = $coreFields[$field]['uploaded'] ?? null;
                    return $uploaded !== null && $uploaded !== '';
                })
                ->count();

            $percentage = $totalRecords > 0 ? round(($count / $totalRecords) * 100, 1) : 0;

            $population[$field] = [
                'count' => $count,
                'percentage' => $percentage,
            ];
        }

        return $population;
    }

    /**
     * Calculate core field population using chunking (for batches > 10,000)
     */
    protected function calculateCoreFieldPopulationChunked(int $batchId, array $coreFields, int $totalRecords): array
    {
        $population = [];

        foreach ($coreFields as $field) {
            $count = 0;

            MatchResult::where('batch_id', $batchId)
                ->whereNotNull('field_breakdown')
                ->chunk(1000, function ($results) use ($field, &$count) {
                    foreach ($results as $result) {
                        $breakdown = $result->field_breakdown;
                        $coreFields = $breakdown['core_fields'] ?? [];
                        
                        if (!isset($coreFields[$field])) {
                            continue;
                        }

                        $uploaded = $coreFields[$field]['uploaded'] ?? null;
                        if ($uploaded !== null && $uploaded !== '') {
                            $count++;
                        }
                    }
                });

            $percentage = $totalRecords > 0 ? round(($count / $totalRecords) * 100, 1) : 0;

            $population[$field] = [
                'count' => $count,
                'percentage' => $percentage,
            ];
        }

        return $population;
    }

    /**
     * Calculate template field population
     */
    protected function calculateTemplateFieldPopulation(int $batchId, int $templateId, int $totalRecords): array
    {
        $template = \App\Models\ColumnMappingTemplate::with('fields')->find($templateId);

        if (!$template) {
            Log::warning('Template not found for field population calculation', ['template_id' => $templateId]);
            return [];
        }

        $population = [];

        foreach ($template->fields as $templateField) {
            $fieldName = $templateField->field_name;

            // Count non-empty values from field_breakdown
            $count = MatchResult::where('batch_id', $batchId)
                ->whereNotNull('field_breakdown')
                ->get(['field_breakdown'])
                ->filter(function ($result) use ($fieldName) {
                    $breakdown = $result->field_breakdown;
                    $templateFields = $breakdown['template_fields'] ?? [];
                    
                    if (!isset($templateFields[$fieldName])) {
                        return false;
                    }

                    $uploaded = $templateFields[$fieldName]['uploaded'] ?? null;
                    return $uploaded !== null && $uploaded !== '';
                })
                ->count();

            $percentage = $totalRecords > 0 ? round(($count / $totalRecords) * 100, 1) : 0;

            $population[$fieldName] = [
                'count' => $count,
                'percentage' => $percentage,
            ];
        }

        return $population;
    }

    /**
     * Generate chart data for column mapping visualization
     * 
     * @param int $batchId
     * @param array $columnMapping Session data
     * @return array Chart datasets for pie and bar charts
     */
    public function generateChartData(int $batchId, array $columnMapping): array
    {
        // Generate pie chart data for mapped vs skipped columns
        $mappedCount = count($columnMapping['mapped'] ?? []);
        $skippedCount = count($columnMapping['skipped'] ?? []);

        $mappingPieChart = [
            'labels' => ['Mapped', 'Skipped'],
            'data' => [$mappedCount, $skippedCount],
            'colors' => ['#28a745', '#6c757d'], // Green for mapped, gray for skipped
        ];

        // Generate bar chart data for field population rates
        $fieldPopulation = $this->calculateFieldPopulationRates($batchId);
        $coreFields = $fieldPopulation['core_fields'];

        $populationLabels = [];
        $populationData = [];
        $populationColors = [];

        foreach ($coreFields as $fieldName => $stats) {
            $populationLabels[] = $fieldName;
            $populationData[] = $stats['percentage'];
            
            // Color coding: green >80%, yellow 50-80%, red <50%
            if ($stats['percentage'] > 80) {
                $populationColors[] = '#28a745'; // Green
            } elseif ($stats['percentage'] >= 50) {
                $populationColors[] = '#ffc107'; // Yellow
            } else {
                $populationColors[] = '#dc3545'; // Red
            }
        }

        $populationBarChart = [
            'labels' => $populationLabels,
            'data' => $populationData,
            'colors' => $populationColors,
        ];

        return [
            'mapping_pie' => $mappingPieChart,
            'population_bar' => $populationBarChart,
        ];
    }

    /**
     * Calculate quality score for batch
     * 
     * @param array $statistics Batch statistics
     * @return array Quality level (excellent/good/fair/poor) and score
     */
    public function calculateQualityScore(array $statistics): array
    {
        $averageConfidence = $statistics['average_confidence'] ?? 0;

        // Map confidence scores to quality levels
        if ($averageConfidence >= 90) {
            $level = 'excellent';
            $color = 'success';
        } elseif ($averageConfidence >= 75) {
            $level = 'good';
            $color = 'success';
        } elseif ($averageConfidence >= 60) {
            $level = 'fair';
            $color = 'warning';
        } else {
            $level = 'poor';
            $color = 'danger';
        }

        return [
            'level' => $level,
            'score' => $averageConfidence,
            'color' => $color,
        ];
    }

    /**
     * Identify top and bottom fields by match rate and population rate
     * 
     * @param int $batchId
     * @return array Top 5 and bottom 5 fields
     */
    public function identifyTopBottomFields(int $batchId): array
    {
        $fieldPopulation = $this->calculateFieldPopulationRates($batchId);
        $coreFields = $fieldPopulation['core_fields'];

        // Sort fields by population rate
        uasort($coreFields, function ($a, $b) {
            return $b['percentage'] <=> $a['percentage'];
        });

        $sortedFields = array_keys($coreFields);
        $fieldCount = count($sortedFields);

        // Get top 5 and bottom 5
        $top5 = array_slice($sortedFields, 0, min(5, $fieldCount));
        $bottom5 = array_slice($sortedFields, max(0, $fieldCount - 5), 5);

        // Reverse bottom 5 to show lowest first
        $bottom5 = array_reverse($bottom5);

        return [
            'top_fields' => array_map(function ($field) use ($coreFields) {
                return [
                    'field' => $field,
                    'percentage' => $coreFields[$field]['percentage'],
                    'count' => $coreFields[$field]['count'],
                ];
            }, $top5),
            'bottom_fields' => array_map(function ($field) use ($coreFields) {
                return [
                    'field' => $field,
                    'percentage' => $coreFields[$field]['percentage'],
                    'count' => $coreFields[$field]['count'],
                ];
            }, $bottom5),
        ];
    }
}
