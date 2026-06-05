<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Account;
use App\Models\Settings\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BankAccountController extends Controller
{
    public function index()
    {
        $companyId = session('company_id') ?: Company::where('enabled', 1)->first()?->id;

        $banks = BankAccount::where('company_id', $companyId)->with('account')->get()->map(fn($b) => [
            'id'             => $b->id,
            'name'           => $b->name,
            'type'           => $b->type,
            'bank_name'      => $b->bank_name,
            'account_number' => $b->account_number,
            'account_id'     => $b->account_id,
            'account_name'   => $b->account ? "{$b->account->code} - {$b->account->name}" : '-',
            'is_default'     => $b->is_default,
            'enabled'        => $b->enabled,
        ]);

        $accounts = Account::where('company_id', $companyId)
            ->whereHas('type', fn($q) => $q->where('category', 'Asset'))
            ->where('code', 'not like', '12%')
            ->where('enabled', 1)
            ->get(['id', 'code', 'name', 'parent_id']);

        return Inertia::render('Settings/BankAccounts/Index', [
            'banks'    => $banks,
            'accounts' => $accounts,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'type'           => 'required|in:bank,cash',
            'bank_name'      => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:100',
            'account_id'     => 'required|integer|exists:accounts,id',
            'is_default'     => 'boolean',
            'enabled'        => 'boolean',
        ]);

        $companyId = session('company_id') ?: Company::where('enabled', 1)->first()?->id;
        $validated['company_id'] = $companyId;

        // If set as default, unset others
        if (!empty($validated['is_default'])) {
            BankAccount::where('company_id', $companyId)->update(['is_default' => false]);
        }

        BankAccount::create($validated);

        return back()->with('success', 'Bank account berhasil ditambahkan.');
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'type'           => 'required|in:bank,cash',
            'bank_name'      => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:100',
            'account_id'     => 'required|integer|exists:accounts,id',
            'is_default'     => 'boolean',
            'enabled'        => 'boolean',
        ]);

        $companyId = $bankAccount->company_id;

        if (!empty($validated['is_default'])) {
            BankAccount::where('company_id', $companyId)
                ->where('id', '!=', $bankAccount->id)
                ->update(['is_default' => false]);
        }

        $bankAccount->update($validated);

        return back()->with('success', 'Bank account berhasil diperbarui.');
    }

    public function destroy(BankAccount $bankAccount)
    {
        $bankAccount->delete();
        return back()->with('success', 'Bank account berhasil dihapus.');
    }
}
