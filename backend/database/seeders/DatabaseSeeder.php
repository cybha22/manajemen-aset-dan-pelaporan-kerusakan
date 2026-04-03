<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Building;
use App\Models\Room;
use App\Models\Category;
use App\Models\User;
use App\Models\Technician;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $buildings = [
            ['code' => 'A', 'name' => 'Gedung A'],
            ['code' => 'B', 'name' => 'Gedung B'],
            ['code' => 'C', 'name' => 'Gedung C'],
            ['code' => 'D', 'name' => 'Gedung D'],
            ['code' => 'E', 'name' => 'Gedung E'],
            ['code' => 'F', 'name' => 'Gedung F'],
            ['code' => 'G', 'name' => 'Gedung G'],
            ['code' => 'H', 'name' => 'Gedung H'],
        ];

        foreach ($buildings as $b) {
            Building::create($b);
        }

        $gedungA = Building::where('code', 'A')->first();
        $rooms = ['101', '102', '103', '201', '202', '301', '302'];
        foreach ($rooms as $r) {
            Room::create([
                'building_id' => $gedungA->id,
                'room_number' => $r,
            ]);
        }

        Category::create(['name' => 'Air Conditioner (AC)', 'description' => 'Pendingin ruangan']);
        Category::create(['name' => 'Proyektor & Layar', 'description' => 'Alat presentasi kelas']);
        Category::create(['name' => 'Furnitur (Kursi / Meja)', 'description' => 'Perabotan kelas']);
        Category::create(['name' => 'Kelistrikan (Lampu / Stop Kontak)', 'description' => 'Instalasi listrik ruangan']);

        User::create([
            'name' => 'Zuli Maulidati',
            'username' => 'admin',
            'email' => 'admin@asetlink.itats.ac.id',
            'password' => Hash::make('qwe123'),
        ]);

        Technician::create(['name' => 'Pak Budi', 'telegram_chat_id' => '5551234', 'phone' => '081234567890', 'status' => 'aktif']);
        Technician::create(['name' => 'Pak Agus', 'telegram_chat_id' => '5555678', 'phone' => '081298765432', 'status' => 'aktif']);
        Technician::create(['name' => 'Pak Dedi', 'telegram_chat_id' => '5559012', 'phone' => '081356789012', 'status' => 'nonaktif']);
    }
}