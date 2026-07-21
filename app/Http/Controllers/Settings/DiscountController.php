<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Discount;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $perPage = (int) $request->input('per_page', 25);
        $page = (int) $request->input('page', 1);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;

        $query = Discount::with('account');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $paginator = $query->orderBy('id', 'asc')->paginate($perPage, ['*'], 'page', $page);

        // Format to include account info
        $discounts = $paginator->getCollection()->map(function ($discount) {
            $data = $discount->toArray();
            $data['account_name'] = $discount->account ? $discount->account->code . ' - ' . $discount->account->name : '-';
            return $data;
        });

        return response()->json([
            'discounts'  => $discounts,
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
            'type'        => 'required|in:percentage,fixed',
            'rate'        => 'required|numeric|min:0|max:' . ($request->input('type') === 'percentage' ? '100' : '999999999999.99'),
            'account_id'  => 'nullable|integer|exists:accounts,id',
            'description' => 'nullable|string',
            'enabled'     => 'boolean',
        ]);

        $companyId = session('company_id') ?: (\App\Models\Settings\Company::where('enabled', 1)->first()?->id ?? 0);
        $validated['company_id'] = $companyId;

        $discount = Discount::create($validated);

        return response()->json(['success' => true, 'discount' => $discount]);
    }

    public function update(Request $request, $id)
    {
        $discount = Discount::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'type'        => 'required|in:percentage,fixed',
            'rate'        => 'required|numeric|min:0|max:' . ($request->input('type') === 'percentage' ? '100' : '999999999999.99'),
            'account_id'  => 'nullable|integer|exists:accounts,id',
            'description' => 'nullable|string',
            'enabled'     => 'boolean',
        ]);

        $discount->update($validated);

        return response()->json(['success' => true, 'discount' => $discount->fresh('account')]);
    }

    public function destroy($id)
    {
        $discount = Discount::findOrFail($id);
        $discount->delete();

        return response()->json(['success' => true]);
    }
}
