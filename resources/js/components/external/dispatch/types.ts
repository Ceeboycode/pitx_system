export type DispatchVehicleOption = {
    id: number;
    plate_number: string;
    body_number?: string | null;
    vehicle_type?: string | null;
    make_model?: string | null;
    status?: string | null;
    route?: {
        id: number;
        gate_id?: number | null;
        route_name?: string | null;
        origin_name?: string | null;
        destination_name?: string | null;
        status?: string | null;
        gate?: {
            id: number;
            gate_name?: string | null;
            status?: string | null;
        } | null;
    } | null;
    label: string;
};

export type DispatchDriverOption = {
    id: number;
    name: string;
    username?: string | null;
    email?: string | null;
    label: string;
};

export type DispatchGateOption = {
    id: number;
    gate_name: string;
    bays: number;
    status?: string | null;
    label: string;
    bay_options: Array<{ value: number; label: string }>;
};

export type DispatchItem = {
    id: number;
    plate_number: string;
    pax_count: number;
    bay_number: string | number;
    remarks?: string | null;
    status: string;
    arrived_at_formatted?: string | null;
    departed_at_formatted?: string | null;
    vehicle?: {
        id: number;
        plate_number: string;
        body_number?: string | null;
        vehicle_type?: string | null;
        make_model?: string | null;
    } | null;
    dispatcher?: { id: number; name: string; username?: string | null } | null;
    driver?: { id: number; name: string; username?: string | null } | null;
    gate?: { id: number; gate_name: string } | null;
};
