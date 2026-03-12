<?php

namespace App\Services;

use App\Models\MainSystem;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class MainSystemValidationService
{
    /**
     * Validate Main System record data for creation
     *
     * @param array $data The data to validate
     * @return array Array with 'valid' boolean and 'errors' array if invalid
     */
    public function validateForCreate(array $data): array
    {
        $rules = $this->getCreationRules();
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        return ['valid' => true, 'errors' => []];
    }

    /**
     * Validate Main System record data for update
     *
     * @param array $data The data to validate
     * @param int $recordId The ID of the record being updated
     * @return array Array with 'valid' boolean and 'errors' array if invalid
     */
    public function validateForUpdate(array $data, int $recordId): array
    {
        $rules = $this->getUpdateRules($recordId);
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        return ['valid' => true, 'errors' => []];
    }

    /**
     * Get validation rules for record creation
     *
     * @return array Validation rules
     */
    private function getCreationRules(): array
    {
        return [
            'uid' => ['required', 'string', 'max:255', 'unique:main_system,uid'],
            'regs_no' => ['nullable', 'string', 'max:255'],
            'registration_date' => ['nullable', 'date_format:Y-m-d'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'birthday' => ['nullable', 'date_format:Y-m-d', 'before:today'],
            'gender' => ['nullable', Rule::in(['Male', 'Female', 'Other'])],
            'civil_status' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'archived'])],
            'category' => ['nullable', 'string', 'max:255'],
            'templateFields' => ['nullable', 'array'],
            'templateFields.*' => ['nullable', 'string'],
        ];
    }

    /**
     * Get validation rules for record update
     *
     * @param int $recordId The ID of the record being updated
     * @return array Validation rules
     */
    private function getUpdateRules(int $recordId): array
    {
        return [
            'uid' => ['sometimes', 'string', 'max:255', Rule::unique('main_system', 'uid')->ignore($recordId)],
            'regs_no' => ['nullable', 'string', 'max:255'],
            'registration_date' => ['nullable', 'date_format:Y-m-d'],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'birthday' => ['nullable', 'date_format:Y-m-d', 'before:today'],
            'gender' => ['nullable', Rule::in(['Male', 'Female', 'Other'])],
            'civil_status' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'archived'])],
            'category' => ['nullable', 'string', 'max:255'],
            'templateFields' => ['nullable', 'array'],
            'templateFields.*' => ['nullable', 'string'],
        ];
    }

    /**
     * Validate a bulk status update request
     *
     * @param array $data The data to validate
     * @return array Array with 'valid' boolean and 'errors' array if invalid
     */
    public function validateBulkStatusUpdate(array $data): array
    {
        $rules = [
            'recordIds' => ['required', 'array', 'min:1'],
            'recordIds.*' => ['integer', 'exists:main_system,id'],
            'status' => ['required', Rule::in(['active', 'inactive', 'archived'])],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        return ['valid' => true, 'errors' => []];
    }

    /**
     * Validate a bulk category update request
     *
     * @param array $data The data to validate
     * @return array Array with 'valid' boolean and 'errors' array if invalid
     */
    public function validateBulkCategoryUpdate(array $data): array
    {
        $rules = [
            'recordIds' => ['required', 'array', 'min:1'],
            'recordIds.*' => ['integer', 'exists:main_system,id'],
            'category' => ['required', 'string', 'max:255'],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        return ['valid' => true, 'errors' => []];
    }

    /**
     * Validate a bulk delete request
     *
     * @param array $data The data to validate
     * @return array Array with 'valid' boolean and 'errors' array if invalid
     */
    public function validateBulkDelete(array $data): array
    {
        $rules = [
            'recordIds' => ['required', 'array', 'min:1'],
            'recordIds.*' => ['integer', 'exists:main_system,id'],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        return ['valid' => true, 'errors' => []];
    }
}
