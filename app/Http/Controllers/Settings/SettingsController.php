<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Company;
use App\Models\Settings\InvoiceSetting;
use App\Models\Settings\Tax;
use App\Models\Settings\Discount;
use App\Models\User;
use App\Models\Incomes\InvoiceType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Settings\Permission;
use App\Models\Settings\Role;
use App\Models\Account;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'companies');

        // Companies tab initial data
        $companies = Company::orderBy('id', 'asc')->paginate(25);

        // Users tab initial data
        $users = User::with(['roles', 'companies'])->orderBy('id', 'asc')->paginate(25);
        $usersFormatted = $users->getCollection()->map(function ($user) {
            return [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'roles'      => $user->roles->pluck('name')->toArray(),
                'companies'  => $user->companies->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->toArray(),
                'created_at' => $user->created_at?->format('d M Y'),
            ];
        });

        // Roles
        $roles = Role::with('permissions')->get()->map(function ($role) {
            return [
                'id'          => $role->id,
                'name'        => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray(),
                'users_count' => $role->users()->count(),
            ];
        });

        // Taxes tab initial data
        $taxes = Tax::orderBy('id', 'asc')->paginate(25);

        // Discounts tab initial data
        $discounts = Discount::orderBy('id', 'asc')->paginate(25);

        // Invoice Types initial data
        $invoiceTypes = InvoiceType::orderBy('id', 'asc')->paginate(25);

        // Invoice settings
        $invoiceSetting = InvoiceSetting::getSetting();

        $companyId = session('company_id') ?: Company::where('enabled', 1)->first()?->id;

        return Inertia::render('Settings/Index', [
            'activeTab'       => $tab,
            'initialCompanies' => [
                'data'       => $companies->items(),
                'pagination' => [
                    'total'       => $companies->total(),
                    'perPage'     => $companies->perPage(),
                    'currentPage' => $companies->currentPage(),
                    'lastPage'    => $companies->lastPage(),
                    'from'        => $companies->firstItem() ?? 0,
                    'to'          => $companies->lastItem() ?? 0,
                ],
            ],
            'initialUsers' => [
                'data'       => $usersFormatted->toArray(),
                'pagination' => [
                    'total'       => $users->total(),
                    'perPage'     => $users->perPage(),
                    'currentPage' => $users->currentPage(),
                    'lastPage'    => $users->lastPage(),
                    'from'        => $users->firstItem() ?? 0,
                    'to'          => $users->lastItem() ?? 0,
                ],
            ],
            'initialRoles'        => $roles->toArray(),
            'allPermissions'      => Permission::all(['id', 'name'])->toArray(),
            'allCompaniesForForm' => Company::where('enabled', true)->get(['id', 'name'])->toArray(),
            'initialTaxes' => [
                'data'       => $taxes->items(),
                'pagination' => [
                    'total'       => $taxes->total(),
                    'perPage'     => $taxes->perPage(),
                    'currentPage' => $taxes->currentPage(),
                    'lastPage'    => $taxes->lastPage(),
                    'from'        => $taxes->firstItem() ?? 0,
                    'to'          => $taxes->lastItem() ?? 0,
                ],
            ],
            'initialDiscounts' => [
                'data'       => $discounts->items(),
                'pagination' => [
                    'total'       => $discounts->total(),
                    'perPage'     => $discounts->perPage(),
                    'currentPage' => $discounts->currentPage(),
                    'lastPage'    => $discounts->lastPage(),
                    'from'        => $discounts->firstItem() ?? 0,
                    'to'          => $discounts->lastItem() ?? 0,
                ],
            ],
            'invoiceSetting' => $invoiceSetting,
            'initialInvoiceTypes' => [
                'data'       => $invoiceTypes->items(),
                'pagination' => [
                    'total'       => $invoiceTypes->total(),
                    'perPage'     => $invoiceTypes->perPage(),
                    'currentPage' => $invoiceTypes->currentPage(),
                    'lastPage'    => $invoiceTypes->lastPage(),
                    'from'        => $invoiceTypes->firstItem() ?? 0,
                    'to'          => $invoiceTypes->lastItem() ?? 0,
                ],
            ],
            'accounts' => \App\Helpers\AccountHelper::formatAccountsList(Account::where('company_id', $companyId)
                ->where('code', 'not like', '12%')
                ->where('enabled', 1)
                ->get(['id', 'code', 'name', 'parent_id'])),
        ]);
    }
}
