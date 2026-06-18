<?php

namespace App\Http\Controllers\Expenses;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Expenses\Vendor;
use App\Models\Accounting\Accounting\Account;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $search = $request->input('search');

        $query = Vendor::with('account')
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('vendor_code', 'like', "%{$search}%");
            });

        $paginator = $query->orderBy('id', 'desc')->paginate($perPage);

        $pagination = [
            'total'       => $paginator->total(),
            'perPage'     => $paginator->perPage(),
            'currentPage' => $paginator->currentPage(),
            'lastPage'    => $paginator->lastPage(),
            'from'        => $paginator->firstItem() ?: 0,
            'to'          => $paginator->lastItem() ?: 0,
        ];

        return Inertia::render('Expenses/Vendors/Index', [
            'vendors' => $paginator->items(),
            'pagination' => $pagination,
            'filters' => [
                'search' => $search ?? '',
                'per_page' => $perPage
            ]
        ]);
    }

    public function create()
    {
        $accounts = Account::where('enabled', true)->get();
        return Inertia::render('Expenses/Vendors/Form', [
            'vendor' => new Vendor(),
            'accounts' => $accounts,
            'isEdit' => false
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_code' => 'nullable|string|max:50',
            'name' => 'required|string|max:191',
            'npwp' => 'nullable|string|max:191',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:191',
            'account_id' => 'nullable|exists:accounts,id'
        ]);

        $companyId = session('company_id') ?: (\App\Models\Settings\Company::where('enabled', 1)->first()?->id ?? 1);
        $validated['company_id'] = $companyId;

        $vendor = Vendor::create($validated);

        return redirect()->route('vendors.index')->with('success', 'Vendor created successfully.');
    }

    public function edit(Vendor $vendor)
    {
        $accounts = Account::where('enabled', true)->get();
        return Inertia::render('Expenses/Vendors/Form', [
            'vendor' => $vendor,
            'accounts' => $accounts,
            'isEdit' => true
        ]);
    }

    public function update(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'vendor_code' => 'nullable|string|max:50',
            'name' => 'required|string|max:191',
            'npwp' => 'nullable|string|max:191',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:191',
            'account_id' => 'nullable|exists:accounts,id'
        ]);

        $vendor->update($validated);

        return redirect()->route('vendors.index')->with('success', 'Vendor updated successfully.');
    }

    public function destroy(Vendor $vendor)
    {
        $vendor->delete();
        return redirect()->route('vendors.index')->with('success', 'Vendor deleted successfully.');
    }
}
