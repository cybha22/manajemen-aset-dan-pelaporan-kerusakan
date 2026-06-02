<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $query = Room::with(['building', 'assets.category', 'tickets.category']);

        if ($request->has('building_id')) {
            $query->where('building_id', $request->building_id);
        }

        $query->orderBy('building_id')->orderBy('room_number');

        // Data ruangan diformat sama baik saat memakai paginator maupun array biasa.
        if ($this->wantsPagination($request)) {
            $rooms = $query->paginate($this->perPage($request));
            $rooms->setCollection($rooms->getCollection()->map(fn(Room $room) => $this->formatRoom($room)));

            return response()->json($rooms);
        }

        $rooms = $query->get()->map(fn(Room $room) => $this->formatRoom($room));

        return response()->json($rooms);
    }

    public function store(Request $request)
    {
        $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'room_number' => 'required|string|max:10',
        ]);

        $room = Room::create($request->only('building_id', 'room_number'));

        return response()->json(['data' => $room], 201);
    }

    public function show(Room $room)
    {
        return response()->json([
            'data' => $room->load(['building', 'assets.category', 'tickets.category']),
        ]);
    }

    public function update(Request $request, Room $room)
    {
        $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'room_number' => 'required|string|max:10',
        ]);

        $room->update($request->only('building_id', 'room_number'));

        return response()->json(['data' => $room]);
    }

    public function destroy(Room $room)
    {
        $room->delete();

        return response()->json(['message' => 'Room deleted successfully.']);
    }

    private function formatRoom(Room $room): array
    {
        // Struktur ini dipakai tabel master data agar aset, kategori tiket, dan total laporan tersedia.
        $registeredAssets = $room->assets->map(fn($asset) => [
            'id' => $asset->id,
            'category_id' => $asset->category_id,
            'name' => $asset->category->name ?? '-',
            'quantity' => $asset->quantity,
            'condition' => $asset->condition,
        ]);

        $ticketCategories = $room->tickets
            ->pluck('category')->filter()->unique('id')->values()
            ->map(fn($category) => ['id' => $category->id, 'name' => $category->name]);

        return array_merge($room->only(['id', 'building_id', 'room_number']), [
            'building' => $room->building,
            'registered_assets' => $registeredAssets,
            'categories' => $ticketCategories,
            'total_tickets' => $room->tickets->count(),
        ]);
    }
}
