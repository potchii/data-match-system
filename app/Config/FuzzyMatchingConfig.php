<?php

namespace App\Config;

use InvalidArgumentException;
use Illuminate\Support\Facades\Log;

class FuzzyMatchingConfig
{
    private array $config;

    public function __construct()
    {
        $this->config = $this->loadConfiguration();
        $this->validateConfiguration();
        $this->logConfiguration();
    }

    private function loadConfiguration(): array
    {
        return [
            'enabled' => $this->getBoolEnv('FUZZY_MATCHING_ENABLED', true),
            'name_similarity_threshold' => $this->getIntEnv('FUZZY_MATCHING_NAME_THRESHOLD', 85),
            'address_similarity_threshold' => $this->getIntEnv('FUZZY_MATCHING_ADDRESS_THRESHOLD', 80),
            'discriminators' => [
                'dob' => [
                    'enabled' => $this->getBoolEnv('FUZZY_MATCHING_DOB_ENABLED', true),
                    'bonus_exact_match' => $this->getIntEnv('FUZZY_MATCHING_DOB_BONUS_EXACT', 10),
                    'penalty_missing_one' => $this->getIntEnv('FUZZY_MATCHING_DOB_PENALTY_MISSING', 5),
                    'penalty_partial_match' => $this->getIntEnv('FUZZY_MATCHING_DOB_PENALTY_PARTIAL', 3),
                ],
                'gender' => [
                    'enabled' => $this->getBoolEnv('FUZZY_MATCHING_GENDER_ENABLED', true),
                    'bonus_match' => $this->getIntEnv('FUZZY_MATCHING_GENDER_BONUS', 5),
                    'penalty_missing_one' => $this->getIntEnv('FUZZY_MATCHING_GENDER_PENALTY_MISSING', 3),
                    'reject_on_mismatch' => $this->getBoolEnv('FUZZY_MATCHING_GENDER_REJECT_MISMATCH', true),
                ],
                'address' => [
                    'enabled' => $this->getBoolEnv('FUZZY_MATCHING_ADDRESS_ENABLED', true),
                    'bonus_exact_barangay' => $this->getIntEnv('FUZZY_MATCHING_ADDRESS_BONUS_BARANGAY', 5),
                    'bonus_fuzzy_address' => $this->getIntEnv('FUZZY_MATCHING_ADDRESS_BONUS_FUZZY', 5),
                    'penalty_missing_one' => $this->getIntEnv('FUZZY_MATCHING_ADDRESS_PENALTY_MISSING', 5),
                    'penalty_fuzzy_fail' => $this->getIntEnv('FUZZY_MATCHING_ADDRESS_PENALTY_FUZZY_FAIL', 5),
                ],
                'template_fields' => [
                    'enabled' => $this->getBoolEnv('FUZZY_MATCHING_TEMPLATE_ENABLED', true),
                    'bonus_exact_match' => $this->getIntEnv('FUZZY_MATCHING_TEMPLATE_BONUS_EXACT', 2),
                    'bonus_fuzzy_match' => $this->getIntEnv('FUZZY_MATCHING_TEMPLATE_BONUS_FUZZY', 1),
                    'penalty_no_match' => $this->getIntEnv('FUZZY_MATCHING_TEMPLATE_PENALTY_NO_MATCH', 1),
                    'max_bonus' => $this->getIntEnv('FUZZY_MATCHING_TEMPLATE_MAX_BONUS', 10),
                    'max_penalty' => $this->getIntEnv('FUZZY_MATCHING_TEMPLATE_MAX_PENALTY', 5),
                ],
            ],
            'base_confidence' => $this->getIntEnv('FUZZY_MATCHING_BASE_CONFIDENCE', 70),
            'max_confidence' => $this->getIntEnv('FUZZY_MATCHING_MAX_CONFIDENCE', 100),
            'min_confidence' => $this->getIntEnv('FUZZY_MATCHING_MIN_CONFIDENCE', 0),
        ];
    }

    private function getBoolEnv(string $key, bool $default): bool
    {
        $value = env($key, $default);
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            return strtolower($value) === 'true' || $value === '1';
        }
        return (bool) $value;
    }

    private function getIntEnv(string $key, int $default): int
    {
        $value = env($key, $default);
        return (int) $value;
    }

    private function validateConfiguration(): void
    {
        // Validate thresholds
        $this->validateRange('name_similarity_threshold', $this->config['name_similarity_threshold']);
        $this->validateRange('address_similarity_threshold', $this->config['address_similarity_threshold']);

        // Validate base confidence values
        $this->validateRange('base_confidence', $this->config['base_confidence']);
        $this->validateRange('max_confidence', $this->config['max_confidence']);
        $this->validateRange('min_confidence', $this->config['min_confidence']);

        // Validate discriminator configurations
        foreach ($this->config['discriminators'] as $discriminator => $settings) {
            foreach ($settings as $key => $value) {
                if ($key === 'enabled' || $key === 'reject_on_mismatch') {
                    if (!is_bool($value)) {
                        throw new InvalidArgumentException(
                            "Configuration error: discriminators.{$discriminator}.{$key} must be boolean"
                        );
                    }
                } elseif (is_int($value)) {
                    $this->validateRange("discriminators.{$discriminator}.{$key}", $value);
                }
            }
        }

        // Validate confidence bounds
        if ($this->config['min_confidence'] > $this->config['base_confidence']) {
            throw new InvalidArgumentException(
                'Configuration error: min_confidence cannot be greater than base_confidence'
            );
        }
        if ($this->config['base_confidence'] > $this->config['max_confidence']) {
            throw new InvalidArgumentException(
                'Configuration error: base_confidence cannot be greater than max_confidence'
            );
        }
    }

    private function validateRange(string $key, int $value): void
    {
        if ($value < 0 || $value > 100) {
            throw new InvalidArgumentException(
                "Configuration error: {$key} must be between 0 and 100, got {$value}"
            );
        }
    }

    private function logConfiguration(): void
    {
        Log::info('Fuzzy Matching Configuration Loaded', [
            'enabled' => $this->config['enabled'],
            'name_similarity_threshold' => $this->config['name_similarity_threshold'],
            'address_similarity_threshold' => $this->config['address_similarity_threshold'],
            'discriminators' => [
                'dob_enabled' => $this->config['discriminators']['dob']['enabled'],
                'gender_enabled' => $this->config['discriminators']['gender']['enabled'],
                'address_enabled' => $this->config['discriminators']['address']['enabled'],
                'template_fields_enabled' => $this->config['discriminators']['template_fields']['enabled'],
            ],
        ]);
    }

    public function isEnabled(): bool
    {
        return $this->config['enabled'];
    }

    public function getNameSimilarityThreshold(): int
    {
        return $this->config['name_similarity_threshold'];
    }

    public function getAddressSimilarityThreshold(): int
    {
        return $this->config['address_similarity_threshold'];
    }

    public function getDiscriminatorConfig(string $discriminator): array
    {
        if (!isset($this->config['discriminators'][$discriminator])) {
            throw new InvalidArgumentException("Unknown discriminator: {$discriminator}");
        }
        return $this->config['discriminators'][$discriminator];
    }

    public function isDiscriminatorEnabled(string $discriminator): bool
    {
        return $this->getDiscriminatorConfig($discriminator)['enabled'] ?? false;
    }

    public function getBaseConfidence(): int
    {
        return $this->config['base_confidence'];
    }

    public function getMaxConfidence(): int
    {
        return $this->config['max_confidence'];
    }

    public function getMinConfidence(): int
    {
        return $this->config['min_confidence'];
    }

    public function getAllConfig(): array
    {
        return $this->config;
    }
}
