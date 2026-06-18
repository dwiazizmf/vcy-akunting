<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $perPage = (int) $request->input('per_page', 25);
        $page = (int) $request->input('page', 1);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;

        $query = Discount::query();
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $paginator = $query->orderBy('id', 'asc')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'discounts'  => $paginator->items(),
            'pagination' => [
                'total'       => $paginator->total(),
                'perPage'     => $paginator->perPage(),
                'currentPage' => $paginator->currentPage(),
                'lastPage'    => $paginator->lastPage(),
                'from'        => $paginator->firstItem() ?? 0,
                'to'          => $paginator->lastItem() ?? 0,
            ],
            'filters' => ['search' => $search, 'per_page' => $perPage],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'rate'        => 'required|numeric|min:0|max:999.99',
            'type'        => 'required|in:percentage,fixed',
            'description' => 'nullable|string',
            'enabled'     => 'boolean',
        ]);

        $discount = Discount::create($validated);

        return response()->json(['success' => true, 'discount' => $discount]);
    }

    public function update(Request $request, $id)
    {
        $discount = Discount::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'rate'        => 'required|numeric|min:0|max:999.99',
            'type'        => 'required|in:percentage,fixed',
            'description' => 'nullable|string',
            'enabled'     => 'boolean',
        ]);

        $discount->update($validated);

        return response()->json(['success' => true, 'discount' => $discount->fresh()]);
    }

    public function destroy($id)
    {
        $discount = Discount::findOrFail($id);
        $discount->delete();

        return response()->json(['success' => true]);
    }
}
