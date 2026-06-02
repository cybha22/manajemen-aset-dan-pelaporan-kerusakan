<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Category;
use App\Models\Room;
use App\Models\RoomAsset;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MasterDataApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_master_lists_keep_array_payloads_and_support_pagination(): void
    {
        $building = Building::create(['code' => 'A', 'name' => 'Gedung A']);
        Building::create(['code' => 'B', 'name' => 'Gedung B']);
        Building::create(['code' => 'C', 'name' => 'Gedung C']);

        Category::create(['name' => 'Air Conditioner (AC)', 'description' => 'Pendingin ruangan']);
        Category::create(['name' => 'Proyektor', 'description' => 'Alat presentasi']);
        Category::create(['name' => 'Furnitur', 'description' => 'Meja dan kursi']);

        for ($i = 1; $i <= 3; $i++) {
            Room::create(['building_id' => $building->id, 'room_number' => '10' . $i]);
        }

        $buildings = $this->getJson('/api/buildings')->assertOk();
        $this->assertTrue(array_is_list($buildings->json()));

        $this->getJson('/api/buildings?paginated=1&per_page=2')
            ->assertOk()
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('per_page', 2)
            ->assertJsonPath('total', 3)
            ->assertJsonCount(2, 'data');

        $categories = $this->getJson('/api/categories')->assertOk();
        $this->assertTrue(array_is_list($categories->json()));

        $this->getJson('/api/categories?paginated=1&per_page=2')
            ->assertOk()
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('per_page', 2)
            ->assertJsonPath('total', 3)
            ->assertJsonCount(2, 'data');

        $rooms = $this->getJson('/api/rooms')->assertOk();
        $this->assertTrue(array_is_list($rooms->json()));

        $this->getJson('/api/rooms?paginated=1&per_page=2')
            ->assertOk()
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('per_page', 2)
            ->assertJsonPath('total', 3)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'building_id',
                        'room_number',
                        'building',
                        'registered_assets',
                        'categories',
                        'total_tickets',
                    ],
                ],
            ]);
    }

    public function test_authenticated_master_lists_support_pagination(): void
    {
        $user = User::create([
            'name' => 'Admin Sarpras',
            'username' => 'admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('secret123'),
        ]);

        $building = Building::create(['code' => 'A', 'name' => 'Gedung A']);
        $room = Room::create(['building_id' => $building->id, 'room_number' => '101']);

        $categories = [];
        foreach (['AC', 'Proyektor', 'Furnitur'] as $name) {
            $categories[] = Category::create(['name' => $name, 'description' => $name]);
        }

        for ($i = 1; $i <= 3; $i++) {
            Technician::create([
                'name' => 'Teknisi ' . $i,
                'telegram_chat_id' => '9000' . $i,
                'phone' => '0812345678' . $i,
                'status' => 'aktif',
            ]);

            RoomAsset::create([
                'room_id' => $room->id,
                'category_id' => $categories[$i - 1]->id,
                'quantity' => $i,
                'condition' => 'Baik',
            ]);
        }

        $token = $user->createToken('test-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/technicians?paginated=1&per_page=2')
            ->assertOk()
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('per_page', 2)
            ->assertJsonPath('total', 3)
            ->assertJsonCount(2, 'data');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/room-assets?room_id=' . $room->id . '&paginated=1&per_page=2')
            ->assertOk()
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('per_page', 2)
            ->assertJsonPath('total', 3)
            ->assertJsonCount(2, 'data');
    }

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
