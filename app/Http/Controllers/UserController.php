<?php

// app/Http/Controllers/UserController.php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Permission;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('employee')->get();
        return view('users.index', compact('users'));
    }

    public function edit(User $user)
    {
        $user->load('permissions', 'employee');
        $permissions = Permission::orderBy('key')->get();

        // group for UI
        $groups = $permissions->groupBy(function ($p) {
            return explode('.', $p->key)[0]; // employees / roles / users
        });

        return view('users.edit', compact('user', 'groups'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'string',
        ]);

        $keys = $request->input('permissions', []);

        $permissionIds = Permission::whereIn('key', $keys)->pluck('id')->toArray();
        $user->permissions()->sync($permissionIds);

        return redirect()
            ->route('users.index')
            ->with('success', 'Permissions updated successfully.');
    }
}