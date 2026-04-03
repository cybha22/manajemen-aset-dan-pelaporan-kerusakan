<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Room;
use App\Models\Building;
use App\Models\Technician;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    // ============================================================
    // Menampilkan daftar semua tiket (untuk halaman Manajemen Tiket admin)
    // Mendukung filter berdasarkan status (Baru, Dikerjakan, Selesai, dll)
    // ============================================================
    public function index(Request $request)
    {
        // Eager load relasi agar tidak terjadi N+1 query problem
        $query = Ticket::with(['room.building', 'category', 'admin', 'technician']);

        // Filter opsional berdasarkan status tiket
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Urutkan dari terbaru, paginasi 15 per halaman
        $tickets = $query->latest()->paginate(15);

        return response()->json($tickets);
    }

    // ============================================================
    // Menyimpan laporan kerusakan baru dari mahasiswa (endpoint publik)
    // Alur: Validasi → Upload Foto → Generate Kode Tiket → Simpan ke DB
    // ============================================================
    public function store(Request $request)
    {
        // Validasi semua input dari form pelaporan
        $request->validate([
            'reporter_name' => 'required|string|max:100',
            'reporter_phone' => 'required|string|regex:/^08[0-9]{8,12}$/',
            'building_id' => 'required|exists:buildings,id',
            'room_number' => 'required|string|max:10',
            'category_id' => 'required|exists:categories,id',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'description' => 'required|string|min:10|max:1000',
        ]);

        // Cari ruangan atau buat baru jika belum ada di database
        $room = Room::firstOrCreate(
            ['building_id' => $request->building_id, 'room_number' => $request->room_number],
            ['building_id' => $request->building_id, 'room_number' => $request->room_number]
        );

        // Generate kode tiket unik format TK-XXXXX
        $ticketCode = $this->generateTicketCode();

        // Upload foto bukti kerusakan ke storage publik
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $ext = $request->file('photo')->getClientOriginalExtension();
            $filename = $ticketCode . '_' . time() . '.' . $ext;
            $photoPath = $request->file('photo')->storeAs('tickets', $filename, 'public');
        }

        // Simpan tiket baru ke database dengan status awal "Baru"
        $ticket = Ticket::create([
            'ticket_code' => $ticketCode,
            'reporter_name' => $request->reporter_name,
            'reporter_phone' => $request->reporter_phone,
            'room_id' => $room->id,
            'category_id' => $request->category_id,
            'photo_path' => $photoPath,
            'description' => $request->description,
            'status' => 'Baru',
        ]);

        // Kembalikan kode tiket ke mahasiswa untuk pelacakan
        return response()->json([
            'message' => 'Ticket created successfully.',
            'ticket_code' => $ticket->ticket_code,
            'status' => $ticket->status,
        ], 201);
    }

    // ============================================================
    // Menampilkan detail satu tiket (digunakan admin saat klik tiket)
    // ============================================================
    public function show(Ticket $ticket)
    {
        $ticket->load(['room.building', 'category', 'admin', 'technician']);

        return response()->json(['data' => $ticket]);
    }

    // ============================================================
    // Update status tiket oleh admin (Alur: Baru → Divalidasi → Ditugaskan → Dikerjakan → Selesai)
    // Jika status diubah ke "Ditugaskan", sistem otomatis kirim notifikasi ke Telegram teknisi
    // ============================================================
    public function update(Request $request, Ticket $ticket)
    {
        $request->validate([
            'status' => 'required|in:Baru,Divalidasi,Ditugaskan,Dikerjakan,Selesai',
            'technician_id' => 'required_if:status,Ditugaskan|exists:technicians,id',
        ]);

        $data = ['status' => $request->status];

        // Catat admin yang memvalidasi
        if ($request->status === 'Divalidasi') {
            $data['admin_id'] = $request->user()->id;
        }

        // Catat teknisi yang ditugaskan dan waktu penugasan
        if ($request->status === 'Ditugaskan') {
            $data['technician_id'] = $request->technician_id;
            $data['assigned_at'] = now();
        }

        // Catat waktu penyelesaian (untuk perhitungan rata-rata waktu respon)
        if ($request->status === 'Selesai') {
            $data['resolved_at'] = now();
        }

        $ticket->update($data);

        $ticket = $ticket->fresh()->load(['room.building', 'category', 'admin', 'technician']);

        // INTEGRASI TELEGRAM: Kirim notifikasi tugas ke teknisi via bot
        if ($request->status === 'Ditugaskan' && $ticket->technician) {
            TelegramService::sendTaskNotification($ticket);
        }

        return response()->json(['data' => $ticket]);
    }

    // ============================================================
    // Hapus tiket (termasuk foto yang terupload di storage)
    // ============================================================
    public function destroy(Ticket $ticket)
    {
        if ($ticket->photo_path) {
            Storage::disk('public')->delete($ticket->photo_path);
        }

        $ticket->delete();

        return response()->json(['message' => 'Ticket deleted successfully.']);
    }

    // ============================================================
    // Pelacakan tiket oleh mahasiswa (endpoint publik)
    // Mengembalikan status terkini + timeline progres dari awal sampai selesai
    // ============================================================
    public function track($code)
    {
        $ticket = Ticket::where('ticket_code', $code)->with(['room.building', 'category'])->first();

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }

        // Bangun timeline progres berdasarkan status saat ini
        $timeline = [];
        $timeline[] = ['status' => 'Baru', 'label' => 'Laporan Diterima', 'time' => $ticket->created_at->format('d M Y, H:i'), 'done' => true];

        if (in_array($ticket->status, ['Divalidasi', 'Ditugaskan', 'Dikerjakan', 'Selesai'])) {
            $timeline[] = ['status' => 'Divalidasi', 'label' => 'Divalidasi Admin', 'time' => $ticket->updated_at->format('d M Y, H:i'), 'done' => true];
        }

        if (in_array($ticket->status, ['Ditugaskan', 'Dikerjakan', 'Selesai'])) {
            $timeline[] = ['status' => 'Ditugaskan', 'label' => 'Teknisi Ditugaskan', 'time' => $ticket->assigned_at ? $ticket->assigned_at->format('d M Y, H:i') : '-', 'done' => true];
        }

        if (in_array($ticket->status, ['Dikerjakan', 'Selesai'])) {
            $timeline[] = ['status' => 'Dikerjakan', 'label' => 'Sedang Dikerjakan', 'time' => '-', 'done' => true];
        }

        if ($ticket->status === 'Selesai') {
            $timeline[] = ['status' => 'Selesai', 'label' => 'Perbaikan Selesai', 'time' => $ticket->resolved_at ? $ticket->resolved_at->format('d M Y, H:i') : '-', 'done' => true];
        }

        return response()->json([
            'ticket_code' => $ticket->ticket_code,
            'location' => $ticket->room->building->name . ' / R.' . $ticket->room->room_number,
            'category' => $ticket->category->name,
            'status' => $ticket->status,
            'created_at' => $ticket->created_at->format('d M Y, H:i'),
            'timeline' => $timeline,
        ]);
    }

    // ============================================================
    // Generate kode tiket unik (format: TK-XXXXX)
    // Loop sampai menemukan kode yang belum terpakai di database
    // ============================================================
    private function generateTicketCode(): string
    {
        do {
            $code = 'TK-' . str_pad(random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
        } while (Ticket::where('ticket_code', $code)->exists());

        return $code;
    }
}
