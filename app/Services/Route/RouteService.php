<?php
namespace App\Services\Route;

use App\Models\Route;

class RouteService
{
    public function createRoute(array $data): Route
    {
        $data['created_by'] = auth()->id();

        return Route::create($data);
    }

    public function updateRoute(Route $route, array $data): Route {
        $data['updated_by'] = auth()->id();

        $route->update($data);

        return $route;
    }

    public function deleteRoute(Route $route): void
    {
        $route->delete();
    }

    public function restoreRoute(Route $route): void
    {
        $route->restore();
    }

    public function forceDeleteRoute(Route $route): void
    {
        $route->forceDelete();
    }
}
