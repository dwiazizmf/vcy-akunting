<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\Settings\Company;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $activeCompanyId = session('company_id') ?: Company::where('enabled', 1)->first()?->id;
        $activeCompanyPrefix = session('company_prefix');
        
        if (!$activeCompanyPrefix && $activeCompanyId && $activeCompanyId !== 'all') {
            $activeCompanyPrefix = Company::find($activeCompanyId)?->prefix;
            if ($activeCompanyPrefix) {
                session(['company_prefix' => $activeCompanyPrefix]);
            }
        }

        return [
            ...parent::share($request),
            'companies' => Company::where('enabled', 1)->get(['id', 'name', 'enabled', 'prefix']),
            'active_company_id' => $activeCompanyId,
            'active_company_prefix' => $activeCompanyPrefix,
        ];
    }
}
