<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Settings\PaymentCategory;
use App\Models\Settings\PaymentLimit;
use App\Models\Account;
use App\Models\Settings\Company;

class PaymentLimitController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'categories'); // 'categories' or 'limits'
        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        $search = $request->input('search');

        $emptyPaginator = [
            'data' => [],
            'total' => 0,
            'perPage' => $perPage,
            'currentPage' => 1,
            'lastPage' => 1,
            'from' => 0,
            'to' => 0
        ];
        $categories = $emptyPaginator;
        $limits = $emptyPaginator;

        if ($tab === 'categories') {
            $categoriesQuery = PaymentCategory::query()
                ->when($search, function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            $paginator = $categoriesQuery->orderBy('id', 'desc')->paginate($perPage);
            $categories = [
                'data' => $paginator->items(),
                'total' => $paginator->total(),
                'perPage' => $paginator->perPage(),
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'from' => $paginator->firstItem() ?: 0,
                'to' => $paginator->lastItem() ?: 0,
            ];
        } else {
            $limitsQuery = PaymentLimit::with(['account', 'paymentCategory'])
                ->when($search, function ($q) use ($search) {
                    $q->whereHas('account', function($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                           ->orWhere('code', 'like', "%{$search}%");
                    })->orWhereHas('paymentCategory', function($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
                });
            $paginator = $limitsQuery->orderBy('id', 'desc')->paginate($perPage);
            $limits = [
                'data' => $paginator->items(),
                'total' => $paginator->total(),
                'perPage' => $paginator->perPage(),
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'from' => $paginator->firstItem() ?: 0,
                'to' => $paginator->lastItem() ?: 0,
            ];
        }

        return Inertia::render('Settings/PaymentLimits', [
            'tab' => $tab,
            'categories' => $categories,
            'limits' => $limits,
            'allCategories' => PaymentCategory::all(),
            'accounts' => Account::where('enabled', true)->get(),
            'filters' => [
                'search' => $search ?? '',
                'per_page' => $perPage
            ]
        ]);
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:payment_categories,code',
            'name' => 'required|string',
        ]);

        PaymentCategory::create($validated);
        return back()->with('success', 'Payment Category created successfully.');
    }

    public function updateCategory(Request $request, PaymentCategory $category)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:payment_categories,code,' . $category->id,
            'name' => 'required|string',
        ]);

        $category->update($validated);
        return back()->with('success', 'Payment Category updated successfully.');
    }

    public function destroyCategory(PaymentCategory $category)
    {
        if ($category->limits()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete category in use by limits.']);
        }
        $category->delete();
        return back()->with('success', 'Payment Category deleted successfully.');
    }

    public function storeLimit(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'payment_category_id' => 'required|exists:payment_categories,id',
            'limit_amount' => 'required|numeric|min:0',
        ]);

        $companyId = session('company_id') ?: (Company::where('enabled', 1)->first()?->id ?? 1);
        $validated['company_id'] = $companyId;

        // Check duplicate
        $exists = PaymentLimit::where('account_id', $validated['account_id'])
            ->where('payment_category_id', $validated['payment_category_id'])
            ->exists();
            
        if ($exists) {
            return back()->withErrors(['error' => 'A limit for this account and category already exists.']);
        }

        PaymentLimit::create($validated);
        return back()->with('success', 'Payment Limit created successfully.');
    }

    public function updateLimit(Request $request, PaymentLimit $limit)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'payment_category_id' => 'required|exists:payment_categories,id',
            'limit_amount' => 'required|numeric|min:0',
        ]);

        // Check duplicate on update
        $exists = PaymentLimit::where('account_id', $validated['account_id'])
            ->where('payment_category_id', $validated['payment_category_id'])
            ->where('id', '!=', $limit->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['error' => 'A limit for this account and category already exists.']);
        }

        $limit->update($validated);
        return back()->with('success', 'Payment Limit updated successfully.');
    }

    public function destroyLimit(PaymentLimit $limit)
    {
        $limit->delete();
        return back()->with('success', 'Payment Limit deleted successfully.');
    }
}
