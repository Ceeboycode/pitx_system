<?php

namespace App\Http\Requests\Route;

use Illuminate\Foundation\Http\FormRequest;

class RouteStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'route_name'       => ['required', 'string', 'max:255'],
            'gate_id'          => ['required', 'exists:gates,id'],

            'origin_name'      => ['required', 'string', 'max:255'],
            'origin_lat'       => ['required', 'numeric'],
            'origin_lng'       => ['required', 'numeric'],

            'destination_name' => ['required', 'string', 'max:255'],
            'destination_lat'  => ['required', 'numeric'],
            'destination_lng'  => ['required', 'numeric'],

            'distance_meters'  => ['nullable', 'integer', 'min:0'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'route_geometry'   => ['nullable', 'string'],

            'stops'                     => ['required', 'array', 'min:2'],
            'stops.*.stop_name'         => ['required', 'string', 'max:255'],
            'stops.*.stop_type'         => ['required', 'string', 'in:origin,stop,destination,landmark'],
            'stops.*.address'           => ['nullable', 'string', 'max:500'],
            'stops.*.latitude'          => ['required', 'numeric'],
            'stops.*.longitude'         => ['required', 'numeric'],
            'stops.*.mapbox_feature_id' => ['nullable', 'string', 'max:255'],
            'stops.*.stop_order'        => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'stops.required' => 'At least an origin and destination stop are required.',
            'stops.min'      => 'At least an origin and destination stop are required.',
        ];
    }
}
