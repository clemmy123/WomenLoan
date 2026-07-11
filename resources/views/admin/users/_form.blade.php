@php
    use App\Models\Concerns\HasDisplayName;

    $user = $user ?? null;
    $userRoles = $userRoles ?? [];
    $nameParts = HasDisplayName::splitFullName((string) old('name', $user?->name ?? ''));
    $firstName = old('first_name', $user?->first_name ?? $nameParts['first_name']);
    $middleName = old('middle_name', $user?->middle_name ?? $nameParts['middle_name']);
    $lastName = old('last_name', $user?->last_name ?? $nameParts['last_name']);
@endphp

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="app-card app-card-padded space-y-4">
        <h3 class="font-bold text-slate-900">{{ __('admin.account_details') }}</h3>

        <div>
            <label class="app-label" for="check_number">{{ __('admin.check_number') }} @include('partials.required-mark')</label>
            <input
                type="text"
                name="check_number"
                id="check_number"
                value="{{ old('check_number', $user?->check_number) }}"
                required
                inputmode="numeric"
                pattern="[0-9]{1,10}"
                maxlength="10"
                class="app-input"
                placeholder="{{ __('admin.check_number_placeholder') }}"
                oninput="this.value = this.value.replace(/\D/g, '').slice(0, 10)"
            >
            @error('check_number') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="app-label" for="first_name">{{ __('applicants.first_name') }} @include('partials.required-mark')</label>
                <input type="text" name="first_name" id="first_name" value="{{ $firstName }}" required class="app-input">
                @error('first_name') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="app-label" for="middle_name">{{ __('applicants.middle_name') }}</label>
                <input type="text" name="middle_name" id="middle_name" value="{{ $middleName }}" class="app-input">
                @error('middle_name') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="app-label" for="last_name">{{ __('applicants.last_name') }} @include('partials.required-mark')</label>
                <input type="text" name="last_name" id="last_name" value="{{ $lastName }}" required class="app-input">
                @error('last_name') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="app-label" for="email">{{ __('common.email') }} @include('partials.required-mark')</label>
            <input type="email" name="email" id="email" value="{{ old('email', $user?->email) }}" required class="app-input">
            @error('email') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="app-label" for="phone_local">{{ __('common.phone') }} @include('partials.required-mark')</label>
            @include('partials.inputs.phone-input', [
                'name' => 'phone',
                'id' => 'phone_local',
                'value' => old('phone', $user?->phone),
                'required' => true,
            ])
            @error('phone') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        @if (! $user || auth()->user()->can('reset user password'))
            <div>
                <label class="app-label" for="admin_password">{{ __('common.password') }} {{ $user ? __('common.password_keep_blank') : '' }} @unless($user) @include('partials.required-mark') @endunless</label>
                <input type="password" name="password" id="admin_password" {{ $user ? '' : 'required' }} class="app-input" autocomplete="new-password">
                @include('partials.password-requirements', ['targetId' => 'admin_password', 'variant' => 'app'])
                <p class="mt-1.5 text-xs text-slate-500">{{ __('admin.temporary_password_hint', ['minutes' => (int) config('wdf.temporary_password_minutes', 2)]) }}</p>
                @error('password') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="app-label" for="password_confirmation">{{ __('common.confirm_password') }} @unless($user) @include('partials.required-mark') @endunless</label>
                <input type="password" name="password_confirmation" id="password_confirmation" {{ $user ? '' : 'required' }} class="app-input" autocomplete="new-password">
            </div>
        @elseif ($user)
            <p class="text-sm text-slate-500">{{ __('admin.reset_password_permission_required') }}</p>
        @endif

        @php
            $canActivate = auth()->user()->can('activate users');
            $canDeactivate = auth()->user()->can('deactivate users');
            $isActiveChecked = (bool) old('is_active', $user?->is_active ?? true);
            $canToggleStatus = ($canActivate && $canDeactivate)
                || ($canActivate && ! $isActiveChecked)
                || ($canDeactivate && $isActiveChecked)
                || (! $user && $canActivate);
        @endphp

        @if ($canToggleStatus)
            <input type="hidden" name="is_active" value="0">
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    {{ $isActiveChecked || (! $user && $canActivate && ! $canDeactivate) ? 'checked' : '' }}
                    class="rounded border-slate-300 text-indigo-600"
                >
                <span>
                    {{ __('admin.active_account') }}
                    <span class="block text-xs text-slate-500">{{ __('admin.active_account_hint') }}</span>
                </span>
            </label>
            @error('is_active') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        @elseif ($user)
            <p class="text-sm text-slate-500">
                {{ $user->is_active ? __('admin.account_status_active_readonly') : __('admin.account_status_inactive_readonly') }}
            </p>
        @elseif ($canActivate || $canDeactivate)
            {{-- Create: only deactivate permission — start inactive --}}
            <input type="hidden" name="is_active" value="0">
            <p class="text-sm text-slate-500">{{ __('admin.account_status_inactive_readonly') }}</p>
        @endif

        @if($user?->login_locked_permanently || $user?->login_locked_until)
            <div class="rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-800 p-3 space-y-2">
                <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                    @if($user->login_locked_permanently)
                        {{ __('admin.login_locked_permanently_notice') }}
                    @else
                        {{ __('admin.login_locked_temporarily_notice') }}
                    @endif
                </p>
                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-zinc-200">
                    <input type="checkbox" name="unlock_login" value="1" {{ old('unlock_login') ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600">
                    {{ __('admin.unlock_login') }}
                </label>
            </div>
        @endif
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
