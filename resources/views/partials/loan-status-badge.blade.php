@include('partials.badge', [
    'variant' => loan_status_badge_variant($status),
    'text' => loan_status_label($status),
])
