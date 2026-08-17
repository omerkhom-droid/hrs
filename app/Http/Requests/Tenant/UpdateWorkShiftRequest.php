<?php

namespace App\Http\Requests\Tenant;

use App\Models\WorkShift;
use Illuminate\Validation\Rule;

class UpdateWorkShiftRequest extends StoreWorkShiftRequest
{
    protected function prepareForValidation(): void
    {
        $values = [];

        if ($this->exists('code')) {
            $values['code'] = mb_strtoupper(
                trim((string) $this->input('code'))
            );
        }

        foreach (['name', 'name_en'] as $field) {
            if ($this->exists($field)) {
                $values[$field] = $this->clean(
                    $this->input($field)
                );
            }
        }

        foreach ([
            'crosses_midnight',
            'is_default',
            'is_active',
        ] as $field) {
            if ($this->exists($field)) {
                $values[$field] = $this->boolean($field);
            }
        }

        if ($this->exists('work_days')) {
            $values['work_days'] = array_values(array_unique(
                array_map('intval', (array) $this->input('work_days'))
            ));
        }

        $this->merge($values);
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $tenantId = (int) $this->user()->tenant_id;
        $shift = $this->route('shift');
        $shiftId = $shift instanceof WorkShift
            ? $shift->getKey()
            : $shift;

        foreach ($rules as $field => $fieldRules) {
            $rules[$field] = ['sometimes', ...$fieldRules];
        }

        $rules['code'] = [
            'sometimes',
            'required',
            'string',
            'max:50',
            Rule::unique('work_shifts', 'code')
                ->where('tenant_id', $tenantId)
                ->ignore($shiftId),
        ];

        return $rules;
    }
}
