<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q     = (string) $request->query('q', '');
        $role  = (string) $request->query('role', '');
        $state = $request->query('state');

        $users = User::query()
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%$q%")
                        ->orWhere('email', 'like', "%$q%")
                        ->orWhere('phone', 'like', "%$q%");
                });
            })
            ->when($role !== '', fn($qb) => $qb->where('role', $role))
            ->when($state !== null && $state !== '', fn($qb) => $qb->where('is_active', (bool) $state))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'q', 'role', 'state'));
    }

    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer', 'exists:users,id'],
            'action'=> ['required', 'string', 'in:activate,deactivate,set_admin,set_user,set_superadmin,set_agent'],
        ]);

        $ids = $data['ids'];

        match ($data['action']) {
            'activate'       => User::whereIn('id', $ids)->update(['is_active' => true]),
            'deactivate'     => tap(User::whereIn('id', $ids)->update(['is_active' => false]), function () use ($ids) {
                $this->forceLogoutUserSessions($ids);
            }),
            'set_admin'      => User::whereIn('id', $ids)->update(['role' => Role::Admin->value]),
            'set_user'       => User::whereIn('id', $ids)->update(['role' => Role::User->value]),
            'set_superadmin' => User::whereIn('id', $ids)->update(['role' => Role::SuperAdmin->value]),
            'set_agent'      => User::whereIn('id', $ids)->update(['role' => Role::Agent->value]),
        };

        if ($data['action'] === 'deactivate' && in_array(auth()->id(), $ids, true)) {
            return redirect()->route('login');
        }

        return back()->with('ok', 'Azione massiva completata');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'role'             => ['required', 'in:' . implode(',', Role::values())],
            'is_active'        => ['required', 'boolean'],
            'phone'            => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
            'whatsapp_opt_in'  => ['nullable', 'boolean'],
        ]);

        $wasActive = (bool) $user->is_active;

        $user->role = Role::from($data['role']);
        $user->is_active = (bool) $data['is_active'];
        $user->phone = $this->normalizePhone($data['phone'] ?? null);
        $user->whatsapp_opt_in = (bool) ($data['whatsapp_opt_in'] ?? false);
        $user->save();

        if ($wasActive && !$user->is_active) {
            $this->forceLogoutUserSessions([$user->id]);

            if (auth()->id() === $user->id) {
                return redirect()->route('login')->with('status', 'Account disattivato: sei stato disconnesso.');
            }
        }

        return back()->with('ok', 'Utente aggiornato');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Non puoi eliminare il tuo stesso account!');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('ok', "Utente {$user->name} eliminato!");
    }

    public function editPermissions(User $user)
    {
        $allPermissions = Permission::orderBy('name')->get();
        return view('admin.users.permissions', compact('user', 'allPermissions'));
    }

    public function syncPermissions(Request $request, User $user)
    {
        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $permissionNames = $validated['permissions'] ?? [];
        $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id');

        $user->permissions()->sync($permissionIds);

        return redirect()
            ->route('admin.users.index')
            ->with('ok', "Permessi aggiornati per {$user->name}!");
    }

    public function clearPermissions(User $user)
    {
        $user->permissions()->detach();
        return back()->with('ok', "Permessi extra rimossi per {$user->name}!");
    }

    private function forceLogoutUserSessions(array $userIds): void
    {
        User::whereIn('id', $userIds)->update(['remember_token' => null]);

        if (Config::get('session.driver') === 'database') {
            DB::table(Config::get('session.table', 'sessions'))
                ->whereIn('user_id', $userIds)
                ->delete();
        }
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $phone = trim($phone);

        if ($phone === '') {
            return null;
        }

        return preg_replace('/[^\d+]/', '', $phone);
    }
}
