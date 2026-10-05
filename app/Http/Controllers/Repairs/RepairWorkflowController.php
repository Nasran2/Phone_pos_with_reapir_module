<?php

namespace App\Http\Controllers\Repairs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Repairs\RepairActionRequest;
use App\Models\RepairImage;
use App\Models\RepairJob;
use App\Services\Repairs\RepairActivityService;
use App\Services\Repairs\RepairImageService;
use App\Services\Repairs\RepairInventoryService;
use App\Services\Repairs\RepairJobService;
use App\Services\Repairs\RepairPaymentService;
use App\Services\Repairs\RepairPricingService;
use App\Services\Repairs\RepairStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RepairWorkflowController extends Controller
{
    public function action(RepairActionRequest $request, RepairJob $repairJob): RedirectResponse
    {
        $data = $request->validated();
        $action = $request->route('action');
        if ($action === 'status') {
            app(RepairStatusService::class)->change($repairJob, $data);
        } elseif ($action === 'assign') {
            app(RepairJobService::class)->assign($repairJob, $data['technician_id']);
        } elseif ($action === 'part') {
            app(RepairInventoryService::class)->add($repairJob, $data);
        } elseif ($action === 'part-state') {
            app(RepairInventoryService::class)->change($repairJob, $repairJob->parts()->whereKey((int) $data['part_id'])->firstOrFail(), $data['operation']);
        } elseif ($action === 'payment') {
            app(RepairPaymentService::class)->add($repairJob, $data);
        } else {
            DB::transaction(function () use ($repairJob, $data, $action, $request) {
                $job = RepairJob::whereKey($repairJob->id)->lockForUpdate()->firstOrFail();
                if (in_array($action, ['charge', 'charge-delete', 'pricing', 'checklist', 'approval', 'photo-delete'])) {
                    app(RepairInventoryService::class)->editable($job);
                }
                switch ($action) {
                    case 'charge':
                        $charge = isset($data['charge_id']) ? $job->charges()->whereKey((int) $data['charge_id'])->firstOrFail() : $job->charges()->make();
                        $charge->fill(collect($data)->only(['name', 'amount'])->all())->save();
                        app(RepairPricingService::class)->refresh($job);
                        break;
                    case 'charge-delete': $job->charges()->whereKey((int) $data['charge_id'])->firstOrFail()->delete();
                        app(RepairPricingService::class)->refresh($job);
                        break;
                    case 'pricing': $job->update($data);
                        app(RepairPricingService::class)->refresh($job);
                        break;
                    case 'note': $job->notes()->create(array_merge($data, ['user_id' => auth()->id()]));
                        break;
                    case 'checklist': $check = $job->checklist()->firstOrNew();
                        $items = $check->items ?? [];
                        $items['after'] = $data['after'];
                        $check->items = $items;
                        $check->save();
                        break;
                    case 'approval': $job->update(array_merge($data, ['approved_at' => $data['approval_status'] === 'Pending' ? null : now(), 'approved_by' => auth()->id()]));
                        break;
                    case 'warranty':
                        $data['duration'] = (int) $data['duration'];
                        $start = isset($data['starts_at']) ? Carbon::parse($data['starts_at']) : today();
                        $data['starts_at'] = $data['warranty_type'] === 'None' ? null : $start->toDateString();
                        $data['expires_at'] = $data['warranty_type'] === 'None' ? null : ($data['warranty_type'] === 'Months' ? $start->copy()->addMonthsNoOverflow($data['duration']) : $start->copy()->addDays($data['duration']))->toDateString();
                        $job->warranty()->updateOrCreate([], $data);
                        break;
                    case 'photo':
                        app(RepairImageService::class)->add($job, $request->file('images'), $data['type']);
                        break;
                    case 'photo-delete': $image = $job->images()->whereKey((int) $data['image_id'])->firstOrFail();
                        $image->delete();
                        DB::afterCommit(fn () => Storage::disk('local')->delete($image->path));
                        break;
                    default: abort(404);
                }
                app(RepairActivityService::class)->log($job, ucfirst(str_replace('-', ' ', $action)).' updated');
            }, 3);
        }

        return redirect()->route('repairs.show', $repairJob)->with('success', 'Job updated.');
    }

    public function reveal(RepairJob $repairJob): JsonResponse
    {
        app(RepairActivityService::class)->log($repairJob, 'Credential revealed', 'Authorized access to the device unlock credential');
        $device = $repairJob->device;

        return response()->json(['type' => $device ? $device->unlock_type : 'None', 'secret' => $device?->unlock_secret])->header('Cache-Control', 'no-store, private')->header('Pragma', 'no-cache');
    }

    public function image(RepairJob $repairJob, RepairImage $image): StreamedResponse
    {
        abort_unless($image->repair_job_id === $repairJob->id && str_starts_with($image->path, 'repairs/'.$repairJob->id.'/'), 404);

        return Storage::disk('local')->response($image->path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function destroy(RepairJob $repairJob): RedirectResponse
    {
        DB::transaction(function () use ($repairJob) {
            $job = RepairJob::whereKey($repairJob->id)->lockForUpdate()->firstOrFail();
            if (! $job->repairStatus?->is_cancelled || $job->payments()->exists() || $job->parts()->where('status', 'Issued')->exists() || (float) $job->total_amount !== 0.0) {
                throw ValidationException::withMessages(['job' => 'Only cancelled jobs without payments, issued parts or financial balances can be archived. Financial history is retained.']);
            }
            app(RepairActivityService::class)->log($job, 'Job archived');
            $job->delete();
        });

        return redirect()->route('repairs.index')->with('success', 'Job archived.');
    }
}
