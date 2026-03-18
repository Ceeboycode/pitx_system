<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyVehicleRequest;
use App\Http\Requests\UpdateCompanyVehicleRequest;
use App\Models\Gate;
use App\Models\Route;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyVehicleController extends Controller
{
    private const DOC_TYPES = [
        'ltfrb_certificate' => 'LTFRB Certificate',
        'cpc' => 'Certificate of Public Convenience (CPC)',
        'or_cr' => 'Official Receipt / Certificate of Registration (OR/CR)',
    ];

    public function index(Request $request): Response
    {
        $user = $request->user();
        $company = $user->company;

        $vehicles = Vehicle::query()
            ->where('company_id', $company->id)
            ->select([
                'id',
                'company_id',
                'route_id',
                'vehicle_type',
                'plate_number',
                'body_number',
                'capacity',
                'color',
                'make_model',
                'status',
                'created_at',
            ])
            ->with([
                'route:id,route_name',
                'documents:id,vehicle_id,document_type,status',
            ])
            ->search($request->search)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('External/Vehicles/Index', [
            'company' => [
                'id' => $company->id,
                'company_name' => $company->company_name,
                'company_code' => $company->company_code,
                'status' => $company->status,
                'logo_url' => $company->logo
                    ? $this->publicDisk()->url($company->logo)
                    : null,
            ],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
            ],
            'vehicles' => $vehicles,
            'filters' => [
                'search' => $request->search,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $company = $user->company;

        $gates = Gate::query()
            ->where('status', 'active')
            ->select([
                'id',
                'gate_name',
                'bays',
            ])
            ->orderBy('gate_name')
            ->get();

        $routes = Route::query()
            ->where('status', 'active')
            ->select([
                'id',
                'gate_id',
                'route_name',
                'origin_name',
                'destination_name',
                'route_geometry',
            ])
            ->with([
                'gate:id,gate_name',
                'stops:id,route_id,stop_name,stop_order,stop_type,address,latitude,longitude',
            ])
            ->orderBy('route_name')
            ->get();

        return Inertia::render('External/Vehicles/Create', [
            'company' => [
                'id' => $company->id,
                'company_name' => $company->company_name,
                'company_code' => $company->company_code,
                'status' => $company->status,
                'logo_url' => $company->logo
                    ? $this->publicDisk()->url($company->logo)
                    : null,
            ],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
            ],
            'gates' => $gates,
            'routes' => $routes,
            'docTypes' => self::DOC_TYPES,
            'mapConfig' => [
                'mapboxToken' => config('app.mapbox_public_token', env('VITE_MAPBOX_TOKEN')),
                'defaultCenter' => [
                    'lng' => 120.9842,
                    'lat' => 14.5995,
                ],
                'defaultZoom' => 11,
            ],
        ]);
    }

    public function show(Request $request, Vehicle $vehicle): Response
    {
        $user = $request->user();
        $company = $user->company;

        abort_unless($vehicle->company_id === $company->id, 404);

        $vehicle->load([
            'route:id,gate_id,route_name,origin_name,destination_name,route_geometry',
            'route.gate:id,gate_name',
            'route.stops:id,route_id,stop_name,stop_order,stop_type,address,latitude,longitude',
            'documents:id,vehicle_id,document_type,file_path,file_name,file_mime_type,file_size,status,issued_at,expires_at,created_at',
        ]);

        $gates = Gate::query()
            ->where('status', 'active')
            ->select([
                'id',
                'gate_name',
                'bays',
            ])
            ->orderBy('gate_name')
            ->get();

        $routes = Route::query()
            ->where('status', 'active')
            ->select([
                'id',
                'gate_id',
                'route_name',
                'origin_name',
                'destination_name',
                'route_geometry',
            ])
            ->with([
                'gate:id,gate_name',
                'stops:id,route_id,stop_name,stop_order,stop_type,address,latitude,longitude',
            ])
            ->orderBy('route_name')
            ->get();

        return Inertia::render('External/Vehicles/Show', [
            'company' => [
                'id' => $company->id,
                'company_name' => $company->company_name,
                'company_code' => $company->company_code,
                'status' => $company->status,
                'logo_url' => $company->logo
                    ? $this->publicDisk()->url($company->logo)
                    : null,
            ],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
            ],
            'vehicle' => [
                'id' => $vehicle->id,
                'route_id' => $vehicle->route_id,
                'vehicle_type' => $vehicle->vehicle_type,
                'plate_number' => $vehicle->plate_number,
                'body_number' => $vehicle->body_number,
                'capacity' => $vehicle->capacity,
                'color' => $vehicle->color,
                'engine_number' => $vehicle->engine_number,
                'chassis_number' => $vehicle->chassis_number,
                'make_model' => $vehicle->make_model,
                'status' => $vehicle->status,
                'created_at' => optional($vehicle->created_at)?->toDateTimeString(),
                'route' => $vehicle->route ? [
                    'id' => $vehicle->route->id,
                    'gate_id' => $vehicle->route->gate_id,
                    'route_name' => $vehicle->route->route_name,
                    'origin_name' => $vehicle->route->origin_name,
                    'destination_name' => $vehicle->route->destination_name,
                    'route_geometry' => $vehicle->route->route_geometry,
                    'gate' => $vehicle->route->gate ? [
                        'id' => $vehicle->route->gate->id,
                        'gate_name' => $vehicle->route->gate->gate_name,
                    ] : null,
                    'stops' => $vehicle->route->stops->map(fn ($stop) => [
                        'id' => $stop->id,
                        'route_id' => $stop->route_id,
                        'stop_name' => $stop->stop_name,
                        'stop_order' => $stop->stop_order,
                        'stop_type' => $stop->stop_type,
                        'address' => $stop->address,
                        'latitude' => $stop->latitude,
                        'longitude' => $stop->longitude,
                    ])->values(),
                ] : null,
                'documents' => $vehicle->documents->map(function ($document) use ($vehicle) {
                    return [
                        'id' => $document->id,
                        'document_type' => $document->document_type,
                        'file_name' => $document->file_name,
                        'file_url' => $document->file_path
                            ? $this->publicDisk()->url($document->file_path)
                            : null,
                        'file_mime_type' => $document->file_mime_type,
                        'file_size' => $document->file_size,
                        'status' => $document->status,
                        'issued_at' => optional($document->issued_at)?->format('Y-m-d'),
                        'expires_at' => optional($document->expires_at)?->format('Y-m-d'),
                        'created_at' => optional($document->created_at)?->toDateTimeString(),
                        'download_url' => route('company.vehicles.documents.download', [
                            'vehicle' => $vehicle->id,
                            'document' => $document->id,
                        ]),
                    ];
                })->values(),
            ],
            'gates' => $gates,
            'routes' => $routes,
            'docTypes' => self::DOC_TYPES,
            'mapConfig' => [
                'mapboxToken' => config('app.mapbox_public_token', env('VITE_MAPBOX_TOKEN')),
                'defaultCenter' => [
                    'lng' => 120.9842,
                    'lat' => 14.5995,
                ],
                'defaultZoom' => 11,
            ],
        ]);
    }

    public function edit(Request $request, Vehicle $vehicle): Response
    {
        $user = $request->user();
        $company = $user->company;

        abort_unless($vehicle->company_id === $company->id, 404);
        abort_if($vehicle->status === 'suspended', 403, 'Suspended vehicles cannot be edited.');

        $vehicle->load([
            'documents:id,vehicle_id,document_type,file_name,status,issued_at,expires_at,created_at',
        ]);

        $gates = Gate::query()
            ->where('status', 'active')
            ->select([
                'id',
                'gate_name',
                'bays',
            ])
            ->orderBy('gate_name')
            ->get();

        $routes = Route::query()
            ->where('status', 'active')
            ->select([
                'id',
                'gate_id',
                'route_name',
                'origin_name',
                'destination_name',
                'route_geometry',
            ])
            ->with([
                'gate:id,gate_name',
                'stops:id,route_id,stop_name,stop_order,stop_type,address,latitude,longitude',
            ])
            ->orderBy('route_name')
            ->get();

        return Inertia::render('External/Vehicles/Edit', [
            'company' => [
                'id' => $company->id,
                'company_name' => $company->company_name,
                'company_code' => $company->company_code,
                'status' => $company->status,
                'logo_url' => $company->logo
                    ? $this->publicDisk()->url($company->logo)
                    : null,
            ],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
            ],
            'vehicle' => [
                'id' => $vehicle->id,
                'route_id' => $vehicle->route_id,
                'vehicle_type' => $vehicle->vehicle_type,
                'plate_number' => $vehicle->plate_number,
                'body_number' => $vehicle->body_number,
                'capacity' => $vehicle->capacity,
                'color' => $vehicle->color,
                'engine_number' => $vehicle->engine_number,
                'chassis_number' => $vehicle->chassis_number,
                'make_model' => $vehicle->make_model,
                'status' => $vehicle->status,
                'documents' => $vehicle->documents->map(fn ($document) => [
                    'id' => $document->id,
                    'document_type' => $document->document_type,
                    'file_name' => $document->file_name,
                    'status' => $document->status,
                    'issued_at' => optional($document->issued_at)?->format('Y-m-d'),
                    'expires_at' => optional($document->expires_at)?->format('Y-m-d'),
                    'created_at' => optional($document->created_at)?->toDateTimeString(),
                ])->values(),
            ],
            'gates' => $gates,
            'routes' => $routes,
            'docTypes' => self::DOC_TYPES,
            'mapConfig' => [
                'mapboxToken' => config('app.mapbox_public_token', env('VITE_MAPBOX_TOKEN')),
                'defaultCenter' => [
                    'lng' => 120.9842,
                    'lat' => 14.5995,
                ],
                'defaultZoom' => 11,
            ],
        ]);
    }

    public function update(UpdateCompanyVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $company = $request->user()->company;
        $user = $request->user();

        abort_unless($vehicle->company_id === $company->id, 404);
        abort_if($vehicle->status === 'suspended', 403, 'Suspended vehicles cannot be updated.');

        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated, $vehicle, $company, $user) {
            $vehicle->update([
                'route_id' => $validated['route_id'],
                'vehicle_type' => $validated['vehicle_type'],
                'plate_number' => strtoupper(trim((string) $validated['plate_number'])),
                'body_number' => $validated['body_number'],
                'capacity' => $validated['capacity'],
                'color' => $validated['color'],
                'engine_number' => $validated['engine_number'],
                'chassis_number' => $validated['chassis_number'],
                'make_model' => $validated['make_model'],
                'status' => 'pending',
                'updated_by' => $user->id,
            ]);

            $documentsByType = $vehicle->documents()->get()->keyBy('document_type');

            foreach ($validated['documents'] ?? [] as $index => $docMeta) {
                $file = data_get($request->file('documents'), "{$index}.file");

                if (! $file) {
                    continue;
                }

                $documentType = $docMeta['document_type'] ?? null;
                $existingDocument = $documentsByType->get($documentType);

                if (! $existingDocument) {
                    throw ValidationException::withMessages([
                        "documents.{$index}.file" => 'Document record not found for update.',
                    ]);
                }

                if (! in_array($existingDocument->status, ['pending', 'rejected'], true)) {
                    throw ValidationException::withMessages([
                        "documents.{$index}.file" => 'Only pending or rejected documents can be reuploaded.',
                    ]);
                }

                $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');

                $companyCode = $this->sanitizeForFileName(
                    $company->company_code ?: 'COMPANY_' . $company->id
                );

                $plateNumber = $this->sanitizeForFileName(
                    $vehicle->plate_number ?: 'VEHICLE_' . $vehicle->id
                );

                $documentTypeSafe = $this->sanitizeForFileName(strtoupper((string) $documentType));
                $timestamp = now()->format('Ymd_His');

                $fileName = "{$companyCode}_{$plateNumber}_{$documentTypeSafe}_{$timestamp}.{$extension}";
                $directory = "company-documents/{$company->id}/vehicles/{$vehicle->id}/{$documentTypeSafe}";
                $newPath = $file->storeAs($directory, $fileName, 'public');

                $oldPath = ltrim((string) $existingDocument->file_path, '/');

                $existingDocument->update([
                    'file_path' => $newPath,
                    'file_name' => $fileName,
                    'file_mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'status' => 'pending',
                    'issued_at' => $docMeta['issued_at'],
                    'expires_at' => $docMeta['expires_at'],
                    'updated_by' => $user->id,
                ]);

                if ($oldPath !== '' && $oldPath !== $newPath && $this->publicDisk()->exists($oldPath)) {
                    $this->publicDisk()->delete($oldPath);
                }
            }
        });

        return to_route('company.vehicles.show', $vehicle)
            ->with('success', 'Vehicle updated successfully.');
    }

    public function toggleStatus(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $company = $request->user()->company;

        abort_unless($vehicle->company_id === $company->id, 404);

        if ($vehicle->status === 'suspended') {
            return to_route('company.vehicles.index')
                ->with('error', 'Suspended vehicles cannot change status.');
        }

        if (! $vehicle->documents()->exists()) {
            return to_route('company.vehicles.index')
                ->with('error', 'No documents uploaded for this vehicle.');
        }

        $hasInvalidDocuments = $vehicle->documents()
            ->whereIn('status', ['pending', 'rejected'])
            ->exists();

        if ($hasInvalidDocuments) {
            return to_route('company.vehicles.index')
                ->with('error', 'You cannot change the vehicle status while documents are pending or rejected.');
        }

        $vehicle->update([
            'status' => $vehicle->status === 'active' ? 'inactive' : 'active',
        ]);

        return to_route('company.vehicles.index')
            ->with('success', 'Vehicle status updated successfully.');
    }

    public function downloadDocument(Request $request, Vehicle $vehicle, VehicleDocument $document): StreamedResponse
    {
        $company = $request->user()->company;

        abort_unless($vehicle->company_id === $company->id, 404);
        abort_unless($document->vehicle_id === $vehicle->id, 404);

        $path = ltrim((string) $document->file_path, '/');

        abort_if($path === '', 404, 'No file path saved for this document.');
        abort_unless($this->publicDisk()->exists($path), 404, 'File not found.');

        return $this->publicDisk()->download(
            $path,
            $document->file_name ?: basename($path)
        );
    }

    public function store(CompanyVehicleRequest $request): RedirectResponse
    {
        $user = $request->user();
        $company = $user->company;
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $company, $user, $request) {
            $vehicle = Vehicle::create([
                'company_id' => $company->id,
                'route_id' => $validated['route_id'],
                'vehicle_type' => $validated['vehicle_type'],
                'plate_number' => $validated['plate_number'],
                'body_number' => $validated['body_number'],
                'capacity' => $validated['capacity'],
                'color' => $validated['color'],
                'engine_number' => $validated['engine_number'],
                'chassis_number' => $validated['chassis_number'],
                'make_model' => $validated['make_model'],
                'status' => 'pending',
                'created_by' => $user->id,
            ]);

            foreach ($request->file('documents', []) as $index => $docInput) {
                $docMeta = $validated['documents'][$index] ?? null;
                $file = $docInput['file'] ?? null;

                if (! $docMeta || ! $file) {
                    continue;
                }

                $documentType = strtoupper($docMeta['document_type']);
                $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');

                $companyCode = $this->sanitizeForFileName(
                    $company->company_code ?: 'COMPANY_' . $company->id
                );

                $plateNumber = $this->sanitizeForFileName(
                    $vehicle->plate_number ?: 'VEHICLE_' . $vehicle->id
                );

                $documentTypeSafe = $this->sanitizeForFileName($documentType);
                $timestamp = now()->format('Ymd_His');

                $fileName = "{$companyCode}_{$plateNumber}_{$documentTypeSafe}_{$timestamp}.{$extension}";

                $directory = "company-documents/{$company->id}/vehicles/{$vehicle->id}/{$documentTypeSafe}";
                $path = $file->storeAs($directory, $fileName, 'public');

                VehicleDocument::create([
                    'vehicle_id' => $vehicle->id,
                    'document_type' => $docMeta['document_type'],
                    'file_path' => $path,
                    'file_name' => $fileName,
                    'file_mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'status' => 'pending',
                    'issued_at' => $docMeta['issued_at'],
                    'expires_at' => $docMeta['expires_at'],
                    'created_by' => $user->id,
                ]);
            }
        });

        return to_route('company.vehicles.index')
            ->with('success', 'Vehicle registered successfully and documents submitted for review.');
    }

    private function sanitizeForFileName(?string $value): string
    {
        $value = strtoupper(trim((string) $value));

        $value = preg_replace('/[^A-Z0-9]+/', '_', $value);
        $value = preg_replace('/_+/', '_', $value);

        return trim($value ?? '', '_') ?: 'FILE';
    }

    private function publicDisk(): FilesystemAdapter
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk;
    }
}
