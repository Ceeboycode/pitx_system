import type { InertiaForm } from '@inertiajs/vue3';

export type GateItem = {
    id: number;
    gate_name: string;
    bays?: number | null;
};

export type RouteStop = {
    id: number;
    route_id: number;
    stop_name: string;
    stop_order: number;
    stop_type: string;
    address?: string | null;
    latitude?: number | null;
    longitude?: number | null;
};

export type RouteItem = {
    id: number;
    gate_id?: number | null;
    route_name: string;
    origin_name?: string | null;
    destination_name?: string | null;
    route_geometry?: unknown;
    stops?: RouteStop[];
    gate?: {
        id: number;
        gate_name: string;
    } | null;
};

export type VehicleDocument = {
    id: number;
    document_type: string;
    file_name?: string | null;
    file_url?: string | null;
    file_mime_type?: string | null;
    file_size?: number | null;
    status: string;
    issued_at?: string | null;
    expires_at?: string | null;
    created_at?: string | null;
    download_url?: string | null;
};

export type VehicleModel = {
    id: number;
    route_id: number | string | null;
    vehicle_type_id: number | null;
    vehicle_type?: string | null;
    plate_number: string;
    body_number: string;
    capacity: string | number;
    color: string;
    engine_number: string;
    chassis_number: string;
    make_model: string;
    status: string;
    verification_status?: string | null;
    verification_remark?: string | null;
    operator_remark?: string | null;
    suspension_remark?: string | null;
    created_at?: string | null;
    updated_at?: string | null;
    route?: RouteItem | null;
    documents: VehicleDocument[];
};

export type DispatchRow = {
    id: number;
    gate_name?: string | null;
    status: string;
    pax_count?: number | null;
    bay_number?: string | number | null;
    arrived_at?: string | null;
    departed_at?: string | null;
    remarks?: string | null;
};

export type HistoryChange = {
    field: string;
    old: string | null;
    new: string | null;
};

export type HistoryEntry = {
    id: number;
    action: 'created' | 'updated' | 'deleted' | string;
    subject: 'vehicle' | 'document';
    document_label?: string | null;
    actor: string;
    created_at?: string | null;
    changes: HistoryChange[];
};

export type MapConfig = {
    mapboxToken?: string | null;
    defaultCenter: {
        lng: number;
        lat: number;
    };
    defaultZoom: number;
};

export type FormDocument = {
    id: number | null;
    document_type: string;
    status: string;
    existing_file_name: string | null;
    file: File | null;
    issued_at: string;
    expires_at: string;
};

export type VehicleFormData = {
    vehicle_type_id: number | null;
    plate_number: string;
    body_number: string;
    capacity: string | number;
    color: string;
    engine_number: string;
    chassis_number: string;
    make_model: string;
    route_id: string;
    documents: FormDocument[];
};

export type VehicleForm = InertiaForm<VehicleFormData>;

/** The starting values of the vehicle form: the saved vehicle, with one entry per required document. */
export function buildFormValues(vehicle: VehicleModel, docTypes: Record<string, string>): VehicleFormData {
    return {
        vehicle_type_id: vehicle.vehicle_type_id,
        plate_number: vehicle.plate_number ?? '',
        body_number: vehicle.body_number ?? '',
        capacity: vehicle.capacity ?? '',
        color: vehicle.color ?? '',
        engine_number: vehicle.engine_number ?? '',
        chassis_number: vehicle.chassis_number ?? '',
        make_model: vehicle.make_model ?? '',
        route_id: vehicle.route_id ? String(vehicle.route_id) : '',
        documents: Object.keys(docTypes).map((docType) => {
            const existing = vehicle.documents.find((doc) => doc.document_type === docType);

            return {
                id: existing?.id ?? null,
                document_type: docType,
                status: existing?.status ?? 'pending',
                existing_file_name: existing?.file_name ?? null,
                file: null,
                issued_at: existing?.issued_at ?? '',
                expires_at: existing?.expires_at ?? '',
            };
        }),
    };
}
