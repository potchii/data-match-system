<?php

namespace Database\Factories;

use App\Models\MainSystem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MainSystem>
 */
class MainSystemFactory extends Factory
{
    protected $model = MainSystem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();
        $middleName = fake()->firstName();

        return [
            'uid' => 'UID-' . strtoupper(Str::random(10)),
            'origin_batch_id' => null,
            'origin_match_result_id' => null,
            'last_name' => $lastName,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name_normalized' => strtolower($lastName),
            'first_name_normalized' => strtolower($firstName),
            'middle_name_normalized' => strtolower($middleName),
            'suffix' => null,
            'birthday' => fake()->date('Y-m-d', '-18 years'),
            'gender' => fake()->randomElement(['Male', 'Female']),
            'civil_status' => fake()->randomElement(['Single', 'Married', 'Widowed', 'Divorced']),
            'address' => fake()->buildingNumber() . ' ' . fake()->streetName() . ', ' . fake()->city() . ', ' . fake()->state(),
            'barangay' => 'Barangay ' . fake()->word(),
        ];
    }
}
