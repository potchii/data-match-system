<?php

namespace Tests\Unit;

use App\Models\MainSystem;
use App\Services\ConfidenceScoreService;
use PHPUnit\Framework\TestCase;

class ConfidenceScoreServiceNullMiddleNameTest extends TestCase
{
    private ConfidenceScoreService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ConfidenceScoreService();
    }

    public function test_breakdown_includes_middle_name_when_uploaded_value_is_null()
    {
        // Arrange
        $uploadedData = [
            'core_fields' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                // middle_name is intentionally missing (null)
                'birthday' => '1990-01-15',
            ],
        ];

        $existingRecord = new MainSystem([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'middle_name' => 'Michael',
            'birthday' => '1990-01-15',
        ]);

        // Act
        $result = $this->service->generateBreakdown($uploadedData, $existingRecord);

        // Assert
        $this->assertArrayHasKey('middle_name', $result['core_fields']);
        $this->assertNull($result['core_fields']['middle_name']['uploaded']);
        $this->assertEquals('Michael', $result['core_fields']['middle_name']['existing']);
        $this->assertEquals('mismatch', $result['core_fields']['middle_name']['status']);
    }

    public function test_breakdown_includes_middle_name_when_both_values_are_null()
    {
        // Arrange
        $uploadedData = [
            'core_fields' => [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                // middle_name is intentionally missing (null)
                'birthday' => '1985-03-20',
            ],
        ];

        $existingRecord = new MainSystem([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'middle_name' => null,
            'birthday' => '1985-03-20',
        ]);

        // Act
        $result = $this->service->generateBreakdown($uploadedData, $existingRecord);

        // Assert
        // middle_name should NOT be included if both are null
        $this->assertArrayNotHasKey('middle_name', $result['core_fields']);
    }

    public function test_breakdown_includes_middle_name_when_uploaded_has_value()
    {
        // Arrange
        $uploadedData = [
            'core_fields' => [
                'first_name' => 'Robert',
                'last_name' => 'Johnson',
                'middle_name' => 'James',
                'birthday' => '1992-07-10',
            ],
        ];

        $existingRecord = new MainSystem([
            'first_name' => 'Robert',
            'last_name' => 'Johnson',
            'middle_name' => 'James',
            'birthday' => '1992-07-10',
        ]);

        // Act
        $result = $this->service->generateBreakdown($uploadedData, $existingRecord);

        // Assert
        $this->assertArrayHasKey('middle_name', $result['core_fields']);
        $this->assertEquals('James', $result['core_fields']['middle_name']['uploaded']);
        $this->assertEquals('James', $result['core_fields']['middle_name']['existing']);
        $this->assertEquals('match', $result['core_fields']['middle_name']['status']);
    }
}
