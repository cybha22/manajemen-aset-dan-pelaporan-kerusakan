<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// ============================================================
// Model Ticket — Representasi tabel "tickets" (tabel inti sistem)
// Menyimpan semua laporan kerusakan dari mahasiswa
// ============================================================
class Ticket extends Model
{
    use HasFactory;

    // Kolom yang boleh diisi secara mass-assignment
    protected $fillable = [
        'ticket_code',
        'reporter_name',
        'reporter_phone',
        'room_id',
        'category_id',
        'admin_id',
        'technician_id',
        'photo_path',
        'description',
        'status',
        'assigned_at',
        'resolved_at',
    ];

    // Casting kolom tanggal agar otomatis jadi objek Carbon
    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    // Relasi: Tiket milik satu Ruangan (Many-to-One)
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // Relasi: Tiket milik satu Kategori Kerusakan (Many-to-One)
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relasi: Tiket divalidasi oleh satu Admin (Many-to-One)
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    // Relasi: Tiket dikerjakan oleh satu Teknisi (Many-to-One)
    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }
}
