<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::orderBy('name')->get();
        return view('admin.permissions.index', compact('permissions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required','string','max:100','unique:permissions,name'],
            'description' => ['nullable','string','max:255'],
        ]);

        Permission::create($data);
        return back()->with('ok', 'Permesso creato');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return back()->with('ok', 'Permesso eliminato');
    }
}
