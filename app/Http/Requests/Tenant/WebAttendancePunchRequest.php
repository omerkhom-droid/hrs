<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class WebAttendancePunchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(
            'self_service.attendance'
        );
    }

    public function rules(): array
    {
        return [
            'latitude' => [
                'nullable',
                'required_with:longitude',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                'nullable',
                'required_with:latitude',
                'numeric',
                'between:-180,180',
            ],
            'accuracy' => [
                'nullable',
                'numeric',
                'min:0',
                'max:5000',
            ],
            'photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'capture_method' => [
                'nullable',
                'required_with:photo',
                'in:camera',
            ],
            'captured_at' => [
                'nullable',
                'required_with:photo',
                'date',
            ],
            'camera_facing' => [
                'nullable',
                'required_with:photo',
                'in:user',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'latitude' => 'خط العرض',
            'longitude' => 'خط الطول',
            'accuracy' => 'دقة الموقع',
            'photo' => 'صورة إثبات الحضور',
            'capture_method' => 'طريقة التقاط الصورة',
            'captured_at' => 'وقت التقاط الصورة',
            'camera_facing' => 'اتجاه الكاميرا',
        ];
    }
}
