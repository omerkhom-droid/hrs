<?php

namespace App\Http\Requests\Tenant;

use App\Models\Holiday;
use Illuminate\Validation\Rule;

class UpdateHolidayRequest extends StoreHolidayRequest
{
    public function authorize(): bool
    {
        if (!parent::authorize()) {
            return false;
        }


        $holiday =
            $this->route('holiday');


        /*
         * التأكد من أن العطلة تتبع
         * الشركة الحالية.
         */
        if (
            $holiday instanceof Holiday &&
            (int) $holiday->tenant_id !==
            (int) $this->user()->tenant_id
        ) {
            return false;
        }


        return true;
    }


    public function rules(): array
    {
        $rules =
            parent::rules();


        $tenantId = (int) $this
            ->user()
            ->tenant_id;


        $holiday =
            $this->route('holiday');


        $holidayId =
            $holiday instanceof Holiday
                ? $holiday->id
                : (int) $holiday;


        /*
         * استبدال قاعدة الكود فقط
         * لاستثناء العطلة الحالية.
         */
        $rules['code'] = [
            'required',
            'string',
            'max:50',
            'regex:/^[A-Z0-9_-]+$/',

            Rule::unique(
                'holidays',
                'code'
            )
                ->ignore(
                    $holidayId
                )
                ->where(
                    fn ($query) =>
                        $query
                            ->where(
                                'tenant_id',
                                $tenantId
                            )
                            ->whereNull(
                                'deleted_at'
                            )
                ),
        ];


        return $rules;
    }


    public function messages(): array
    {
        return array_merge(
            parent::messages(),
            [
                'code.unique' =>
                    'يوجد سجل عطلة آخر يستخدم الكود نفسه داخل الشركة.',
            ]
        );
    }
}