<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}" defer></script>
@unless($skipAppJs ?? false)
<script type="module" src="{{ asset('js/app.js') }}?v={{ @filemtime(public_path('js/app.js')) ?: time() }}"></script>
@endunless
