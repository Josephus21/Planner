<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Permission;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $companyId = (int) auth()->user()->company_id;

        $users = User::with(['employee' => function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            }])
            ->where('company_id', $companyId)
            ->get();

        return view('users.index', compact('users'));
    }

    public function edit($id)
    {
        $companyId = (int) auth()->user()->company_id;

        $user = User::with(['employee' => function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            }, 'permissions'])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        $permissions = Permission::orderBy('key')->get();

        $groups = $permissions->groupBy(function ($p) {
            return explode('.', $p->key)[0];
        });

        return view('users.edit', compact('user', 'groups'));
    }

    public function update(Request $request, $id)
    {
        $companyId = (int) auth()->user()->company_id;

        $user = User::where('company_id', $companyId)->findOrFail($id);

        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'string',
        ]);

        $keys = $request->input('permissions', []);

        $permissionIds = Permission::whereIn('key', $keys)
            ->pluck('id')
            ->toArray();

        $user->permissions()->sync($permissionIds);

        return redirect()
            ->route('users.index')
            ->with('success', 'Permissions updated successfully.');
    }
}