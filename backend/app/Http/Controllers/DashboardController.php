<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// ============================================================
// DashboardController — Menyediakan data untuk Dasbor Analitik Admin
// Menghitung statistik tiket dan data grafik
// ============================================================
class DashboardController extends Controller
{
    // ============================================================
    // Statistik utama: Total tiket, Selesai, Dikerjakan, Baru, Rata-rata waktu
    // Ditampilkan di 4 kartu statistik di bagian atas dashboard
    // ============================================================
    public function stats()
    {
        $total = Ticket::count();
        $resolved = Ticket::where('status', 'Selesai')
            ->whereMonth('resolved_at', now()->month)
            ->whereYear('resolved_at', now()->year)
            ->count();
        $inProgress = Ticket::whereIn('status', ['Divalidasi', 'Ditugaskan', 'Dikerjakan'])->count();
        $pending = Ticket::where('status', 'Baru')->count();

        // Hitung rata-rata waktu penyelesaian (dari created_at sampai resolved_at) dalam jam
        $avgHours = Ticket::where('status', 'Selesai')
            ->whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at) / 60.0) as avg_hours')
            ->value('avg_hours');

        return response()->json([
            'total' => $total,
            'selesai' => $resolved,
            'dikerjakan' => $inProgress,
            'baru' => $pending,
            'avg_resolution_hours' => round($avgHours ?? 0, 1),
        ]);
    }

    // ============================================================
    // Data grafik "Tren Pelaporan Mingguan" (line chart)
    // Menghitung jumlah tiket per minggu selama 8 minggu terakhir
    // ============================================================
    public function chartWeekly()
    {
        $weeks = collect();
        for ($i = 7; $i >= 0; $i--) {
            $start = now()->subWeeks($i)->startOfWeek();
            $end = now()->subWeeks($i)->endOfWeek();
            $count = Ticket::whereBetween('created_at', [$start, $end])->count();
            $weeks->push(['label' => 'W' . (8 - $i), 'count' => $count]);
        }

        return response()->json([
            'labels' => $weeks->pluck('label'),
            'data' => $weeks->pluck('count'),
        ]);
    }

    // ============================================================
    // Data grafik "Distribusi Kategori" (donut chart)
    // Menghitung jumlah tiket per kategori kerusakan (AC, Proyektor, dll)
    // ============================================================
    public function chartCategory()
    {
        $data = Ticket::select('category_id', DB::raw('COUNT(*) as total'))
            ->groupBy('category_id')
            ->with('category')
            ->get()
            ->map(fn($item) => [
                'label' => $item->category->name,
                'count' => $item->total,
            ]);

        return response()->json([
            'labels' => $data->pluck('label'),
            'data' => $data->pluck('count'),
        ]);
    }

    // ============================================================
    // Data grafik "Performa per Gedung" (bar chart)
    // Menghitung jumlah tiket per gedung (A, B, C, dll)
    // ============================================================
    public function chartBuilding()
    {
        $data = Ticket::select('room_id', DB::raw('COUNT(*) as total'))
            ->groupBy('room_id')
            ->with('room.building')
            ->get()
            ->groupBy(fn($item) => $item->room->building->name)
            ->map(fn($group) => $group->sum('total'));

        return response()->json([
            'labels' => $data->keys(),
            'data' => $data->values(),
        ]);
    }

    // ============================================================
    // Data grafik "Rata-rata Waktu Respon (Jam)" (horizontal bar chart)
    // Menghitung rata-rata waktu penyelesaian per kategori kerusakan
    // Rumus: AVG(resolved_at - created_at) dalam jam per kategori
    // ============================================================
    public function chartResponseTime()
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

        return response()->json([
            'labels' => $data->pluck('label'),
            'data' => $data->pluck('hours'),
        ]);
    }
}
