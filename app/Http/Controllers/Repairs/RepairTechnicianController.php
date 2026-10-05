<?php

namespace App\Http\Controllers\Repairs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Repairs\RepairTechnicianRequest;
use App\Models\ActivityLog;
use App\Models\RepairTechnician;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RepairTechnicianController extends Controller
{
    public function index(): View
    {
        return view('repairs.technicians', ['technicians' => RepairTechnician::withCount('jobs')->orderBy('name')->get()]);
    }

    public function store(RepairTechnicianRequest $request): RedirectResponse
    {
        $this->save($request, new RepairTechnician);

        return back()->with('success', 'Technician created.');
    }

    public function update(RepairTechnicianRequest $request, RepairTechnician $technician): RedirectResponse
    {
        $this->save($request, $technician);

        return back()->with('success', 'Technician updated.');
    }

    private function save(RepairTechnicianRequest $request, RepairTechnician $technician): void
    {
        $data = $request->safe()->except('photo');
        $old = $technician->photo;
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('repairs/technicians', 'local');
        }
        $technician->fill($data)->save();
        if (isset($data['photo']) && $old) {
            Storage::disk('local')->delete($old);
        }
        ActivityLog::log('Update', 'Repair Technicians', 'Technician '.$technician->name.' '.$technician->status);
    }

    public function photo(RepairTechnician $technician): StreamedResponse
    {
        abort_unless($technician->photo && str_starts_with($technician->photo, 'repairs/technicians/'), 404);

        return Storage::disk('local')->response($technician->photo, null, ['Cache-Control' => 'private']);
    }
}
