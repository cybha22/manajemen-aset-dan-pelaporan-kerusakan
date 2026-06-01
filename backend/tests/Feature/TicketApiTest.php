<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Category;
use App\Models\Room;
use App\Models\Technician;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_create_ticket_and_admin_can_paginate_update_and_delete_it(): void
    {
        $admin = User::create([
            'name' => 'Admin Sarpras',
            'username' => 'admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('secret123'),
        ]);

        $building = Building::create(['code' => 'A', 'name' => 'Gedung A']);
        $category = Category::create(['name' => 'Air Conditioner (AC)', 'description' => 'Pendingin ruangan']);
        $technician = Technician::create([
            'name' => 'Teknisi AC',
            'telegram_chat_id' => '12345',
            'phone' => '081234567891',
            'status' => 'aktif',
        ]);
        $room = Room::create(['building_id' => $building->id, 'room_number' => '101']);

        $create = $this->postJson('/api/tickets', [
            'reporter_name' => 'Mahasiswa',
            'reporter_phone' => '081234567890',
            'building_id' => $building->id,
            'room_number' => '101',
            'category_id' => $category->id,
            'description' => 'AC tidak dingin sejak pagi',
        ]);

        $create->assertCreated()
            ->assertJsonStructure(['message', 'ticket_code', 'status']);

        for ($i = 0; $i < 17; $i++) {
            Ticket::create([
                'ticket_code' => 'TK-' . str_pad((string) ($i + 10000), 5, '0', STR_PAD_LEFT),
                'reporter_name' => 'Pelapor ' . $i,
                'reporter_phone' => '0812345678' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'room_id' => $room->id,
                'category_id' => $category->id,
                'description' => 'Kerusakan aset nomor ' . $i,
                'status' => 'Baru',
            ]);
        }

        $token = $admin->createToken('test-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/tickets')
            ->assertOk()
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('per_page', 15)
            ->assertJsonCount(15, 'data');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/tickets?page=2')
            ->assertOk()
            ->assertJsonPath('current_page', 2)
            ->assertJsonCount(3, 'data');

        $ticket = Ticket::query()->first();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/tickets/' . $ticket->id, [
                'status' => 'Ditugaskan',
                'technician_id' => $technician->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'Ditugaskan');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/tickets/' . $ticket->id)
            ->assertOk();
    }
}
