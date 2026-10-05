<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityUser;
use Illuminate\Support\Facades\DB;

class ActivityUserController extends Controller
{
    /**
     * Display a listing of user activities
     */
    public function index(Request $request)
    {
        $query = ActivityUser::with('user')
            ->orderBy('created_at', 'desc');

        // Filter by model if provided
        if ($request->has('model') && $request->model) {
            $query->where('model_name', $request->model);
        }

        // Filter by action if provided
        if ($request->has('action') && $request->action) {
            $query->where('action', strtoupper($request->action));
        }

        // Filter by date range if provided
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $activities = $query->paginate(50);

        return view('activity-user.index', compact('activities'));
    }

    /**
     * Get activity statistics
     */
    public function statistics()
    {
        $stats = [
            'total_activities' => ActivityUser::count(),
            'activities_today' => ActivityUser::whereDate('created_at', today())->count(),
            'activities_this_week' => ActivityUser::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'activities_this_month' => ActivityUser::whereMonth('created_at', now()->month)->count(),
        ];

        $activities_by_model = ActivityUser::select('model_name', DB::raw('count(*) as count'))
            ->groupBy('model_name')
            ->orderBy('count', 'desc')
            ->get();

        $activities_by_action = ActivityUser::select('action', DB::raw('count(*) as count'))
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->get();

        $activities_by_user = ActivityUser::with('user')
            ->select('user_id', DB::raw('count(*) as count'))
            ->groupBy('user_id')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'stats' => $stats,
            'by_model' => $activities_by_model,
            'by_action' => $activities_by_action,
            'by_user' => $activities_by_user
        ]);
    }
}
