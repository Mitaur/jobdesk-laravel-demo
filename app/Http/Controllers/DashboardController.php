<?php

namespace App\Http\Controllers;

use App\Enums\JobStatus;
use App\Models\ServiceJob;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        // Count per status for this user's jobs (one grouped query).
        $raw = ServiceJob::query()
            ->whereHas('customer', fn ($q) => $q->where('user_id', $user->id))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = collect(JobStatus::cases())
            ->mapWithKeys(fn (JobStatus $s) => [$s->value => (int) ($raw[$s->value] ?? 0)]);

        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        $thisWeek = $user->serviceJobs()
            ->with('customer')
            ->whereBetween('service_jobs.scheduled_at', [$weekStart, $weekEnd])
            ->orderBy('service_jobs.scheduled_at')
            ->get();

        return view('dashboard', [
            'counts' => $counts,
            'thisWeek' => $thisWeek,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'customerCount' => $user->customers()->count(),
        ]);
    }
}
