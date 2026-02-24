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
            'street_no' => fake()->buildingNumber(),
            'street' => fake()->streetName(),
            'city' => fake()->city(),
            'province' => fake()->state(),
            'barangay' => 'Barangay ' . fake()->word(),
            'additional_attributes' => null,
        ];
    }

    /**
     * Indicate that the model has dynamic attributes.
     */
    public function withDynamicAttributes(array $attributes = []): static
    {
        return $this->state(fn (array $state) => [
            'additional_attributes' => empty($attributes) ? $this->generateRandomDynamicAttributes() : $attributes,
        ]);
    }

    /**
     * Generate random dynamic attributes for testing.
     */
    protected function generateRandomDynamicAttributes(): array
    {
        return [
            'employee_id' => 'EMP-' . fake()->year() . '-' . fake()->numberBetween(100, 999),
            'department' => fake()->randomElement(['IT', 'HR', 'Finance', 'Operations', 'Marketing', 'Sales']),
            'position' => fake()->randomElement(['Manager', 'Supervisor', 'Staff', 'Senior Staff', 'Director']),
            'salary_grade' => 'SG-' . fake()->numberBetween(1, 20),
            'contact_number' => '+63-' . fake()->numerify('9##-###-####'),
        ];
    }

    /**
     * Create model with employee-related dynamic attributes.
     */
    public function withEmployeeAttributes(): static
    {
        return $this->state(fn (array $state) => [
            'additional_attributes' => [
                'employee_id' => 'EMP-' . fake()->year() . '-' . fake()->numberBetween(100, 999),
                'department' => fake()->randomElement(['IT', 'HR', 'Finance', 'Operations', 'Marketing', 'Sales']),
                'position' => fake()->jobTitle(),
                'salary_grade' => 'SG-' . fake()->numberBetween(1, 20),
                'hire_date' => fake()->date('Y-m-d', '-5 years'),
                'employment_status' => fake()->randomElement(['Regular', 'Probationary', 'Contractual']),
                'contact_number' => '+63-' . fake()->numerify('9##-###-####'),
                'email' => fake()->safeEmail(),
            ],
        ]);
    }

    /**
     * Create model with student-related dynamic attributes.
     */
    public function withStudentAttributes(): static
    {
        return $this->state(fn (array $state) => [
            'additional_attributes' => [
                'student_id' => 'STU-' . fake()->year() . '-' . fake()->numberBetween(1000, 9999),
                'course' => fake()->randomElement(['BSIT', 'BSCS', 'BSBA', 'BSN', 'BSED']),
                'year_level' => fake()->randomElement(['1st Year', '2nd Year', '3rd Year', '4th Year']),
                'section' => fake()->randomElement(['A', 'B', 'C', 'D']),
                'enrollment_date' => fake()->date('Y-m-d', '-2 years'),
                'scholarship' => fake()->randomElement(['None', 'Academic', 'Athletic', 'Government']),
                'guardian_name' => fake()->name(),
                'guardian_contact' => '+63-' . fake()->numerify('9##-###-####'),
            ],
        ]);
    }

    /**
     * Create model with healthcare-related dynamic attributes.
     */
    public function withHealthcareAttributes(): static
    {
        return $this->state(fn (array $state) => [
            'additional_attributes' => [
                'patient_id' => 'PAT-' . fake()->year() . '-' . fake()->numberBetween(10000, 99999),
                'blood_type' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
                'philhealth_number' => fake()->numerify('##-#########-#'),
                'emergency_contact' => fake()->name(),
                'emergency_contact_number' => '+63-' . fake()->numerify('9##-###-####'),
                'allergies' => fake()->randomElement(['None', 'Penicillin', 'Peanuts', 'Shellfish']),
                'medical_conditions' => fake()->randomElement(['None', 'Hypertension', 'Diabetes', 'Asthma']),
            ],
        ]);
    }

    /**
     * Create model with minimal dynamic attributes (1-2 fields).
     */
    public function withMinimalDynamicAttributes(): static
    {
        return $this->state(fn (array $state) => [
            'additional_attributes' => [
                'reference_id' => 'REF-' . fake()->uuid(),
            ],
        ]);
    }

    /**
     * Create model with extensive dynamic attributes (many fields).
     */
    public function withExtensiveDynamicAttributes(): static
    {
        $attributes = [];
        $fieldCount = fake()->numberBetween(10, 20);
        
        for ($i = 1; $i <= $fieldCount; $i++) {
            $attributes['custom_field_' . $i] = fake()->word() . ' ' . fake()->numberBetween(1, 100);
        }
        
        return $this->state(fn (array $state) => [
            'additional_attributes' => $attributes,
        ]);
    }

    /**
     * Create model with nested array dynamic attributes.
     */
    public function withNestedDynamicAttributes(): static
    {
        return $this->state(fn (array $state) => [
            'additional_attributes' => [
                'contact_info' => [
                    'mobile' => '+63-' . fake()->numerify('9##-###-####'),
                    'landline' => fake()->phoneNumber(),
                    'email' => fake()->safeEmail(),
                ],
                'address_details' => [
                    'house_number' => fake()->buildingNumber(),
                    'subdivision' => fake()->streetName() . ' Subdivision',
                    'zip_code' => fake()->postcode(),
                ],
                'metadata' => [
                    'source' => fake()->randomElement(['Online', 'Walk-in', 'Referral']),
                    'verified' => fake()->boolean(),
                    'notes' => fake()->sentence(),
                ],
            ],
        ]);
    }
}
