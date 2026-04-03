<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Room;
use App\Models\Category;
use App\Models\Technician;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    public function index()
    {
        return response()->json(Building::all(['id', 'code', 'name']));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:5|unique:buildings',
            'name' => 'required|string|max:100',
        ]);

        $building = Building::create($request->only('code', 'name'));

        return response()->json(['data' => $building], 201);
    }

    public function update(Request $request, Building $building)
    {
        $request->validate([
            'code' => 'required|string|max:5|unique:buildings,code,' . $building->id,
            'name' => 'required|string|max:100',
        ]);

        $building->update($request->only('code', 'name'));

        return response()->json(['data' => $building]);
    }

    public function destroy(Building $building)
    {
        $building->delete();

        return response()->json(['message' => 'Building deleted successfully.']);
    }
}
