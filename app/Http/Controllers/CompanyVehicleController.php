<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyVehicleRequest;
use App\Http\Requests\UpdateCompanyVehicleRequest;
use App\Models\Gate as GateModel;
use App\Models\Route;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Notifications\Internal\NewVehicleSubmittedNotification;
use App\Notifications\Internal\VehicleResubmittedNotification;
use App\Services\NotificationService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyVehicleController extends Controller
{
    private const DOC_TYPES = [
        'insurance_certificate'       => 'Insurance Certificate',
        'cpc'                         => 'Certificate of Public Convenience (CPC)',
        'official_receipt'            => 'Official Receipt (OR)',
        'certificate_of_registration' => 'Certificate of Registration (CR)',
        'puv_identification_markings' => 'PUV Identification Markings',
    ];

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {
    }

    public function index(Request $request): Response
    {
        Gate::authorize('external_vehicles.viewAny');

        $user    = $request->user();
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
                'remarks',
                'created_at',
            ])
            ->with([
                'route:id,route_name',
                'documents:id,vehicle_id,document_type,status,expires_at',
            ])
            ->search($request->search)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $routes = Route::query()
            ->select('id', 'route_name')
            ->orderBy('route_name')
            ->get();

        return Inertia::render('External/Vehicles/Index', [
            'company' => [
                'id'           => $company->id,
                'company_name' => $company->company_name,
                'company_code' => $company->company_code,
                'status'       => $company->status,
                'logo_url'     => $company->logo
                    ? $this->publicDisk()->url($company->logo)
                    : null,
            ],
            'user' => [
                'id'       => $user->id,
                'name'     => $user->name,
                'username' => $user->username,
                'email'    => $user->email,
            ],
            'vehicles' => $vehicles,
            'filters'  => [
                'search' => $request->search,
            ],
            'routes' => $routes,
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('external_vehicles.create');

        $user    = $request->user();
        $company = $user->company;

        $gates = GateModel::query()
            ->where('status', 'active')
            ->select(['id', 'gate_name', 'bays'])
            ->orderBy('gate_name')
            ->get();

        $routes = Route::query()
            ->where('status', 'active')
            ->where(function ($query) {
                $query
                    ->whereNull('gate_id')
                    ->orWhereHas('gate', fn ($gateQuery) => $gateQuery->where('status', 'active'));
            })
            ->select(['id', 'gate_id', 'route_name', 'origin_name', 'destination_name', 'route_geometry'])
            ->with([
                'gate:id,gate_name',
                'stops:id,route_id,stop_name,stop_order,stop_type,address,latitude,longitude',
            ])
            ->orderBy('route_name')
            ->get();

        return Inertia::render('External/Vehicles/Create', [
            'company' => [
                'id'           => $company->id,
                'company_name' => $company->company_name,
                'company_code' => $company->company_code,
                'status'       => $company->status,
                'logo_url'     => $company->logo
                    ? $this->publicDisk()->url($company->logo)
                    : null,
            ],
            'user' => [
                'id'       => $user->id,
                'name'     => $user->name,
                'username' => $user->username,
                'email'    => $user->email,
            ],
            'gates'     => $gates,
            'routes'    => $routes,
            'docTypes'  => self::DOC_TYPES,
            'mapConfig' => [
                'mapboxToken'   => config('app.mapbox_public_token', env('VITE_MAPBOX_TOKEN')),
                'defaultCenter' => ['lng' => 120.9842, 'lat' => 14.5995],
                'defaultZoom'   => 11,
            ],
        ]);
    }

    public function store(CompanyVehicleRequest $request): RedirectResponse
    {
        Gate::authorize('external_vehicles.create');

        $user      = $request->user();
        $company   = $user->company;
        $validated = $request->validated();

        $vehicle = DB::transaction(function () use ($validated, $company, $user, $request) {
            $vehicle = Vehicle::create([
                'company_id'     => $company->id,
                'route_id'       => $validated['route_id'],
                'vehicle_type'   => $validated['vehicle_type'],
                'plate_number'   => $validated['plate_number'],
                'body_number'    => $validated['body_number'],
                'capacity'       => $validated['capacity'],
                'color'          => $validated['color'],
                'engine_number'  => $validated['engine_number'],
                'chassis_number' => $validated['chassis_number'],
                'make_model'     => $validated['make_model'],
                'status'         => 'pending',
                'created_by'     => $user->id,
            ]);

            foreach ($request->file('documents', []) as $index => $docInput) {
                $docMeta = $validated['documents'][$index] ?? null;
                $file    = $docInput['file'] ?? null;

                if (! $docMeta || ! $file) {
                    continue;
                }

                $documentType     = strtoupper($docMeta['document_type']);
                $extension        = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
                $companyCode      = $this->sanitizeForFileName($company->company_code ?: 'COMPANY_' . $company->id);
                $plateNumber      = $this->sanitizeForFileName($vehicle->plate_number ?: 'VEHICLE_' . $vehicle->id);
                $documentTypeSafe = $this->sanitizeForFileName($documentType);
                $timestamp        = now()->format('Ymd_His');
                $fileName         = "{$companyCode}_{$plateNumber}_{$documentTypeSafe}_{$timestamp}.{$extension}";
                $directory        = "company-documents/{$company->id}/vehicles/{$vehicle->id}/{$documentTypeSafe}";
                $path             = $file->storeAs($directory, $fileName, 'public');

                VehicleDocument::create([
                    'vehicle_id'     => $vehicle->id,
                    'document_type'  => $docMeta['document_type'],
                    'file_path'      => $path,
                    'file_name'      => $fileName,
                    'file_mime_type' => $file->getMimeType(),
                    'file_size'      => $file->getSize(),
                    'status'         => 'pending',
                    'issued_at'      => $this->usesDocumentDates($docMeta['document_type']) ? $docMeta['issued_at'] : null,
                    'expires_at'     => $this->usesDocumentDates($docMeta['document_type']) ? $docMeta['expires_at'] : null,
                    'created_by'     => $user->id,
                ]);
            }

            return $vehicle;
        });

        $this->notificationService->notifyInternalUsers(
            new NewVehicleSubmittedNotification($vehicle->fresh(), $user),
            ['super-admin', 'admin', 'terminal manager']
        );

        return to_route('company.vehicles.index')
            ->with('success', 'Vehicle registered successfully and documents submitted for review.');
    }

    public function show(Request $request, Vehicle $vehicle): Response
    {
        Gate::authorize('external_vehicles.view');

        $user    = $request->user();
        $company = $user->company;

        abort_unless($vehicle->company_id === $company->id, 404);

        $vehicle->load([
            'route:id,gate_id,route_name,origin_name,destination_name,route_geometry',
            'route.gate:id,gate_name',
            'route.stops:id,route_id,stop_name,stop_order,stop_type,address,latitude,longitude',
            'documents:id,vehicle_id,document_type,file_path,file_name,file_mime_type,file_size,status,issued_at,expires_at,created_at',
        ]);

        $gates = GateModel::query()
            ->where('status', 'active')
            ->select(['id', 'gate_name', 'bays'])
            ->orderBy('gate_name')
            ->get();

        $routes = Route::query()
            ->where('status', 'active')
            ->where(function ($query) {
                $query
                    ->whereNull('gate_id')
                    ->orWhereHas('gate', fn ($gateQuery) => $gateQuery->where('status', 'active'));
            })
            ->select(['id', 'gate_id', 'route_name', 'origin_name', 'destination_name', 'route_geometry'])
            ->with([
                'gate:id,gate_name',
                'stops:id,route_id,stop_name,stop_order,stop_type,address,latitude,longitude',
            ])
            ->orderBy('route_name')
            ->get();

        return Inertia::render('External/Vehicles/Show', [
            'company' => [
                'id'           => $company->id,
                'company_name' => $company->company_name,
                'company_code' => $company->company_code,
                'status'       => $company->status,
                'logo_url'     => $company->logo
                    ? $this->publicDisk()->url($company->logo)
                    : null,
            ],
            'user' => [
                'id'       => $user->id,
                'name'     => $user->name,
                'username' => $user->username,
                'email'    => $user->email,
            ],
            'vehicle' => [
                'id'             => $vehicle->id,
                'route_id'       => $vehicle->route_id,
                'vehicle_type'   => $vehicle->vehicle_type,
                'plate_number'   => $vehicle->plate_number,
                'body_number'    => $vehicle->body_number,
                'capacity'       => $vehicle->capacity,
                'color'          => $vehicle->color,
                'engine_number'  => $vehicle->engine_number,
                'chassis_number' => $vehicle->chassis_number,
                'make_model'     => $vehicle->make_model,
                'status'         => $vehicle->status,
                'created_at'     => optional($vehicle->created_at)?->toDateTimeString(),
                'route'          => $vehicle->route ? [
                    'id'               => $vehicle->route->id,
                    'gate_id'          => $vehicle->route->gate_id,
                    'route_name'       => $vehicle->route->route_name,
                    'origin_name'      => $vehicle->route->origin_name,
                    'destination_name' => $vehicle->route->destination_name,
                    'route_geometry'   => $vehicle->route->route_geometry,
                    'gate'             => $vehicle->route->gate ? [
                        'id'        => $vehicle->route->gate->id,
                        'gate_name' => $vehicle->route->gate->gate_name,
                    ] : null,
                    'stops' => $vehicle->route->stops->map(fn ($stop) => [
                        'id'         => $stop->id,
                        'route_id'   => $stop->route_id,
                        'stop_name'  => $stop->stop_name,
                        'stop_order' => $stop->stop_order,
                        'stop_type'  => $stop->stop_type,
                        'address'    => $stop->address,
                        'latitude'   => $stop->latitude,
                        'longitude'  => $stop->longitude,
                    ])->values(),
                ] : null,
                'documents' => $vehicle->documents->map(function ($document) use ($vehicle) {
                    return [
                        'id'             => $document->id,
                        'document_type'  => $document->document_type,
                        'file_name'      => $document->file_name,
                        'file_url'       => $document->file_path
                            ? $this->publicDisk()->url($document->file_path)
                            : null,
                        'file_mime_type' => $document->file_mime_type,
                        'file_size'      => $document->file_size,
                        'status'         => $document->status,
                        'issued_at'      => optional($document->issued_at)?->format('Y-m-d'),
                        'expires_at'     => optional($document->expires_at)?->format('Y-m-d'),
                        'created_at'     => optional($document->created_at)?->toDateTimeString(),
                        'download_url'   => route('company.vehicles.documents.download', [
                            'vehicle'  => $vehicle->id,
                            'document' => $document->id,
                        ]),
                    ];
                })->values(),
            ],
            'gates'     => $gates,
            'routes'    => $routes,
            'docTypes'  => self::DOC_TYPES,
            'mapConfig' => [
                'mapboxToken'   => config('app.mapbox_public_token', env('VITE_MAPBOX_TOKEN')),
                'defaultCenter' => ['lng' => 120.9842, 'lat' => 14.5995],
                'defaultZoom'   => 11,
            ],
        ]);
    }

    public function edit(Request $request, Vehicle $vehicle): Response
    {
        Gate::authorize('external_vehicles.update');

        $user    = $request->user();
        $company = $user->company;

        abort_unless($vehicle->company_id === $company->id, 404);
        abort_if($vehicle->status === 'suspended', 403, 'Suspended vehicles cannot be edited.');

        $vehicle->load([
            'documents:id,vehicle_id,document_type,file_name,status,issued_at,expires_at,created_at',
        ]);

        $gates = GateModel::query()
            ->where('status', 'active')
            ->select(['id', 'gate_name', 'bays'])
            ->orderBy('gate_name')
            ->get();

        $routes = Route::query()
            ->where('status', 'active')
            ->where(function ($query) {
                $query
                    ->whereNull('gate_id')
                    ->orWhereHas('gate', fn ($gateQuery) => $gateQuery->where('status', 'active'));
            })
            ->select(['id', 'gate_id', 'route_name', 'origin_name', 'destination_name', 'route_geometry'])
            ->with([
                'gate:id,gate_name',
                'stops:id,route_id,stop_name,stop_order,stop_type,address,latitude,longitude',
            ])
            ->orderBy('route_name')
            ->get();

        return Inertia::render('External/Vehicles/Edit', [
            'company' => [
                'id'           => $company->id,
                'company_name' => $company->company_name,
                'company_code' => $company->company_code,
                'status'       => $company->status,
                'logo_url'     => $company->logo
                    ? $this->publicDisk()->url($company->logo)
                    : null,
            ],
            'user' => [
                'id'       => $user->id,
                'name'     => $user->name,
                'username' => $user->username,
                'email'    => $user->email,
            ],
            'vehicle' => [
                'id'             => $vehicle->id,
                'route_id'       => $vehicle->route_id,
                'vehicle_type'   => $vehicle->vehicle_type,
                'plate_number'   => $vehicle->plate_number,
                'body_number'    => $vehicle->body_number,
                'capacity'       => $vehicle->capacity,
                'color'          => $vehicle->color,
                'engine_number'  => $vehicle->engine_number,
                'chassis_number' => $vehicle->chassis_number,
                'make_model'     => $vehicle->make_model,
                'status'         => $vehicle->status,
                'documents'      => $vehicle->documents->map(fn ($document) => [
                    'id'            => $document->id,
                    'document_type' => $document->document_type,
                    'file_name'     => $document->file_name,
                    'status'        => $document->status,
                    'issued_at'     => optional($document->issued_at)?->format('Y-m-d'),
                    'expires_at'    => optional($document->expires_at)?->format('Y-m-d'),
                    'created_at'    => optional($document->created_at)?->toDateTimeString(),
                ])->values(),
            ],
            'gates'     => $gates,
            'routes'    => $routes,
            'docTypes'  => self::DOC_TYPES,
            'mapConfig' => [
                'mapboxToken'   => config('app.mapbox_public_token', env('VITE_MAPBOX_TOKEN')),
                'defaultCenter' => ['lng' => 120.9842, 'lat' => 14.5995],
                'defaultZoom'   => 11,
            ],
        ]);
    }

    public function update(UpdateCompanyVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        Gate::authorize('external_vehicles.update');

        $company   = $request->user()->company;
        $user      = $request->user();
        $validated = $request->validated();

        abort_unless($vehicle->company_id === $company->id, 404);
        abort_if($vehicle->status === 'suspended', 403, 'Suspended vehicles cannot be updated.');

        DB::transaction(function () use ($request, $validated, $vehicle, $company, $user) {
            $vehicle->update([
                'route_id'       => $validated['route_id'],
                'vehicle_type'   => $validated['vehicle_type'],
                'plate_number'   => strtoupper(trim((string) $validated['plate_number'])),
                'body_number'    => $validated['body_number'],
                'capacity'       => $validated['capacity'],
                'color'          => $validated['color'],
                'engine_number'  => $validated['engine_number'],
                'chassis_number' => $validated['chassis_number'],
                'make_model'     => $validated['make_model'],
                'status'         => 'pending',
                'updated_by'     => $user->id,
            ]);

            $documentsByType = $vehicle->documents()->get()->keyBy('document_type');

            foreach ($validated['documents'] ?? [] as $index => $docMeta) {
                $file = data_get($request->file('documents'), "{$index}.file");

                if (! $file) {
                    continue;
                }

                $documentType     = $docMeta['document_type'] ?? null;
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

                $extension        = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
                $companyCode      = $this->sanitizeForFileName($company->company_code ?: 'COMPANY_' . $company->id);
                $plateNumber      = $this->sanitizeForFileName($vehicle->plate_number ?: 'VEHICLE_' . $vehicle->id);
                $documentTypeSafe = $this->sanitizeForFileName(strtoupper((string) $documentType));
                $timestamp        = now()->format('Ymd_His');
                $fileName         = "{$companyCode}_{$plateNumber}_{$documentTypeSafe}_{$timestamp}.{$extension}";
                $directory        = "company-documents/{$company->id}/vehicles/{$vehicle->id}/{$documentTypeSafe}";
                $newPath          = $file->storeAs($directory, $fileName, 'public');
                $oldPath          = ltrim((string) $existingDocument->file_path, '/');

                $existingDocument->update([
                    'file_path'      => $newPath,
                    'file_name'      => $fileName,
                    'file_mime_type' => $file->getMimeType(),
                    'file_size'      => $file->getSize(),
                    'status'         => 'pending',
                    'issued_at'      => $this->usesDocumentDates((string) $documentType) ? $docMeta['issued_at'] : null,
                    'expires_at'     => $this->usesDocumentDates((string) $documentType) ? $docMeta['expires_at'] : null,
                    'updated_by'     => $user->id,
                ]);

                if ($oldPath !== '' && $oldPath !== $newPath && $this->publicDisk()->exists($oldPath)) {
                    $this->publicDisk()->delete($oldPath);
                }
            }
        });

        $this->notificationService->notifyInternalUsers(
            new VehicleResubmittedNotification($vehicle->fresh(), $user),
            ['super-admin', 'admin', 'terminal manager']
        );

        return to_route('company.vehicles.show', $vehicle)
            ->with('success', 'Vehicle updated successfully.');
    }

    public function toggleStatus(Request $request, Vehicle $vehicle): RedirectResponse
    {
        Gate::authorize('external_vehicles.toggleStatus');

        $company = $request->user()->company;

        abort_unless($vehicle->company_id === $company->id, 404);

        if ($vehicle->status === 'suspended') {
            return to_route('company.vehicles.index')
                ->with('error', 'Suspended vehicles cannot change status.');
        }

        $hasDocuments = $vehicle->documents()->exists();
        $hasPendingOrRejected = $vehicle->documents()
            ->whereIn('status', ['pending', 'rejected', 'invalid'])
            ->exists();

        $hasExpired = $vehicle->documents()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now()->toDateString())
            ->exists();

        if ($vehicle->status === 'active') {
            $validated = $request->validate([
                'remarks' => ['required', 'string', 'max:1000'],
            ]);

            $vehicle->update([
                'status'  => 'inactive',
                'remarks' => $validated['remarks'],
                'updated_by' => $request->user()->id,
            ]);

            return to_route('company.vehicles.index')
                ->with('success', 'Vehicle set to inactive.');
        }

        if ($vehicle->status === 'inactive') {
            if (! $hasDocuments) {
                return to_route('company.vehicles.index')
                    ->with('error', 'Upload the required documents before activating this vehicle.');
            }

            if ($hasPendingOrRejected) {
                return to_route('company.vehicles.index')
                    ->with('error', 'All documents must be approved before activation.');
            }

            if ($hasExpired) {
                return to_route('company.vehicles.index')
                    ->with('error', 'Renew expired documents before activation.');
            }

            $vehicle->update([
                'status'  => 'active',
                'remarks' => null,
                'updated_by' => $request->user()->id,
            ]);

            return to_route('company.vehicles.index')
                ->with('success', 'Vehicle activated successfully.');
        }

        return to_route('company.vehicles.index')
            ->with('error', 'Status change not allowed for this vehicle.');
    }

    public function downloadDocument(Request $request, Vehicle $vehicle, VehicleDocument $document): StreamedResponse
    {
        Gate::authorize('external_vehicle_documents.download');

        $company = $request->user()->company;

        abort_unless($vehicle->company_id === $company->id, 404);
        abort_unless($document->vehicle_id === $vehicle->id, 404);

        $path = ltrim((string) $document->file_path, '/');

        abort_if($path === '', 404, 'No file path saved for this document.');
        abort_unless($this->publicDisk()->exists($path), 404, 'File not found.');

        return $this->publicDisk()->download(
            $path,
            $document->file_name ?: basename($path),
        );
    }

    private function sanitizeForFileName(?string $value): string
    {
        $value = strtoupper(trim((string) $value));
        $value = preg_replace('/[^A-Z0-9]+/', '_', $value);
        $value = preg_replace('/_+/', '_', $value);

        return trim($value ?? '', '_') ?: 'FILE';
    }

    private function usesDocumentDates(string $documentType): bool
    {
        return $documentType !== 'puv_identification_markings';
    }

    private function publicDisk(): FilesystemAdapter
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk;
    }
}
