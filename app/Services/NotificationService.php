<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Gate;
use App\Models\Route;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class NotificationService
{
    public function internalUsers(array $roles = ['super-admin', 'admin', 'terminal manager']): Collection
    {
        return User::query()
            ->where('status', 'active')
            ->get()
            ->filter(fn (User $user) => $user->hasAnyRole($roles))
            ->values();
    }

    public function companyUsers(Company $company): Collection
    {
        return User::query()
            ->where('company_id', $company->id)
            ->where('status', 'active')
            ->get();
    }

    public function notifyInternalUsers(
        object $notification,
        array $roles = ['super-admin', 'admin', 'terminal manager']
    ): void {
        $this->internalUsers($roles)->each(
            fn (User $user) => $user->notify($notification)
        );
    }

    public function notifyCompanyUsers(Company $company, object $notification): void
    {
        $this->companyUsers($company)->each(
            fn (User $user) => $user->notify($notification)
        );
    }

    public function notifyCompanyEmail(Company $company, object $notification): void
    {
        if (blank($company->company_email)) {
            return;
        }

        try {
            Notification::route('mail', $company->company_email)
                ->notify($notification);
        } catch (Throwable $e) {
            Log::warning('Company email notification failed.', [
                'company_id' => $company->id,
                'company_email' => $company->company_email,
                'notification' => get_class($notification),
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function affectedCompaniesByGate(Gate $gate): Collection
    {
        return Company::query()
            ->whereHas('vehicles.route', fn ($query) => $query->where('gate_id', $gate->id))
            ->get()
            ->unique('id')
            ->values();
    }

    public function affectedCompaniesByRoute(Route $route): Collection
    {
        return Company::query()
            ->whereHas('vehicles', fn ($query) => $query->where('route_id', $route->id))
            ->get()
            ->unique('id')
            ->values();
    }

    public function notifyAffectedCompaniesByGate(Gate $gate, object $notification): void
    {
        $this->affectedCompaniesByGate($gate)->each(function (Company $company) use ($notification) {
            $this->notifyCompanyUsers($company, $notification);
            $this->notifyCompanyEmail($company, $notification);
        });
    }

    public function notifyAffectedCompaniesByRoute(Route $route, object $notification): void
    {
        $this->affectedCompaniesByRoute($route)->each(function (Company $company) use ($notification) {
            $this->notifyCompanyUsers($company, $notification);
            $this->notifyCompanyEmail($company, $notification);
        });
    }
}
