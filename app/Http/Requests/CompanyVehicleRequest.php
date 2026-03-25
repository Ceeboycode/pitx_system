<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CompanyVehicleRequest extends FormRequest
{
    private const DOC_TYPES = [
        'insurance_certificate' => 'Insurance Certificate',
        'cpc' => 'Certificate of Public Convenience (CPC)',
        'official_receipt' => 'Official Receipt (OR)',
        'certificate_of_registration' => 'Certificate of Registration (CR)',
        'puv_identification_markings' => 'PUV Identification Markings',
    ];

    public function authorize(): bool
    {
        return $this->user()?->company !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'vehicle_type' => $this->clean($this->vehicle_type),
            'plate_number' => strtoupper($this->clean($this->plate_number)),
            'body_number' => $this->clean($this->body_number),
            'color' => $this->clean($this->color),
            'engine_number' => $this->clean($this->engine_number),
            'chassis_number' => $this->clean($this->chassis_number),
            'make_model' => $this->clean($this->make_model),
            'documents' => collect($this->input('documents', []))
                ->map(function ($document) {
                    if (! is_array($document)) {
                        return $document;
                    }

                    $document['document_type'] = isset($document['document_type'])
                        ? trim((string) $document['document_type'])
                        : null;

                    $document['issued_at'] = isset($document['issued_at']) && $document['issued_at'] !== ''
                        ? trim((string) $document['issued_at'])
                        : null;

                    $document['expires_at'] = isset($document['expires_at']) && $document['expires_at'] !== ''
                        ? trim((string) $document['expires_at'])
                        : null;

                    return $document;
                })
                ->values()
                ->all(),
        ]);
    }

    public function rules(): array
    {
        return [
            'vehicle_type' => ['required', 'string', 'max:100'],
            'plate_number' => ['required', 'string', 'max:20', 'unique:vehicles,plate_number'],
            'body_number' => ['required', 'string', 'max:50'],
            'capacity' => ['required', 'integer', 'min:1', 'max:200'],
            'color' => ['required', 'string', 'max:50'],
            'engine_number' => ['required', 'string', 'max:100'],
            'chassis_number' => ['required', 'string', 'max:100'],
            'make_model' => ['required', 'string', 'max:100'],
            'route_id' => [
                'required',
                Rule::exists('routes', 'id')->where(fn ($query) => $query->where('status', 'active')),
            ],

            'documents' => ['required', 'array', 'size:5'],
            'documents.*.document_type' => [
                'required',
                'string',
                Rule::in(array_keys(self::DOC_TYPES)),
            ],
            'documents.*.file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'documents.*.issued_at' => ['nullable', 'date'],
            'documents.*.expires_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle_type.required' => 'Please enter the vehicle type.',
            'vehicle_type.max' => 'The vehicle type may not exceed 100 characters.',

            'plate_number.required' => 'Please enter the plate number.',
            'plate_number.max' => 'The plate number may not exceed 20 characters.',
            'plate_number.unique' => 'This plate number is already registered.',

            'body_number.required' => 'Please enter the body number.',
            'body_number.max' => 'The body number may not exceed 50 characters.',

            'capacity.required' => 'Please enter the seating capacity.',
            'capacity.integer' => 'The seating capacity must be a whole number.',
            'capacity.min' => 'The seating capacity must be at least 1.',
            'capacity.max' => 'The seating capacity may not exceed 200.',

            'color.required' => 'Please enter the vehicle color.',
            'color.max' => 'The vehicle color may not exceed 50 characters.',

            'engine_number.required' => 'Please enter the engine number.',
            'engine_number.max' => 'The engine number may not exceed 100 characters.',

            'chassis_number.required' => 'Please enter the chassis number.',
            'chassis_number.max' => 'The chassis number may not exceed 100 characters.',

            'make_model.required' => 'Please enter the make and model.',
            'make_model.max' => 'The make and model may not exceed 100 characters.',

            'route_id.required' => 'Please select a route.',
            'route_id.exists' => 'The selected route is invalid.',

            'documents.required' => 'All required documents must be uploaded.',
            'documents.array' => 'The documents data is invalid.',
            'documents.size' => 'Exactly 5 required documents must be submitted.',

            'documents.*.document_type.required' => 'Please select a document type.',
            'documents.*.document_type.in' => 'The selected document type is invalid.',

            'documents.*.file.required' => 'Please upload the document file.',
            'documents.*.file.file' => 'The uploaded document must be a valid file.',
            'documents.*.file.mimes' => 'The document must be a PDF, JPG, JPEG, PNG, or WEBP file.',
            'documents.*.file.max' => 'The document file must not exceed 5 MB.',

            'documents.*.issued_at.date' => 'The issue date must be a valid date.',
            'documents.*.expires_at.date' => 'The expiry date must be a valid date.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $documents = $this->input('documents', []);
            $documentTypes = collect($documents)->pluck('document_type')->filter();

            if ($documentTypes->count() !== $documentTypes->unique()->count()) {
                $validator->errors()->add('documents', 'Duplicate document types are not allowed.');
            }

            $requiredTypes = collect(array_keys(self::DOC_TYPES));
            $submittedTypes = $documentTypes->values();

            if ($submittedTypes->count() !== $requiredTypes->count() || $requiredTypes->diff($submittedTypes)->isNotEmpty()) {
                $validator->errors()->add('documents', 'All required document types must be submitted.');
            }

            foreach ($documents as $index => $document) {
                $documentType = $document['document_type'] ?? null;
                $issuedAt = $document['issued_at'] ?? null;
                $expiresAt = $document['expires_at'] ?? null;

                if ($this->usesDocumentDates($documentType)) {
                    if (! $issuedAt) {
                        $validator->errors()->add(
                            "documents.$index.issued_at",
                            'Please enter the issue date.'
                        );
                    }

                    if (! $expiresAt) {
                        $validator->errors()->add(
                            "documents.$index.expires_at",
                            'Please enter the expiry date.'
                        );
                    }
                }

                if (! $issuedAt || ! $expiresAt || ! $this->usesDocumentDates($documentType)) {
                    continue;
                }

                try {
                    if (Carbon::parse($expiresAt)->lt(Carbon::parse($issuedAt))) {
                        $validator->errors()->add(
                            "documents.$index.expires_at",
                            'The expiry date must be on or after the issued date.'
                        );
                    }
                } catch (\Throwable $e) {
                    // Let base date validation handle invalid values.
                }
            }
        });
    }

    private function usesDocumentDates(?string $documentType): bool
    {
        return $documentType !== 'puv_identification_markings';
    }

    private function clean(mixed $value): mixed
    {
        return is_string($value) ? trim($value) : $value;
    }
}