<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Company;
use Illuminate\Http\Request;
use App\Models\Settings\Role;
use App\Models\Settings\Permission;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');

        $query = Role::with('permissions');
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }
        $roles = $query->get()->map(function ($role) {
            return [
                'id'          => $role->id,
                'name'        => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray(),
                'users_count' => $role->users()->count(),
            ];
        });

        return response()->json([
            'roles'       => $roles,
            'permissions' => Permission::all(['id', 'name'])->groupBy(function ($p) {
                return explode('.', $p->name)[0] ?? 'general';
            }),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);

        if (!empty($validated['permissions'])) {
            $permNames = Permission::whereIn('id', $validated['permissions'])->pluck('name')->toArray();
            $role->syncPermissions($permNames);
        }

        return response()->json(['success' => true, 'role' => $role->load('permissions')]);
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:100|unique:roles,name,' . $id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update(['name' => $validated['name']]);

        if (isset($validated['permissions'])) {
            $permNames = Permission::whereIn('id', $validated['permissions'])->pluck('name')->toArray();
            $role->syncPermissions($permNames);
        }

        return response()->json(['success' => true, 'role' => $role->fresh()->load('permissions')]);
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json(['success' => true]);
    }

    // Manage permissions list
    public function permissions()
    {
        return response()->json([
            'permissions' => Permission::all(['id', 'name']),
        ]);
    }

    public function storePermission(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:permissions,name',
        ]);

        $permission = Permission::create(['name' => $validated['name'], 'guard_name' => 'web']);

        return response()->json(['success' => true, 'permission' => $permission]);
    }

    public function destroyPermission($id)
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();

        return response()->json(['success' => true]);
    }
}
