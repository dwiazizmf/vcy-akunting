<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Tax;
use Illuminate\Http\Request;

class TaxController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $perPage = (int) $request->input('per_page', 25);
        $page = (int) $request->input('page', 1);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;

        $query = Tax::query();
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $paginator = $query->orderBy('id', 'asc')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'taxes'      => $paginator->items(),
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
            'account_id'  => 'nullable|integer|exists:accounts,id',
            'description' => 'nullable|string',
            'enabled'   => 'boolean',
        ]);

        $companyId = session('company_id') ?: (\App\Models\Settings\Company::where('enabled', 1)->first()?->id ?? 0);
        $validated['company_id'] = $companyId;

        $tax = Tax::create($validated);

        return response()->json(['success' => true, 'tax' => $tax]);
    }

    public function update(Request $request, $id)
    {
        $tax = Tax::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'rate'        => 'required|numeric|min:0|max:999.99',
            'type'        => 'required|in:percentage,fixed',
            'account_id'  => 'nullable|integer|exists:accounts,id',
            'description' => 'nullable|string',
            'enabled'   => 'boolean',
        ]);

        $tax->update($validated);

        return response()->json(['success' => true, 'tax' => $tax->fresh()]);
    }

    public function destroy($id)
    {
        $tax = Tax::findOrFail($id);
        $tax->delete();

        return response()->json(['success' => true]);
    }
}
