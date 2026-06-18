<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Accounting\Accounting\Account;
use App\Models\Accounting\Accounting\AccountType;
use Inertia\Inertia;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('company_id') ?: 1;
        
        $query = Account::with(['type', 'parent'])
            ->where('company_id', $companyId);
            
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type_id')) {
            $query->where('type_id', $request->input('type_id'));
        }

        $accounts = $query->orderBy('code')->paginate($request->input('per_page', 25))->withQueryString();
        
        $types = AccountType::all();
        $parentAccounts = Account::where('company_id', $companyId)->orderBy('code')->get();

        return Inertia::render('Accounting/Accounts/Index', [
            'accounts' => $accounts,
            'types' => $types,
            'parentAccounts' => $parentAccounts,
            'filters' => [
                'search' => $request->input('search', ''),
                'type_id' => $request->input('type_id', '')
            ]
        ]);
    }

    public function store(Request $request)
    {
        $companyId = session('company_id') ?: 1;

        $validated = $request->validate([
            'type_id' => 'required|exists:account_types,id',
            'parent_id' => 'nullable|exists:accounts,id',
            'code' => 'required|string|unique:accounts,code,NULL,id,company_id,' . $companyId,
            'name' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $validated['company_id'] = $companyId;
        $validated['system'] = false;
        $validated['enabled'] = true;

        Account::create($validated);

        return back()->with('success', 'Akun berhasil dibuat.');
    }

    public function update(Request $request, Account $account)
    {
        if ($account->system) {
            $validated = $request->validate([
                'name' => 'required|string',
                'description' => 'nullable|string',
                'enabled' => 'boolean'
            ]);
        } else {
            $validated = $request->validate([
                'type_id' => 'required|exists:account_types,id',
                'parent_id' => 'nullable|exists:accounts,id',
                'code' => 'required|string|unique:accounts,code,' . $account->id . ',id,company_id,' . $account->company_id,
                'name' => 'required|string',
                'description' => 'nullable|string',
                'enabled' => 'boolean'
            ]);
        }

        $account->update($validated);

        return back()->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(Account $account)
    {
        if ($account->system) {
            return back()->with('error', 'Akun sistem tidak dapat dihapus.');
        }

        if ($account->ledgers()->exists()) {
            return back()->with('error', 'Akun tidak dapat dihapus karena sudah memiliki transaksi.');
        }

        if ($account->children()->exists()) {
            return back()->with('error', 'Akun tidak dapat dihapus karena memiliki sub-akun.');
        }

        $account->delete();

        return back()->with('success', 'Akun berhasil dihapus.');
    }
}
