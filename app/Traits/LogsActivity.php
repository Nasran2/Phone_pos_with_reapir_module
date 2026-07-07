<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Str;

trait LogsActivity
{
    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            $model->logActivity('Create');
        });

        static::updated(function ($model) {
            $model->logActivity('Update');
        });

        static::deleted(function ($model) {
            $model->logActivity('Delete');
        });
    }

    protected function logActivity($action)
    {
        // Do not log if auth user is missing (e.g. running from console or seeder)
        if (!auth()->check()) {
            return;
        }

        $module = Str::headline(class_basename(self::class));
        
        $identifier = $this->name ?? $this->title ?? $this->invoice_no ?? $this->purchase_no ?? $this->return_no ?? $this->reference_no ?? $this->code ?? "ID: {$this->id}";
        
        // Clean up identifier if it's an array or complex object
        if (is_array($identifier) || is_object($identifier)) {
            $identifier = "ID: {$this->id}";
        }

        $description = "{$action}d {$module}: {$identifier}";
        
        ActivityLog::log($action, $module, $description);
    }
}
