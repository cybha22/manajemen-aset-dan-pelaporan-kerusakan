<?php

namespace App\Http\Controllers;

use App\Models\RoomAsset;
use Illuminate\Http\Request;

class RoomAssetController extends Controller
{
    public function index(Request $request)
    {
        $assets = RoomAsset::with('category')
            ->where('room_id', $request->room_id)
            ->get();

        return response()->json($assets);
    }

    public function store(Request $request)
    {
        $request->validate([
            'room_id'     => 'required|exists:rooms,id',
            'category_id' => 'required|exists:categories,id',
            'quantity'    => 'required|integer|min:1|max:999',
            'condition'   => 'required|in:Baik,Rusak Ringan,Rusak Berat',
        ]);

        $asset = RoomAsset::updateOrCreate(
            ['room_id' => $request->room_id, 'category_id' => $request->category_id],
            ['quantity' => $request->quantity, 'condition' => $request->condition]
        );

        return response()->json(['data' => $asset->load('category')], 201);
    }

    public function update(Request $request, RoomAsset $roomAsset)
    {
        $request->validate([
            'quantity'  => 'required|integer|min:1|max:999',
            'condition' => 'required|in:Baik,Rusak Ringan,Rusak Berat',
        ]);

        $roomAsset->update($request->only('quantity', 'condition'));

        return response()->json(['data' => $roomAsset->load('category')]);
    }

    public function destroy(RoomAsset $roomAsset)
    {
        $roomAsset->delete();

        return response()->json(['message' => 'Aset berhasil dihapus.']);
    }
}
