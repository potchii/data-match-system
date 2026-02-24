<?php

namespace Database\Factories;

use App\Models\UploadBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

class UploadBatchFactory extends Factory
{
    protected $model = UploadBatch::class;

    public function definition(): array
    {
        return [
            'file_name' => $this->faker->word . '.xlsx',
            'uploaded_by' => $this->faker->name,
            'uploaded_at' => now(),
            'status' => $this->faker->randomElement(['PROCESSING', 'COMPLETED', 'FAILED']),
        ];
    }
}

