<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class AuditLogService
{
    public function normalizeFilters(array $input): array
    {
        $dateFrom = filled($input['date_from'] ?? null) ? (string) $input['date_from'] : null;
        $dateTo = filled($input['date_to'] ?? null) ? (string) $input['date_to'] : null;

        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $event = trim((string) ($input['event'] ?? ''));
        $allowedEvents = ['created', 'updated', 'deleted', 'restored', 'login', 'logout'];
        if (! in_array($event, $allowedEvents, true)) {
            $event = '';
        }

        return [
            'search' => trim((string) ($input['search'] ?? '')),
            'event' => $event,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->query($filters)
            ->with(['causer', 'subject'])
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function exportRows(array $filters): Collection
    {
        return $this->query($filters)
            ->with(['causer', 'subject'])
            ->latest('id')
            ->get()
            ->map(fn (Activity $activity) => [
                'when' => $activity->created_at
                    ? format_app_datetime($activity->created_at, withSeconds: true)
                    : '—',
                'who' => $this->causerLabel($activity),
                'event' => $this->eventLabel($activity->event),
                'what' => $this->subjectLabel($activity),
                'description' => $activity->description,
            ]);
    }

    public function find(int $id): Activity
    {
        return Activity::query()
            ->with(['causer', 'subject'])
            ->findOrFail($id);
    }

    /**
     * @return Builder<Activity>
     */
    protected function query(array $filters): Builder
    {
        $query = Activity::query();

        if (! empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('subject_type', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%")
                    ->orWhereHas('causer', function (Builder $causer) use ($search) {
                        $causer->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    public function subjectLabel(?Activity $activity): string
    {
        if (! $activity?->subject_type) {
            return __('audit.system');
        }

        $type = class_basename($activity->subject_type);
        $id = $activity->subject_id;

        $subject = $activity->subject;
        if ($subject) {
            $name = $subject->name
                ?? $subject->full_name
                ?? $subject->loan_track_id
                ?? null;

            if ($name) {
                return "{$type} · {$name}";
            }
        }

        return $id ? "{$type} #{$id}" : $type;
    }

    public function causerLabel(?Activity $activity): string
    {
        $causer = $activity?->causer;

        if (! $causer) {
            return __('audit.system');
        }

        $name = $causer->name ?: trim(($causer->first_name ?? '').' '.($causer->last_name ?? ''));

        if ($name !== '' && ! empty($causer->email)) {
            return "{$name} ({$causer->email})";
        }

        return $name !== '' ? $name : ($causer->email ?? __('audit.system'));
    }

    public function eventLabel(?string $event): string
    {
        return match ($event) {
            'created' => __('audit.events.created'),
            'updated' => __('audit.events.updated'),
            'deleted' => __('audit.events.deleted'),
            'restored' => __('audit.events.restored'),
            'login' => __('audit.events.login'),
            'logout' => __('audit.events.logout'),
            default => $event ? ucfirst($event) : __('common.na'),
        };
    }
}
