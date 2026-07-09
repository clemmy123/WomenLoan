@extends('layouts.app')

@section('title', __('analytical_reports.title'))

@section('content')
<div class="page">
    <div class="page-header">
        <div>
            <h1 class="page-title lg:text-3xl">{{ __('analytical_reports.title') }}</h1>
            <p class="page-subtitle">{{ __('analytical_reports.subtitle') }}</p>
        </div>
    </div>
    <script>window.location.replace(@json(route('reports.analytical.overview')));</script>
</div>
@endsection
