<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Accounting\Account;
use App\Models\Accounting\AccountType;
use Inertia\Inertia;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('company_id') ?: 1;
        
        $categories = AccountType::select('category')->distinct()->pluck('category')->toArray();
        $category = $request->input('category') ?: ($categories[0] ?? 'Asset');

        $query = Account::with(['type', 'parent', 'company'])
            ->when($companyId !== 'all', function ($q) use ($companyId) {
                return $q->where('company_id', $companyId);
            })
            ->whereHas('type', function($q) use ($category) {
                $q->where('category', $category);
            });
            
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 25);
        $paginator = $query->orderBy('code')->paginate($perPage)->withQueryString();
        
        $items = $paginator->map(function ($account) {
            return [
                'id'           => $account->id,
                'code'         => $account->code,
                'name'         => $account->name,
                'type_name'    => $account->type?->name ?? '-',
                'type_id'      => $account->type_id,
                'parent_name'  => $account->parent?->name ?? '-',
                'parent_id'    => $account->parent_id,
                'description'  => $account->description,
                'company_name' => $account->company?->name ?? '-',
                'enabled'      => $account->enabled,
                'system'       => $account->system,
            ];
        });
        
        $types = AccountType::all();
        $parentAccounts = Account::where('company_id', $companyId)->orderBy('code')->get();

        return Inertia::render('Accounting/Accounts/Index', [
            'accounts' => $items,
            'pagination' => [
                'total'       => $paginator->total(),
                'perPage'     => $paginator->perPage(),
                'currentPage' => $paginator->currentPage(),
                'lastPage'    => $paginator->lastPage(),
                'from'        => $paginator->firstItem() ?? 0,
                'to'          => $paginator->lastItem() ?? 0,
            ],
            'categories' => $categories,
            'types' => $types,
            'parentAccounts' => $parentAccounts,
            'filters' => [
                'search' => $request->input('search', ''),
                'category' => $category,
                'per_page' => $perPage,
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
