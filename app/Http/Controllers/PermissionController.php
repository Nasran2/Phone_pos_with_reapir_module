<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Display a listing of system permissions.
     */
    public function index()
    {
        // Group permissions by their prefix/module to present a nice visual grid in UI
        $moduleLabels = [
            'repair' => 'Repair Management',
            'retail_pos' => 'Retail POS',
            'wholesale_pos' => 'Wholesale POS',
            'sales_return' => 'Sales Returns',
            'hold_sales' => 'Hold Sales',
            'stock' => 'Store Stock',
            'damaged_stock' => 'Damaged Stock',
            'activity_logs' => 'Activity Logs',
        ];
        $permissions = Permission::orderBy('name')->get()->groupBy(function($item) use ($moduleLabels) {
            $parts = explode('.', $item->name);
            return $moduleLabels[$parts[0] ?? ''] ?? ucwords(str_replace('_', ' ', $parts[0] ?? 'Other'));
        })->sortKeys();

        return view('permissions.index', compact('permissions'));
    }
}
