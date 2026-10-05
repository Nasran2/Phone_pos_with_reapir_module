<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Support\Features;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of activity logs.
     */
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->orderBy('id', 'desc');

        // Search in action, description, IP address or user name
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by Module
        if ($request->filled('module')) {
            $query->where('module', $request->input('module'));
        }

        // Filter by User
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $logs = $query->paginate(25)->withQueryString();

        // Get unique modules and all users for filter select inputs
        $modules = ActivityLog::when(! Features::wholesaleEnabled(), fn ($q) => $q->where('module', 'not like', '%wholesale%'))->distinct()->pluck('module')->filter()->values();
        $users = User::all();

        return view('activity_logs.index', compact('logs', 'modules', 'users'));
    }
}
