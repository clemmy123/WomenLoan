@props([
    'name' => 'marital_status',
    'id' => null,
    'value' => '',
    'required' => false,
    'class' => 'app-select',
])

@php
    use App\Models\Applicant;

    $inputId = $id ?? $name;
    $selected = old($name, $value);
@endphp

<select name="{{ $name }}" id="{{ $inputId }}" @required($required) {{ $attributes->merge(['class' => $class]) }}>
    <option value="">{{ __('applicants.select_marital_status') }}</option>
    @foreach(Applicant::MARITAL_STATUSES as $status)
        <option value="{{ $status }}" @selected($selected === $status)>{{ __('applicants.marital_statuses.'.$status) }}</option>
    @endforeach
</select>
