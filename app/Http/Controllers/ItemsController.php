<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Item;

class ItemsController extends Controller
{
    public function index()
    {
        $items = Item::withCount([
            'units',
            'units as available_units_count' => fn ($q) => $q->where('status', 'available'),
        ])->get();

        return response()->json($items);
    }

    public function store(StoreItemRequest $request)
    {
        $item = Item::create($request->validated());

        return response()->json($item, 201);
    }

    public function show(Item $item)
    {
        $item->loadCount([
            'units',
            'units as available_units_count' => fn ($q) => $q->where('status', 'available'),
        ]);

        return response()->json($item);
    }

    public function update(UpdateItemRequest $request, Item $item)
    {
        $item->update($request->validated());

        return response()->json($item->fresh());
    }

    public function destroy(Item $item)
    {
        if ($item->units()->where('status', 'borrowed')->exists()) {
            return response()->json([
                'message' => 'Item tidak dapat dihapus karena masih memiliki unit yang dipinjam',
            ], 422);
        }

        // Delete all available units then the item
        $item->units()->delete();
        $item->delete();

        return response()->json(['message' => 'Item berhasil dihapus']);
    }
}
