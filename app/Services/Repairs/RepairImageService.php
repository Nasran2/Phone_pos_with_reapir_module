<?php

namespace App\Services\Repairs;

use App\Models\RepairJob;
use App\Support\RepairSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RepairImageService
{
    /** @param array<int, UploadedFile> $files */
    public function add(RepairJob $job, array $files, string $type): void
    {
        $paths = [];
        try {
            DB::transaction(function () use ($job, $files, $type, &$paths) {
                RepairJob::whereKey($job->id)->lockForUpdate()->firstOrFail();
                if ($job->images()->count() + count($files) > (int) RepairSettings::get('image_limit', 12)) {
                    throw ValidationException::withMessages(['images' => 'The photo limit for this job has been reached.']);
                }
                foreach ($files as $file) {
                    $path = $file->store('repairs/'.$job->id, 'local');
                    $paths[] = $path;
                    $job->images()->create(['type' => $type, 'path' => $path]);
                }
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($paths);
            throw $e;
        }
    }
}
