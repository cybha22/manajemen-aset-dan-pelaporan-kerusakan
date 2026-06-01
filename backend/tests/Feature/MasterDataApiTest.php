<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Category;
use App\Models\Room;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MasterDataApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_data_show_routes_return_resource_payloads(): void
    {
        $user = User::create([
            'name' => 'Admin Sarpras',
            'username' => 'admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('secret123'),
        ]);

        $building = Building::create(['code' => 'A', 'name' => 'Gedung A']);
        $room = Room::create(['building_id' => $building->id, 'room_number' => '101']);
        $category = Category::create(['name' => 'Proyektor', 'description' => 'Alat presentasi']);
        $technician = Technician::create([
            'name' => 'Teknisi Proyektor',
            'telegram_chat_id' => '99999',
            'phone' => '081234567892',
            'status' => 'aktif',
        ]);
        $token = $user->createToken('test-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/buildings/' . $building->id)
            ->assertOk()
            ->assertJsonPath('data.code', 'A');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/rooms/' . $room->id)
            ->assertOk()
            ->assertJsonPath('data.room_number', '101');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/categories/' . $category->id)
            ->assertOk()
            ->assertJsonPath('data.name', 'Proyektor');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/technicians/' . $technician->id)
            ->assertOk()
            ->assertJsonPath('data.name', 'Teknisi Proyektor');
    }

    public function test_qr_code_endpoint_returns_svg_response(): void
    {
        $user = User::create([
            'name' => 'Admin Sarpras',
            'username' => 'admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('secret123'),
        ]);

        $building = Building::create(['code' => 'A', 'name' => 'Gedung A']);
        $room = Room::create(['building_id' => $building->id, 'room_number' => '101']);
        $token = $user->createToken('test-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->get('/api/qrcode/' . $room->id)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');
    }
}
