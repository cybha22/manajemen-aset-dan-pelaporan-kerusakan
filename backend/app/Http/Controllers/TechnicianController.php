<?php

namespace App\Http\Controllers;

use App\Models\Technician;
use Illuminate\Http\Request;

class TechnicianController extends Controller
{
    public function index()
    {
        return response()->json(Technician::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'telegram_chat_id' => 'required|string|unique:technicians',
            'phone' => 'required|string',
        ]);

        $technician = Technician::create(array_merge(
            $request->only('name', 'telegram_chat_id', 'phone'),
            ['status' => 'aktif']
        ));

        return response()->json(['data' => $technician], 201);
    }

    public function show(Technician $technician)
    {
        return response()->json(['data' => $technician->load('tickets')]);
    }

    public function update(Request $request, Technician $technician)
    {
        $request->validate([
            'name' => 'sometimes|string|max:100',
            'phone' => 'sometimes|string',
            'status' => 'sometimes|in:aktif,nonaktif',
        ]);

        $technician->update($request->only('name', 'phone', 'status'));

        return response()->json(['data' => $technician]);
    }

    public function destroy(Technician $technician)
    {
        $technician->delete();

        return response()->json(['message' => 'Technician deleted successfully.']);
    }
}
