<?php

namespace App\Http\Requests\Tenant;

use App\Models\EmployeeDocument;
use Illuminate\Validation\Rule;

class UpdateEmployeeDocumentRequest extends StoreEmployeeDocumentRequest
{
    protected function prepareForValidation(): void
    {
        $values = [];

        foreach ([
            'document_number',
            'title',
            'issuing_authority',
            'notes',
        ] as $field) {
            if ($this->exists($field)) {
                $values[$field] = $this->clean(
                    $this->input($field)
                );
            }
        }

        $this->merge($values);
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $tenantId = (int) $this->user()->tenant_id;
        $document = $this->route('document');
        $documentId = $document instanceof EmployeeDocument
            ? $document->getKey()
            : $document;

        foreach ($rules as $field => $fieldRules) {
            $rules[$field] = [
                'sometimes',
                ...$fieldRules,
            ];
        }

        $rules['employee_id'] = ['prohibited'];

        $rules['document_number'] = [
            'sometimes',
            'nullable',
            'string',
            'max:100',
            Rule::unique('employee_documents', 'document_number')
                ->where('tenant_id', $tenantId)
                ->ignore($documentId),
        ];

        $rules['file'] = [
            'sometimes',
            'nullable',
            'file',
            'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx',
            'max:10240',
        ];

        return $rules;
    }
}
