<?php

namespace Database\Factories;

use App\Models\MatchResult;
use App\Models\UploadBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

class MatchResultFactory extends Factory
{
    protected $model = MatchResult::class;

    public function definition(): array
    {
        return [
            'batch_id' => UploadBatch::factory(),
            'uploaded_record_id' => 'UID-' . $this->faker->unique()->numerify('########'),
            'uploaded_last_name' => $this->faker->lastName,
            'uploaded_first_name' => $this->faker->firstName,
            'uploaded_middle_name' => $this->faker->optional()->firstName,
            'match_status' => $this->faker->randomElement(['MATCHED', 'POSSIBLE DUPLICATE', 'NEW RECORD']),
            'confidence_score' => $this->faker->randomFloat(1, 0, 100),
            'matched_system_id' => 'UID-' . $this->faker->numerify('########'),
        ];
    }
}

