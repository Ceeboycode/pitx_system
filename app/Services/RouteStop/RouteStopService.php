<?php
namespace App\Services\RouteStop;

use App\Models\RouteStop;



class RouteStopService
{
    public function createRouteStop(array $data): RouteStop
    {
        $data['created_by'] = auth()->id();

        return RouteStop::create($data);
    }

    public function updateRouteStop(RouteStop $routeStop, array $data): RouteStop
    {
        $data['updated_by'] = auth()->id();

        $routeStop->update($data);

        return $routeStop;
    }

    public function deleteRouteStop(RouteStop $routeStop): void
    {
        $routeStop->delete();
    }

    public function restoreRouteStop(RouteStop $routeStop): void
    {
        $routeStop->restore();
    }

    public function forceDeleteRouteStop(RouteStop $routeStop): void
    {
        $routeStop->forceDelete();
    }
}
