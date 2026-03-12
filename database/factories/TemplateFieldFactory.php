<?php

namespace Database\Factories;

use App\Models\ColumnMappingTemplate;
use App\Models\TemplateField;
use Illuminate\Database\Eloquent\Factories\Factory;

class TemplateFieldFactory extends Factory
{
    protected $model = TemplateField::class;

    public function definition(): array
    {
        return [
            'template_id' => ColumnMappingTemplate::factory(),
            'field_name' => $this->faker->unique()->word(),
            'field_type' => $this->faker->randomElement(['string', 'integer', 'date', 'boolean', 'decimal']),
            'is_required' => $this->faker->boolean(),
        ];
    }
}
