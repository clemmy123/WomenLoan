<div class="space-y-6">
    <p class="text-sm text-slate-500 dark:text-zinc-400">{{ __('admin.permissions_tick_hint') }}</p>

    @foreach($permissionGroups as $groupKey => $group)
    <div class="border border-slate-200 dark:border-white/[0.08] rounded-xl overflow-hidden">
        <div class="flex justify-between items-center px-4 py-3 bg-slate-50 dark:bg-white/[0.04] border-b border-slate-100 dark:border-white/[0.06]">
            <h4 class="text-sm font-bold text-slate-800 dark:text-zinc-200">{{ $group['label'] }}</h4>
            <label class="flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 cursor-pointer">
                <input type="checkbox" class="group-select-all rounded border-slate-300 text-indigo-600"
                    data-group="{{ $groupKey }}"
                    onchange="document.querySelectorAll('.perm-{{ $groupKey }}').forEach(c => c.checked = this.checked)">
                {{ __('admin.select_group') }}
            </label>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 p-4">
            @foreach($group['permissions'] as $permissionName)
            <label class="flex flex-col gap-1 p-3 rounded-xl border border-slate-100 dark:border-white/[0.06] hover:bg-slate-50 dark:hover:bg-white/[0.03] cursor-pointer">
                <span class="flex items-start gap-2">
                    <input type="checkbox" name="permissions[]" value="{{ $permissionName }}"
                        {{ in_array($permissionName, old('permissions', $rolePermissions ?? [])) ? 'checked' : '' }}
                        class="perm-check perm-{{ $groupKey }} mt-0.5 rounded border-slate-300 text-indigo-600">
                    <span class="text-sm text-slate-700 dark:text-zinc-300">{{ permission_label($permissionName) }}</span>
                </span>
                @if(!empty($menuHints[$permissionName]))
                <span class="text-[10px] text-slate-400 dark:text-zinc-500 pl-6">{{ __('admin.unlocks_menu', ['menu' => $menuHints[$permissionName]]) }}</span>
                @endif
            </label>
            @endforeach
        </div>
    </div>
    @endforeach
</div>
