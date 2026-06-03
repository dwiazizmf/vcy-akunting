<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Settings\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Settings\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $perPage = (int) $request->input('per_page', 25);
        $page = (int) $request->input('page', 1);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;

        $query = User::with(['roles', 'companies']);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $paginator = $query->orderBy('id', 'asc')->paginate($perPage, ['*'], 'page', $page);

        $usersFormatted = $paginator->getCollection()->map(function ($user) {
            return [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'roles'      => $user->roles->pluck('name')->toArray(),
                'companies'  => $user->companies->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->toArray(),
                'created_at' => $user->created_at?->format('d M Y'),
            ];
        });

        return response()->json([
            'users'      => $usersFormatted,
            'pagination' => [
                'total'       => $paginator->total(),
                'perPage'     => $paginator->perPage(),
                'currentPage' => $paginator->currentPage(),
                'lastPage'    => $paginator->lastPage(),
                'from'        => $paginator->firstItem() ?? 0,
                'to'          => $paginator->lastItem() ?? 0,
            ],
            'filters'    => ['search' => $search, 'per_page' => $perPage],
            'roles'      => Role::all(['id', 'name']),
            'companies'  => Company::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:191',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:8',
            'roles'      => 'nullable|array',
            'roles.*'    => 'exists:roles,id',
            'companies'  => 'nullable|array',
            'companies.*' => 'exists:companies,id',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        if (!empty($validated['roles'])) {
            $roleNames = Role::whereIn('id', $validated['roles'])->pluck('name')->toArray();
            $user->syncRoles($roleNames);
        }

        if (!empty($validated['companies'])) {
            $user->companies()->sync($validated['companies']);
        }

        return response()->json(['success' => true, 'user' => $user->load('roles', 'companies')]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'       => 'required|string|max:191',
            'email'      => 'required|email|unique:users,email,' . $id,
            'password'   => 'nullable|string|min:8',
            'roles'      => 'nullable|array',
            'roles.*'    => 'exists:roles,id',
            'companies'  => 'nullable|array',
            'companies.*' => 'exists:companies,id',
        ]);

        $data = [
            'name'  => $validated['name'],
            'email' => $validated['email'],
        ];
        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }
        $user->update($data);

        if (isset($validated['roles'])) {
            $roleNames = Role::whereIn('id', $validated['roles'])->pluck('name')->toArray();
            $user->syncRoles($roleNames);
        }

        if (isset($validated['companies'])) {
            $user->companies()->sync($validated['companies']);
        }

        return response()->json(['success' => true, 'user' => $user->fresh()->load('roles', 'companies')]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->companies()->detach();
        $user->syncRoles([]);
        $user->delete();

        return response()->json(['success' => true]);
    }
}
