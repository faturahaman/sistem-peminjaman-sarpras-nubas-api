<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Item;
use Illuminate\Support\Facades\Storage;

class ItemsController extends Controller
{
    public function index()
    {
        $items = Item::withCount([
            'units',
            'units as available_units_count' => fn ($q) => $q->where(
                'status',
                'available',
            ),
        ])->get();

        return response()->json($items);
    }

    public function store(StoreItemRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('items', 'public');
        }

        $item = Item::create($data);

        return response()->json($item, 201);
    }

    public function show(Item $item)
    {
        $item->loadCount([
            'units',
            'units as available_units_count' => fn ($q) => $q->where(
                'status',
                'available',
            ),
        ]);

        return response()->json($item);
    }

    public function update(UpdateItemRequest $request, Item $item)
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($item->photo) {
                Storage::disk('public')->delete($item->photo);
            }
            $data['photo'] = $request->file('photo')->store('items', 'public');
        }

        $item->update($data);

        return response()->json($item->fresh());
    }

    public function destroy(Item $item)
    {
        if ($item->units()->where('status', 'borrowed')->exists()) {
            return response()->json(
                [
                    'message' => 'Item tidak dapat dihapus karena masih memiliki unit yang dipinjam',
                ],
                422,
            );
        }

        // Delete photo if exists
        if ($item->photo) {
            Storage::disk('public')->delete($item->photo);
        }

        // Delete all available units then the item
        $item->units()->delete();
        $item->delete();

        return response()->json(['message' => 'Item berhasil dihapus']);
    }
}
