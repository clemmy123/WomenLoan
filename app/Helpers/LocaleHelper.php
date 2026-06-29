<?php

if (! function_exists('loan_status_label')) {
    function loan_status_label(?string $status): string
    {
        if (! $status) {
            return '—';
        }

        return __('statuses.' . $status, [], ucwords(str_replace('_', ' ', $status)));
    }
}

if (! function_exists('role_label')) {
    function role_label(?string $role): string
    {
        if (! $role) {
            return '—';
        }

        return __('roles.' . $role, [], ucwords(str_replace('_', ' ', $role)));
    }
}

if (! function_exists('workflow_action_label')) {
    function workflow_action_label(?string $action): string
    {
        if (! $action) {
            return '—';
        }

        return __('workflow.actions.' . $action, [], ucwords(str_replace('_', ' ', $action)));
    }
}

if (! function_exists('permission_label')) {
    function permission_label(?string $permission): string
    {
        if (! $permission) {
            return '—';
        }

        return __('permissions.' . $permission, [], ucwords($permission));
    }
}

if (! function_exists('loan_type_label')) {
    function loan_type_label(?string $type): string
    {
        if (! $type) {
            return '—';
        }

        return __('loans.types.' . $type, [], ucfirst($type));
    }
}
