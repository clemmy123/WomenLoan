@php $user = $user ?? null; $userRoles = $userRoles ?? []; @endphp

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="app-card app-card-padded space-y-4">
        <h3 class="font-bold text-slate-900">{{ __('admin.account_details') }}</h3>
        <div>
            <label class="app-label">{{ __('admin.full_name') }}</label>
            <input type="text" name="name" value="{{ old('name', $user?->name) }}" required class="app-input">
        </div>
        <div>
            <label class="app-label">{{ __('common.email') }}</label>
            <input type="email" name="email" value="{{ old('email', $user?->email) }}" required class="app-input">
        </div>
        <div>
            <label class="app-label">{{ __('common.phone') }}</label>
            <input type="text" name="phone" value="{{ old('phone', $user?->phone) }}" class="app-input">
        </div>
        <div>
            <label class="app-label">{{ __('common.password') }} {{ $user ? __('common.password_keep_blank') : '' }}</label>
            <input type="password" name="password" {{ $user ? '' : 'required' }} class="app-input">
        </div>
        @if($user)
        <div>
            <label class="app-label">{{ __('common.confirm_password') }}</label>
            <input type="password" name="password_confirmation" class="app-input">
        </div>
        @else
        <div>
            <label class="app-label">{{ __('common.confirm_password') }}</label>
            <input type="password" name="password_confirmation" required class="app-input">
        </div>
        @endif
        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user?->is_active ?? true) ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600">
            {{ __('admin.active_account') }}
        </label>
    </div>

    <div class="app-card app-card-padded">
        <h3 class="font-bold text-slate-900 mb-4">{{ __('admin.assign_roles') }}</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-64 overflow-y-auto">
            @foreach($roles as $role)
            @if($role->name === 'super_admin' && !auth()->user()->hasRole('super_admin'))
                @continue
            @endif
            <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-white/5 cursor-pointer">
                <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                    {{ in_array($role->name, old('roles', $userRoles)) ? 'checked' : '' }}
                    class="rounded border-slate-300 text-indigo-600">
                <span class="text-sm">{{ role_label($role->name) }}</span>
            </label>
            @endforeach
        </div>
    </div>
</div>

<div class="app-card app-card-padded mt-6" x-data="{ zoneType: '{{ old('zone_type', $user?->zoneable_type ? class_basename($user?->zoneable_type) : '') }}' }">
    <h3 class="font-bold text-slate-900 mb-4">{{ __('admin.geo_zone') }}</h3>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label class="app-label">{{ __('admin.zone_type') }}</label>
            <select name="zone_type" x-model="zoneType" class="app-select">
                <option value="">{{ __('admin.zone_none') }}</option>
                <option value="region">{{ __('admin.zone_region') }}</option>
                <option value="council">{{ __('admin.zone_council') }}</option>
                <option value="ward">{{ __('admin.zone_ward') }}</option>
            </select>
        </div>
        <div x-show="zoneType === 'region'" class="sm:col-span-2">
            <label class="app-label">{{ __('geo.region') }}</label>
            <select name="zone_id" class="app-select">
                <option value="">{{ __('admin.select_region') }}</option>
                @foreach($regions as $r)
                    <option value="{{ $r->id }}" {{ old('zone_id', $user?->zoneable_type === \App\Models\Region::class ? $user->zoneable_id : '') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                @endforeach
            </select>
        </div>
        <div x-show="zoneType === 'council'" class="sm:col-span-2">
            <label class="app-label">{{ __('geo.council') }}</label>
            <select name="zone_id" class="app-select">
                <option value="">{{ __('admin.select_council') }}</option>
                @foreach($councils as $c)
                    <option value="{{ $c->id }}" {{ old('zone_id', $user?->zoneable_type === \App\Models\Council::class ? $user->zoneable_id : '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div x-show="zoneType === 'ward'" class="sm:col-span-2">
            <label class="app-label">{{ __('geo.ward') }}</label>
            <select name="zone_id" class="app-select">
                <option value="">{{ __('admin.select_ward') }}</option>
                @foreach($wards as $w)
                    <option value="{{ $w->id }}" {{ old('zone_id', $user?->zoneable_type === \App\Models\Ward::class ? $user->zoneable_id : '') == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
