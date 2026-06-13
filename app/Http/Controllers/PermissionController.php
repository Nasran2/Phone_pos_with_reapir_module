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
        $permissions = Permission::all()->groupBy(function($item) {
            $parts = explode('.', $item->name);
            return ucwords(str_replace('_', ' ', $parts[0] ?? 'Other'));
        });

        return view('permissions.index', compact('permissions'));
    }
}
