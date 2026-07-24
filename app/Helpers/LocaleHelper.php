<?php

if (! function_exists('trans_label')) {
    /**
     * Translate a key, or return a humanized fallback when the key is missing.
     */
    function trans_label(string $key, string $fallback): string
    {
        return \Illuminate\Support\Facades\Lang::has($key) ? __($key) : $fallback;
    }
}

if (! function_exists('loan_status_label')) {
    function loan_status_label(?string $status): string
    {
        if (! $status) {
            return '—';
        }

        return trans_label(
            'statuses.'.$status,
            ucwords(str_replace('_', ' ', $status))
        );
    }
}

if (! function_exists('role_label')) {
    function role_label(?string $role): string
    {
        if (! $role) {
            return '—';
        }

        return trans_label(
            'roles.'.$role,
            ucwords(str_replace('_', ' ', $role))
        );
    }
}

if (! function_exists('workflow_action_label')) {
    function workflow_action_label(?string $action): string
    {
        if (! $action) {
            return '—';
        }

        return trans_label(
            'workflow.actions.'.$action,
            ucwords(str_replace('_', ' ', $action))
        );
    }
}

if (! function_exists('permission_label')) {
    function permission_label(?string $permission): string
    {
        if (! $permission) {
            return '—';
        }

        return trans_label(
            'permissions.'.$permission,
            ucwords($permission)
        );
    }
}

if (! function_exists('loan_type_label')) {
    function loan_type_label(?string $type): string
    {
        if (! $type) {
            return '—';
        }

        return trans_label(
            'loans.types.'.$type,
            ucfirst($type)
        );
    }
}

if (! function_exists('loan_workflow_step_label')) {
    function loan_workflow_step_label(int|string|null $step): string
    {
        $step = (int) $step;

        if ($step < 1) {
            return '—';
        }

        $key = 'loans.workflow_steps.'.$step;

        return \Illuminate\Support\Facades\Lang::has($key)
            ? __($key)
            : __('loans.current_step');
    }
}

if (! function_exists('loan_display_name')) {
    function loan_display_name(\App\Models\Loan $loan): string
    {
        if ($loan->loan_type === 'group') {
            return $loan->group?->name ?? __('common.na');
        }

        return $loan->applicant?->full_name ?? __('common.na');
    }
}

if (! function_exists('validation_attribute_label')) {
    function validation_attribute_label(string $field): string
    {
        $key = 'validation.attributes.'.$field;

        return \Illuminate\Support\Facades\Lang::has($key)
            ? __($key)
            : str_replace('_', ' ', $field);
    }
}

if (! function_exists('can_view_deactivation_reason')) {
    /**
     * Deactivation comments are visible only to users with administration permissions.
     */
    function can_view_deactivation_reason(?\App\Models\User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        $permissions = \App\Support\PermissionCatalog::groups()['administration']['permissions'] ?? [];

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}
