<?php

namespace App\Http\Requests;

use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCompanyVehicleRequest extends FormRequest
{
    private const DOC_TYPES = [
        'ltfrb_certificate' => 'LTFRB Certificate',
        'cpc' => 'Certificate of Public Convenience (CPC)',
        'or_cr' => 'Official Receipt / Certificate of Registration (OR/CR)',
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

                    return $document;
                })
                ->values()
                ->all(),
        ]);
    }

    public function rules(): array
    {
        $vehicleParam = $this->route('vehicle');
        $vehicleId = $vehicleParam instanceof Vehicle ? $vehicleParam->id : $vehicleParam;

        return [
            'vehicle_type' => ['required', 'string', 'max:100'],
            'plate_number' => ['required', 'string', 'max:20', Rule::unique('vehicles', 'plate_number')->ignore($vehicleId)],
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

            'documents' => ['required', 'array', 'size:3'],
            'documents.*.id' => ['nullable', 'integer', 'exists:vehicle_documents,id'],
            'documents.*.document_type' => [
                'required',
                'string',
                Rule::in(array_keys(self::DOC_TYPES)),
            ],
            'documents.*.file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'documents.*.issued_at' => ['required', 'date'],
            'documents.*.expires_at' => ['required', 'date'],
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

            foreach ($documents as $index => $document) {
                $issuedAt = $document['issued_at'] ?? null;
                $expiresAt = $document['expires_at'] ?? null;

                if (! $issuedAt || ! $expiresAt) {
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
                    // Base date validation handles invalid date values.
                }
            }
        });
    }

    private function clean(mixed $value): mixed
    {
        return is_string($value) ? trim($value) : $value;
    }
}
