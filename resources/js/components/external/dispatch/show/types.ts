export type DispatchGate = {
    id: number;
    gate_name: string;
    bays?: number | null;
};

export type DispatchRouteStop = {
    id: number;
    route_id: number;
    stop_name: string;
    stop_order: number;
    stop_type: string;
    address?: string | null;
    latitude?: number | null;
    longitude?: number | null;
};

export type DispatchRoute = {
    id: number;
    gate_id?: number | null;
    route_name: string;
    origin_name?: string | null;
    destination_name?: string | null;
    route_geometry?: unknown;
    stops?: DispatchRouteStop[];
    gate?: { id: number; gate_name: string } | null;
};

export type DispatchPerson = {
    id: number;
    name: string;
    username?: string | null;
    email?: string | null;
};

export type DispatchModel = {
    id: number;
    plate_number: string;
    pax_count: number;
    bay_number: string | number | null;
    remarks?: string | null;
    status: string;
    arrived_at_formatted?: string | null;
    departed_at_formatted?: string | null;
    dispatched_at_formatted?: string | null;
    vehicle?: {
        id: number;
        route_id?: number | null;
        plate_number: string;
        body_number?: string | null;
        vehicle_type?: string | null;
        make_model?: string | null;
        status?: string | null;
        route?: {
            id: number;
            route_name: string;
            origin_name?: string | null;
            destination_name?: string | null;
            status?: string | null;
        } | null;
    } | null;
    dispatcher?: DispatchPerson | null;
    driver?: DispatchPerson | null;
    gate?: DispatchGate | null;
};

export type DispatchMapConfig = {
    mapboxToken?: string | null;
    defaultCenter: { lng: number; lat: number };
    defaultZoom: number;
};
