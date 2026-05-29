<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $perPage = (int) $request->input('per_page', 25);
        $page = (int) $request->input('page', 1);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;

        $query = Company::query();
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('npwp', 'like', "%{$search}%");
            });
        }

        $paginator = $query->orderBy('id', 'asc')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'companies'  => $paginator->items(),
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
            'name'      => 'required|string|max:191',
            'code'      => 'nullable|string|max:20',
            'address'   => 'nullable|string',
            'phone'     => 'nullable|string|max:30',
            'npwp'      => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'logo'      => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('company-logos', 'public');
        }

        $company = Company::create([
            'name'      => $validated['name'],
            'code'      => $validated['code'] ?? null,
            'address'   => $validated['address'] ?? null,
            'phone'     => $validated['phone'] ?? null,
            'npwp'      => $validated['npwp'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'logo_path' => $logoPath,
        ]);

        return response()->json(['success' => true, 'company' => $company]);
    }

    public function update(Request $request, $id)
    {
        $company = Company::findOrFail($id);

        $validated = $request->validate([
            'name'      => 'required|string|max:191',
            'code'      => 'nullable|string|max:20',
            'address'   => 'nullable|string',
            'phone'     => 'nullable|string|max:30',
            'npwp'      => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'logo'      => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $logoPath = $company->logo_path;
        if ($request->hasFile('logo')) {
            // Delete old logo
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $logoPath = $request->file('logo')->store('company-logos', 'public');
        }

        $company->update([
            'name'      => $validated['name'],
            'code'      => $validated['code'] ?? null,
            'address'   => $validated['address'] ?? null,
            'phone'     => $validated['phone'] ?? null,
            'npwp'      => $validated['npwp'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'logo_path' => $logoPath,
        ]);

        return response()->json(['success' => true, 'company' => $company->fresh()]);
    }

    public function destroy($id)
    {
        $company = Company::findOrFail($id);
        if ($company->logo_path) {
            Storage::disk('public')->delete($company->logo_path);
        }
        $company->delete();

        return response()->json(['success' => true]);
    }
}
