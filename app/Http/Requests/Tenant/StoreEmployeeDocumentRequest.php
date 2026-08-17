<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(
            'documents.manage'
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'document_number' => $this->clean(
                $this->input('document_number')
            ),
            'title' => $this->clean(
                $this->input('title')
            ),
            'issuing_authority' => $this->clean(
                $this->input('issuing_authority')
            ),
            'notes' => $this->clean(
                $this->input('notes')
            ),
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

            'document_type' => [
                'required',
                Rule::in([
                    'identity',
                    'passport',
                    'residency',
                    'contract',
                    'qualification',
                    'certificate',
                    'medical',
                    'insurance',
                    'bank',
                    'license',
                    'other',
                ]),
            ],

            'document_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('employee_documents', 'document_number')
                    ->where('tenant_id', $tenantId),
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'issuing_authority' => [
                'nullable',
                'string',
                'max:255',
            ],

            'issue_date' => [
                'nullable',
                'date',
            ],

            'expiry_date' => [
                'nullable',
                'date',
                'after_or_equal:issue_date',
            ],

            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx',
                'max:10240',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_id' => 'الموظف',
            'document_type' => 'نوع المستند',
            'document_number' => 'رقم المستند',
            'title' => 'عنوان المستند',
            'issuing_authority' => 'جهة الإصدار',
            'issue_date' => 'تاريخ الإصدار',
            'expiry_date' => 'تاريخ الانتهاء',
            'file' => 'ملف المستند',
            'notes' => 'الملاحظات',
        ];
    }

    protected function clean(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
