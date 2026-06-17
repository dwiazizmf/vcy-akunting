<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Incomes\InvoiceType;
use Illuminate\Http\Request;

class InvoiceTypeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $perPage = (int) $request->input('per_page', 25);
        $page = (int) $request->input('page', 1);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;

        $query = InvoiceType::query();
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $paginator = $query->orderBy('id', 'asc')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'invoiceTypes' => $paginator->items(),
            'pagination'   => [
                'total'       => $paginator->total(),
                'perPage'     => $paginator->perPage(),
                'currentPage' => $paginator->currentPage(),
                'lastPage'    => $paginator->lastPage(),
                'from'        => $paginator->firstItem() ?? 0,
                'to'          => $paginator->lastItem() ?? 0,
            ],
            'filters'      => ['search' => $search, 'per_page' => $perPage],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:invoice_type,name',
        ]);

        $invoiceType = InvoiceType::create($validated);

        return response()->json(['success' => true, 'invoiceType' => $invoiceType]);
    }

    public function update(Request $request, $id)
    {
        $invoiceType = InvoiceType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:invoice_type,name,' . $invoiceType->id,
        ]);

        $invoiceType->update($validated);

        return response()->json(['success' => true, 'invoiceType' => $invoiceType->fresh()]);
    }

    public function destroy($id)
    {
        $invoiceType = InvoiceType::findOrFail($id);
        
        // Prevent deleting if referenced by any invoices
        $usageCount = \Illuminate\Support\Facades\DB::table('invoices')->where('invoice_type_id', $id)->count();
        if ($usageCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tipe invoice tidak dapat dihapus karena sedang digunakan oleh ' . $usageCount . ' invoice.'
            ], 422);
        }

        $invoiceType->delete();

        return response()->json(['success' => true]);
    }
}
