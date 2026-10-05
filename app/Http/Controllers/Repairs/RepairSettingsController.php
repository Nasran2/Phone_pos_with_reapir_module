<?php

namespace App\Http\Controllers\Repairs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Repairs\RepairSettingsRequest;
use App\Models\RepairChargeTemplate;
use App\Models\RepairProblem;
use App\Models\RepairStatus;
use App\Models\Store;
use App\Support\RepairSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class RepairSettingsController extends Controller
{
    public function show(): View
    {
        $values = [];
        foreach (RepairSettings::definitions() as $key => $definition) {
            $values[$key] = RepairSettings::get($key, $definition[2]);
        }

        return view('repairs.settings', ['values' => $values, 'statuses' => RepairStatus::orderBy('sort_order')->get(), 'problems' => RepairProblem::orderBy('sort_order')->get(), 'templates' => RepairChargeTemplate::orderBy('sort_order')->get(), 'stores' => Store::orderBy('name')->get()]);
    }

    public function update(RepairSettingsRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            foreach (RepairSettings::definitions() as $key => $setting) {
                DB::table('repair_settings')->updateOrInsert(['key' => $key], ['value' => $setting[1] === 'checkbox' ? ($request->boolean($key) ? '1' : '0') : $request->validated($key), 'created_at' => now(), 'updated_at' => now()]);
            }
        });

        return back()->with('success', 'Repair settings saved.');
    }
}
