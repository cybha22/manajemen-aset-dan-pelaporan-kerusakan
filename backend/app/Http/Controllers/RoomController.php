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

        $rooms = $query->get()->map(function ($room) {
            $registeredAssets = $room->assets->map(fn($a) => [
                'id'          => $a->id,
                'category_id' => $a->category_id,
                'name'        => $a->category->name ?? '-',
                'quantity'    => $a->quantity,
                'condition'   => $a->condition,
            ]);

            $ticketCategories = $room->tickets
                ->pluck('category')->filter()->unique('id')->values()
                ->map(fn($c) => ['id' => $c->id, 'name' => $c->name]);

            return array_merge($room->only(['id', 'building_id', 'room_number']), [
                'building'         => $room->building,
                'registered_assets' => $registeredAssets,
                'categories'       => $ticketCategories,
                'total_tickets'    => $room->tickets->count(),
            ]);
        });

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
}
