<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait Auditable
{
    use LogsActivity;

    /**
     * Attributes that must never appear in the audit trail.
     *
     * @return list<string>
     */
    protected function auditExcept(): array
    {
        return array_values(array_unique(array_merge(
            [
                'password',
                'remember_token',
            ],
            property_exists($this, 'auditExclude') ? ($this->auditExclude ?? []) : [],
        )));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept($this->auditExcept())
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName($this->auditLogName());
    }

    protected function auditLogName(): string
    {
        return 'audit';
    }
}
