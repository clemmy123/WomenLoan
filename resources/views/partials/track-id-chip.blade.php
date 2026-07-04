@props(['trackId'])

<button
    type="button"
    class="track-id-chip"
    data-copy-track-id="{{ $trackId }}"
    data-copied-label="{{ __('common.copied') }}"
    title="{{ __('common.copy_track_id') }}"
    aria-label="{{ __('common.copy_track_id') }}"
>
    <span class="track-id-chip-value">{{ $trackId }}</span>
    <span class="track-id-chip-copy" aria-hidden="true">
        @include('partials.icons.copy')
    </span>
</button>
