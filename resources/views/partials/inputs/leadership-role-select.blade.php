@props([
    'name' => 'leadership_role',
    'value' => null,
    'required' => false,
    'showLabel' => true,
])

<div {{ $attributes->merge(['class' => 'wizard-field']) }}>
    @if($showLabel)
        <label class="app-label">{{ __('groups.leadership') }}</label>
    @endif
    <select name="{{ $name }}" class="app-select" @if($required) required @endif>
        <option value="">{{ __('groups.select_leadership') }}</option>
        @foreach(\App\Support\GroupLeadershipRole::options() as $key => $label)
            <option value="{{ $key }}" @selected(old($name, $value) === $key)>{{ $label }}</option>
        @endforeach
    </select>
</div>
