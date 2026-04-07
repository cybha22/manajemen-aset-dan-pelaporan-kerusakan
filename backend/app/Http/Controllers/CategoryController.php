<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function index()
    {
        $data = Cache::remember('public:categories:list', now()->addMinutes(30), function () {
            return Category::query()
                ->orderBy('name')
                ->get(['id', 'name', 'description']);
        });

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:categories',
            'description' => 'nullable|string|max:255',
        ]);

        $category = Category::create($request->only('name', 'description'));
        Cache::forget('public:categories:list');

        return response()->json(['data' => $category], 201);
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:categories,name,' . $category->id,
            'description' => 'nullable|string|max:255',
        ]);

        $category->update($request->only('name', 'description'));
        Cache::forget('public:categories:list');

        return response()->json(['data' => $category]);
    }

    public function destroy(Category $category)
    {
        $category->delete();
        Cache::forget('public:categories:list');

        return response()->json(['message' => 'Category deleted successfully.']);
    }
}
