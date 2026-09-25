@extends('layouts.app')

@section('title', __('analytical_reports.title'))

@section('content')
<div class="app-page">
    <div class="app-page-header">
        <div>
            <h1 class="app-page-title lg:text-3xl">{{ __('analytical_reports.title') }}</h1>
            <p class="app-page-subtitle">{{ __('analytical_reports.subtitle') }}</p>
        </div>
    </div>
    <script>window.location.replace(@json(route('reports.analytical.overview')));</script>
</div>
@endsection
