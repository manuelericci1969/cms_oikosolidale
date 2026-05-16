<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function index()
    {
        $permissions = Permission::orderBy('name')->get();
        $byRole = [
            Role::Admin->value => DB::table('role_permissions')->where('role', Role::Admin->value)->pluck('permission_id')->all(),
            // SuperAdmin ha tutto by-code; mostriamo solo a scopo informativo
        ];
        return view('admin.roles.index', compact('permissions','byRole'));
    }

    public function sync(Request $request)
    {
        $data = $request->validate([
            'role'        => ['required','in:admin'], // gestiamo 'admin' (superadmin non modificabile)
            'permissions' => ['array'],
            'permissions.*' => ['integer','exists:permissions,id'],
        ]);

        DB::transaction(function () use ($data) {
            DB::table('role_permissions')->where('role', $data['role'])->delete();
            foreach ($data['permissions'] ?? [] as $permId) {
                DB::table('role_permissions')->insert(['role'=>$data['role'],'permission_id'=>$permId, 'created_at'=>now(),'updated_at'=>now()]);
            }
        });

        return back()->with('ok', 'Permessi del ruolo aggiornati');
    }
}
