@props(['loan'])

@php
    $badge = app(\App\Services\CdoLoanScopeService::class)->handlingBadge(auth()->user(), $loan);
@endphp

@if($badge)
    @include('partials.badge', ['variant' => $badge['variant'], 'text' => $badge['label']])
@endif
