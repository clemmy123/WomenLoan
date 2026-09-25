<style>
@layer bootstrap, properties, theme, base, components, utilities;
</style>
<link rel="stylesheet" href="{{ asset('css/bootstrap-layer.css') }}?v={{ @filemtime(public_path('css/bootstrap-layer.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('css/wdf-mvc.css') }}?v={{ @filemtime(public_path('css/wdf-mvc.css')) ?: time() }}">
