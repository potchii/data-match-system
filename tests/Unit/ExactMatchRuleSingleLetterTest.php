<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\MatchingRules\ExactMatchRule;
use App\Models\MainSystem;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExactMatchRuleSingleLetterTest extends TestCase
{
    use RefreshDatabase;

    protected ExactMatchRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new ExactMatchRule();
    }

    /** @test */
    public function it_rejects_single_letter_middle_names_in_uploaded_data()
    {
        // Create a candidate with full middle name
        $candidate = MainSystem::factory()->create([
            'first_name_normalized' => 'anson',
            'last_name_normalized' => 'agahon',
            'middle_name_normalized' => 'david',
            'birthday' => '1990-01-15',
            'suffix' => '',
        ]);

        // Try to match with single-letter middle name
        $uploadedData = [
            'first_name_normalized' => 'anson',
            'last_name_normalized' => 'agahon',
            'middle_name_normalized' => 'd',
            'birthday' => '1990-01-15',
        ];

        $result = $this->rule->match($uploadedData, collect([$candidate]));

        $this->assertNull($result, 'Single-letter middle name in uploaded data should not match');
    }

    /** @test */
    public function it_rejects_single_letter_middle_names_in_candidate_data()
    {
        // Create a candidate with single-letter middle name
        $candidate = MainSystem::factory()->create([
            'first_name_normalized' => 'rosita',
            'last_name_normalized' => 'aguilar',
            'middle_name_normalized' => 'c',
            'birthday' => '1985-03-20',
            'suffix' => '',
        ]);

        // Try to match with full middle name
        $uploadedData = [
            'first_name_normalized' => 'rosita',
            'last_name_normalized' => 'aguilar',
            'middle_name_normalized' => 'carmen',
            'birthday' => '1985-03-20',
        ];

        $result = $this->rule->match($uploadedData, collect([$candidate]));

        $this->assertNull($result, 'Single-letter middle name in candidate should not match');
    }

    /** @test */
    public function it_rejects_when_both_have_single_letter_middle_names()
    {
        // Create a candidate with single-letter middle name
        $candidate = MainSystem::factory()->create([
            'first_name_normalized' => 'john',
            'last_name_normalized' => 'doe',
            'middle_name_normalized' => 'd',
            'birthday' => '1992-06-10',
            'suffix' => '',
        ]);

        // Try to match with single-letter middle name
        $uploadedData = [
            'first_name_normalized' => 'john',
            'last_name_normalized' => 'doe',
            'middle_name_normalized' => 'd',
            'birthday' => '1992-06-10',
        ];

        $result = $this->rule->match($uploadedData, collect([$candidate]));

        $this->assertNull($result, 'Both having single-letter middle names should not match');
    }

    /** @test */
    public function it_accepts_two_letter_middle_names()
    {
        // Create a candidate with two-letter middle name
        $candidate = MainSystem::factory()->create([
            'first_name_normalized' => 'maria',
            'last_name_normalized' => 'santos',
            'middle_name_normalized' => 'jo',
            'birthday' => '1988-12-05',
            'suffix' => '',
        ]);

        // Match with same two-letter middle name
        $uploadedData = [
            'first_name_normalized' => 'maria',
            'last_name_normalized' => 'santos',
            'middle_name_normalized' => 'jo',
            'birthday' => '1988-12-05',
        ];

        $result = $this->rule->match($uploadedData, collect([$candidate]));

        $this->assertNotNull($result, 'Two-letter middle names should match');
        $this->assertEquals($candidate->id, $result['record']->id);
        $this->assertEquals(100.0, $result['confidence']);
    }

    /** @test */
    public function it_accepts_full_middle_names()
    {
        // Create a candidate with full middle name
        $candidate = MainSystem::factory()->create([
            'first_name_normalized' => 'pedro',
            'last_name_normalized' => 'reyes',
            'middle_name_normalized' => 'garcia',
            'birthday' => '1995-08-22',
            'suffix' => '',
        ]);

        // Match with same full middle name
        $uploadedData = [
            'first_name_normalized' => 'pedro',
            'last_name_normalized' => 'reyes',
            'middle_name_normalized' => 'garcia',
            'birthday' => '1995-08-22',
        ];

        $result = $this->rule->match($uploadedData, collect([$candidate]));

        $this->assertNotNull($result, 'Full middle names should match');
        $this->assertEquals($candidate->id, $result['record']->id);
        $this->assertEquals(100.0, $result['confidence']);
    }
}
