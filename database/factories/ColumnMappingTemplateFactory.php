<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ColumnMappingTemplate>
 */
class ColumnMappingTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'name' => fake()->words(3, true),
            'mappings' => [
                'Employee No' => 'uid',
                'Surname' => 'last_name',
                'Given Name' => 'first_name',
            ],
        ];
    }

    /**
     * Define a template with HR-specific mappings
     */
    public function hrTemplate(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'HR Department Import',
            'mappings' => [
                'Employee No' => 'uid',
                'Surname' => 'last_name',
                'Given Name' => 'first_name',
                'Department' => 'dept',
                'Position' => 'position',
            ],
        ]);
    }

    /**
     * Define a template with student-specific mappings
     */
    public function studentTemplate(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Student Records Import',
            'mappings' => [
                'Student ID' => 'uid',
                'Last Name' => 'last_name',
                'First Name' => 'first_name',
                'Course' => 'course',
                'Year Level' => 'year_level',
            ],
        ]);
    }
}
