<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Room;
use App\Models\Category;
use App\Models\Technician;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BuildingController extends Controller
{
    public function index()
    {
        $data = Cache::remember('public:buildings:list', now()->addMinutes(30), function () {
            return Building::query()
                ->orderBy('name')
                ->get(['id', 'code', 'name']);
        });

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:5|unique:buildings',
            'name' => 'required|string|max:100',
        ]);

        $building = Building::create($request->only('code', 'name'));
        Cache::forget('public:buildings:list');

        return response()->json(['data' => $building], 201);
    }

    public function show(Building $building)
    {
        return response()->json(['data' => $building->load('rooms')]);
    }

    public function update(Request $request, Building $building)
    {
        $request->validate([
            'code' => 'required|string|max:5|unique:buildings,code,' . $building->id,
            'name' => 'required|string|max:100',
        ]);

        $building->update($request->only('code', 'name'));
        Cache::forget('public:buildings:list');

        return response()->json(['data' => $building]);
    }

    public function destroy(Building $building)
    {
        $building->delete();
        Cache::forget('public:buildings:list');

        return response()->json(['message' => 'Building deleted successfully.']);
    }
}
