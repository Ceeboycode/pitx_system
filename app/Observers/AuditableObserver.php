<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

class AuditableObserver
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    public function created(Model $model): void
    {
        if ($model instanceof AuditLog) {
            return;
        }

        $this->auditLogger->logModelEvent($model, 'created');
    }

    public function updated(Model $model): void
    {
        if ($model instanceof AuditLog) {
            return;
        }

        $this->auditLogger->logModelEvent($model, 'updated');
    }

    public function deleted(Model $model): void
    {
        if ($model instanceof AuditLog) {
            return;
        }

        $this->auditLogger->logModelEvent($model, 'deleted');
    }
}
