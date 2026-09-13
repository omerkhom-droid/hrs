<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MobileLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' =>
                mb_strtolower(
                    trim((string) $this->input('email'))
                ),

            'device_uuid' =>
                trim((string) $this->input('device_uuid')),

            'platform' =>
                mb_strtolower(
                    trim((string) $this->input('platform'))
                ),
        ]);
    }


    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'max:255',
            ],

            'password' => [
                'required',
                'string',
                'max:255',
            ],

            'device_uuid' => [
                'required',
                'string',
                'min:10',
                'max:255',
            ],

            'platform' => [
                'required',
                Rule::in([
                    'android',
                    'ios',
                ]),
            ],

            'device_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'device_model' => [
                'nullable',
                'string',
                'max:255',
            ],

            'os_version' => [
                'nullable',
                'string',
                'max:100',
            ],

            'app_version' => [
                'nullable',
                'string',
                'max:100',
            ],

            'push_token' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }


    public function messages(): array
    {
        return [
            'email.required' =>
                'يجب إدخال البريد الإلكتروني.',

            'email.email' =>
                'البريد الإلكتروني غير صحيح.',

            'password.required' =>
                'يجب إدخال كلمة المرور.',

            'device_uuid.required' =>
                'تعذر التعرف على الجهاز.',

            'platform.required' =>
                'يجب تحديد نظام تشغيل الجهاز.',

            'platform.in' =>
                'نظام تشغيل الجهاز غير مدعوم.',
        ];
    }
}