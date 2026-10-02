<?php

namespace App\Http\Controllers\Platform;

use App\Domain\Module\Models\Module;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PlatformGlobalModuleController
{
    public function index(): View
    {
        return view('admin.global-modules.index', [
            'modules' => Module::query()
                ->withCount(['tenants as enabled_tenants_count' => fn ($query) => $query->wherePivot('enabled', true)])
                ->orderByDesc('is_core')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function update(Request $request, Module $module, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'name_en' => ['required', 'string', 'max:120'],
            'name_ar' => ['required', 'string', 'max:120'],
            'description_en' => ['nullable', 'string', 'max:1000'],
            'description_ar' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($module->is_core && ! $request->boolean('is_active')) {
            return back()->withErrors([
                'is_active' => __('Core modules cannot be disabled globally.'),
            ]);
        }

        $nextActive = $request->boolean('is_active');

        $module->forceFill([
            'name' => [
                'en' => $validated['name_en'],
                'ar' => $validated['name_ar'],
            ],
            'description' => [
                'en' => $validated['description_en'] ?? '',
                'ar' => $validated['description_ar'] ?? '',
            ],
            'is_active' => $nextActive,
        ])->save();

        $audit->log(
            $nextActive ? 'platform.module_activated' : 'platform.module_deactivated',
            $module,
            [
                'module_id' => (int) $module->getKey(),
                'module_key' => $module->key,
                'is_active' => $nextActive,
                'is_core' => (bool) $module->is_core,
            ],
        );

        return back()->with('status', __('Global module settings updated successfully.'));
    }
}
