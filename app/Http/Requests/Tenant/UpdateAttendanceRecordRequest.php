<?php

namespace App\Http\Requests\Tenant;

class UpdateAttendanceRecordRequest extends StoreAttendanceRecordRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->exists('notes')) {
            $this->merge([
                'notes' => $this->clean($this->input('notes')),
            ]);
        }
    }

    public function rules(): array
    {
        $rules = parent::rules();

        foreach ($rules as $field => $fieldRules) {
            $rules[$field] = ['sometimes', ...$fieldRules];
        }

        $rules['employee_id'] = ['prohibited'];
        $rules['attendance_date'] = ['prohibited'];

        return $rules;
    }
}
