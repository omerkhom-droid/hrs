<?php

namespace App\Http\Requests\Tenant;

use App\Models\AttendanceRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAttendanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(
            'attendance.manage'
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'notes' => $this->clean($this->input('notes')),
        ]);
    }

    public function rules(): array
    {
        $tenantId = (int) $this->user()->tenant_id;

        return [
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],
            'work_shift_id' => [
                'nullable',
                'integer',
                Rule::exists('work_shifts', 'id')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],
            'work_location_id' => [
                'nullable',
                'integer',
                Rule::exists('work_locations', 'id')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],
            'attendance_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'check_in_at' => ['nullable', 'date'],
            'check_out_at' => [
                'nullable',
                'date',
                'after:check_in_at',
            ],
            'status' => [
                'required',
                Rule::in([
                    'present',
                    'late',
                    'absent',
                    'on_leave',
                    'holiday',
                    'remote',
                    'incomplete',
                ]),
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $routeRecord = $this->route('record');
            $record = $routeRecord instanceof AttendanceRecord
                ? $routeRecord
                : null;

            $status = $this->input(
                'status',
                $record?->status
            );

            $checkIn = $this->exists('check_in_at')
                ? $this->input('check_in_at')
                : $record?->check_in_at;

            if (
                in_array($status, [
                    'present',
                    'late',
                    'remote',
                    'incomplete',
                ], true)
                && !$checkIn
            ) {
                $validator->errors()->add(
                    'check_in_at',
                    'وقت الحضور مطلوب لهذه الحالة.'
                );
            }
        });
    }

    protected function clean(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
