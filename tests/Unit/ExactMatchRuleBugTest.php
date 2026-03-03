<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\MatchingRules\ExactMatchRule;
use App\Models\MainSystem;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExactMatchRuleBugTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_does_not_match_initial_with_full_middle_name()
    {
        // Create a candidate with full middle name
        $candidate = MainSystem::factory()->create([
            'first_name' => 'Rodil',
            'last_name' => 'Aguilar',
            'middle_name' => 'Santiago',
            'suffix' => '',
            'first_name_normalized' => 'rodil',
            'last_name_normalized' => 'aguilar',
            'middle_name_normalized' => 'santiago',
            'birthday' => '1990-01-15',
        ]);

        // Test data with initial only
        $normalizedData = [
            'first_name' => 'Rodil',
            'last_name' => 'Aguilar',
            'middle_name' => 'S',
            'first_name_normalized' => 'rodil',
            'last_name_normalized' => 'aguilar',
            'middle_name_normalized' => 's',
            'birthday' => '1990-01-15',
        ];

        $rule = new ExactMatchRule();
        $candidates = collect([$candidate]);

        $result = $rule->match($normalizedData, $candidates);

        // Should NOT match because "s" !== "santiago"
        $this->assertNull($result, 'ExactMatchRule should NOT match initial "s" with full name "santiago"');
    }

    /** @test */
    public function it_does_not_match_when_both_birthdays_are_null()
    {
        // Create a candidate with null birthday
        $candidate = MainSystem::factory()->create([
            'first_name' => 'Librada',
            'last_name' => 'Aguilar',
            'middle_name' => 'I',
            'suffix' => '',
            'first_name_normalized' => 'librada',
            'last_name_normalized' => 'aguilar',
            'middle_name_normalized' => 'i',
            'birthday' => null,
        ]);

        // Test data with null birthday
        $normalizedData = [
            'first_name' => 'Librada',
            'last_name' => 'Aguilar',
            'middle_name' => 'I',
            'first_name_normalized' => 'librada',
            'last_name_normalized' => 'aguilar',
            'middle_name_normalized' => 'i',
            'birthday' => null,
        ];

        $rule = new ExactMatchRule();
        $candidates = collect([$candidate]);

        $result = $rule->match($normalizedData, $candidates);

        // Should NOT match because birthday is required
        $this->assertNull($result, 'ExactMatchRule should NOT match when both birthdays are null');
    }

    /** @test */
    public function it_does_not_match_when_middle_name_is_whitespace_only()
    {
        // Create a candidate with whitespace-only middle name
        $candidate = MainSystem::factory()->create([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'middle_name' => '   ',
            'suffix' => '',
            'first_name_normalized' => 'maria',
            'last_name_normalized' => 'santos',
            'middle_name_normalized' => '   ',
            'birthday' => '1990-01-15',
        ]);

        // Test data with whitespace-only middle name
        $normalizedData = [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'middle_name' => '  ',
            'first_name_normalized' => 'maria',
            'last_name_normalized' => 'santos',
            'middle_name_normalized' => '  ',
            'birthday' => '1990-01-15',
        ];

        $rule = new ExactMatchRule();
        $candidates = collect([$candidate]);

        $result = $rule->match($normalizedData, $candidates);

        // Should NOT match because whitespace-only middle names should be treated as empty
        $this->assertNull($result, 'ExactMatchRule should NOT match when middle name is whitespace-only');
    }

    /** @test */
    public function it_does_not_match_when_birthday_is_whitespace_only()
    {
        // Create a candidate with whitespace-only birthday
        $candidate = MainSystem::factory()->create([
            'first_name' => 'Jose',
            'last_name' => 'Reyes',
            'middle_name' => 'Cruz',
            'suffix' => '',
            'first_name_normalized' => 'jose',
            'last_name_normalized' => 'reyes',
            'middle_name_normalized' => 'cruz',
            'birthday' => '   ',
        ]);

        // Test data with whitespace-only birthday
        $normalizedData = [
            'first_name' => 'Jose',
            'last_name' => 'Reyes',
            'middle_name' => 'Cruz',
            'first_name_normalized' => 'jose',
            'last_name_normalized' => 'reyes',
            'middle_name_normalized' => 'cruz',
            'birthday' => '  ',
        ];

        $rule = new ExactMatchRule();
        $candidates = collect([$candidate]);

        $result = $rule->match($normalizedData, $candidates);

        // Should NOT match because whitespace-only birthdays should be treated as empty
        $this->assertNull($result, 'ExactMatchRule should NOT match when birthday is whitespace-only');
    }

    /** @test */
    public function it_matches_when_all_fields_are_identical()
    {
        // Create a candidate with all fields
        $candidate = MainSystem::factory()->create([
            'first_name' => 'Juan',
            'last_name' => 'Cruz',
            'middle_name' => 'Pedro',
            'suffix' => '',
            'first_name_normalized' => 'juan',
            'last_name_normalized' => 'cruz',
            'middle_name_normalized' => 'pedro',
            'birthday' => '1990-01-15',
        ]);

        // Test data with identical fields
        $normalizedData = [
            'first_name' => 'Juan',
            'last_name' => 'Cruz',
            'middle_name' => 'Pedro',
            'first_name_normalized' => 'juan',
            'last_name_normalized' => 'cruz',
            'middle_name_normalized' => 'pedro',
            'birthday' => '1990-01-15',
        ];

        $rule = new ExactMatchRule();
        $candidates = collect([$candidate]);

        $result = $rule->match($normalizedData, $candidates);

        // Should match
        $this->assertNotNull($result, 'ExactMatchRule SHOULD match when all fields are identical');
        $this->assertEquals(100.0, $result['confidence']);
        $this->assertEquals('exact_match', $result['rule']);
    }
}
