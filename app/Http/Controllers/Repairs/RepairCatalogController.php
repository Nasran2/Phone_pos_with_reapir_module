<?php

namespace App\Http\Controllers\Repairs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Repairs\RepairCatalogRequest;
use App\Models\RepairChargeTemplate;
use App\Models\RepairProblem;
use App\Models\RepairStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RepairCatalogController extends Controller
{
    /** @return class-string<RepairStatus>|class-string<RepairProblem>|class-string<RepairChargeTemplate> */
    private function model(string $catalog): string
    {
        return match ($catalog) {
            'statuses' => RepairStatus::class,'problems' => RepairProblem::class,'charges' => RepairChargeTemplate::class,default => abort(404)
        };
    }

    public function store(RepairCatalogRequest $request, string $catalog): RedirectResponse
    {
        $class = $this->model($catalog);
        $this->save($request, new $class, $catalog);

        return back()->with('success', 'Catalog entry created.');
    }

    public function update(RepairCatalogRequest $request, string $catalog, int $record): RedirectResponse
    {
        $class = $this->model($catalog);
        $this->save($request, $class::findOrFail($record), $catalog);

        return back()->with('success', 'Catalog entry updated.');
    }

    private function save(RepairCatalogRequest $request, RepairStatus|RepairProblem|RepairChargeTemplate $record, string $catalog): void
    {
        DB::transaction(function () use ($request, $record, $catalog) {
            $class = $this->model($catalog);
            $data = $request->validated();
            if ($record instanceof RepairStatus) {
                $all = RepairStatus::orderBy('id')->lockForUpdate()->get();
                $default = $record->is_default || $data['is_default'];
                if (! $data['is_active'] && $default) {
                    throw ValidationException::withMessages(['is_active' => 'Choose another active default status before deactivating this status.']);
                }
                if ($record->exists && $record->jobs()->exists()) {
                    foreach (['is_completed', 'is_delivered', 'is_cancelled', 'is_ready', 'is_waiting_approval', 'is_waiting_parts'] as $flag) {
                        if ((bool) $record->$flag !== (bool) $data[$flag]) {
                            throw ValidationException::withMessages([$flag => 'Workflow meaning is preserved for statuses used by historical jobs. Create a new status to change its meaning.']);
                        }
                    }
                }
                if ($data['is_default']) {
                    RepairStatus::where('id', '!=', $record->id ?? 0)->update(['is_default' => false]);
                }
                if (! $record->exists) {
                    $data['key'] = Str::slug($data['name'], '_').'_'.Str::lower(Str::random(8));
                }
                if ($record->is_default && ! $data['is_default'] && ! $all->contains(fn ($s) => $s->id !== $record->id && $s->is_default && $s->is_active)) {
                    throw ValidationException::withMessages(['is_default' => 'At least one active default status is required.']);
                }
                if (($data['is_completed'] + $data['is_delivered'] + $data['is_cancelled']) > 1) {
                    throw ValidationException::withMessages(['status' => 'A status cannot complete, deliver and cancel at the same time.']);
                }
            }
            if ($class::where('name', $data['name'])->where('id', '!=', $record->id ?? 0)->exists()) {
                throw ValidationException::withMessages(['name' => 'This catalog name already exists.']);
            }
            $record->fill($data)->save();
        }, 3);
    }
}
