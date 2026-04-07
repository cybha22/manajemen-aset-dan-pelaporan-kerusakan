<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function all()
    {
        return response()->json(
            Cache::remember('dashboard:all', now()->addSeconds(60), function () {
                return [
                    'stats'        => $this->buildStats(),
                    'weekly'       => $this->buildWeekly(),
                    'category'     => $this->buildCategory(),
                    'building'     => $this->buildBuilding(),
                    'responseTime' => $this->buildResponseTime(),
                ];
            })
        );
    }

    public function stats()
    {
        return response()->json($this->buildStats());
    }

    public function chartWeekly()
    {
        return response()->json($this->buildWeekly());
    }

    public function chartCategory()
    {
        return response()->json($this->buildCategory());
    }

    public function chartBuilding()
    {
        return response()->json($this->buildBuilding());
    }

    public function chartResponseTime()
    {
        return response()->json($this->buildResponseTime());
    }

    private function buildStats(): array
    {
        $total = Ticket::count();
        $resolved = Ticket::where('status', 'Selesai')
            ->whereMonth('resolved_at', now()->month)
            ->whereYear('resolved_at', now()->year)
            ->count();
        $inProgress = Ticket::whereIn('status', ['Divalidasi', 'Ditugaskan', 'Dikerjakan'])->count();
        $pending = Ticket::where('status', 'Baru')->count();
        $avgHours = Ticket::where('status', 'Selesai')
            ->whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at) / 60.0) as avg_hours')
            ->value('avg_hours');

        return [
            'total' => $total,
            'selesai' => $resolved,
            'dikerjakan' => $inProgress,
            'baru' => $pending,
            'avg_resolution_hours' => round($avgHours ?? 0, 1),
        ];
    }

    private function buildWeekly(): array
    {
        $start = now()->subWeeks(7)->startOfWeek();
        $rows = Ticket::where('created_at', '>=', $start)
            ->selectRaw('YEARWEEK(created_at, 1) as yw, COUNT(*) as cnt')
            ->groupBy('yw')
            ->pluck('cnt', 'yw');

        $labels = [];
        $data   = [];
        for ($i = 7; $i >= 0; $i--) {
            $w = now()->subWeeks($i);
            $yw = $w->format('oW');
            $labels[] = 'W' . (8 - $i);
            $data[] = (int) ($rows[$yw] ?? 0);
        }

        return compact('labels', 'data');
    }

    private function buildCategory(): array
    {
        $data = Ticket::select('category_id', DB::raw('COUNT(*) as total'))
            ->groupBy('category_id')
            ->with('category')
            ->get()
            ->map(fn($item) => [
                'label' => $item->category->name,
                'count' => $item->total,
            ]);

        return [
            'labels' => $data->pluck('label')->values(),
            'data'   => $data->pluck('count')->values(),
        ];
    }

    private function buildBuilding(): array
    {
        $data = Ticket::select('room_id', DB::raw('COUNT(*) as total'))
            ->groupBy('room_id')
            ->with('room.building')
            ->get()
            ->groupBy(fn($item) => $item->room->building->name)
            ->map(fn($group) => $group->sum('total'));

        return [
            'labels' => $data->keys()->values(),
            'data'   => $data->values()->values(),
        ];
    }

    private function buildResponseTime(): array
    {
        $data = Ticket::where('status', 'Selesai')
            ->whereNotNull('resolved_at')
            ->select('category_id', DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at) / 60.0) as avg_hours'))
            ->groupBy('category_id')
            ->with('category')
            ->get()
            ->map(fn($item) => [
                'label' => $item->category->name ?? '-',
                'hours' => round($item->avg_hours, 1),
            ]);

        return [
            'labels' => $data->pluck('label')->values(),
            'data'   => $data->pluck('hours')->values(),
        ];
    }
}
