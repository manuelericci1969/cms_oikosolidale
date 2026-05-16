<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role; // 🔹 importa l'enum dei ruoli
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();

        // 🔑 importante: rimuovi eventuale URL "intesa" salvata in sessione
        $request->session()->forget('url.intended');

        // 1) Se è un AGENTE → area CRM agenti
        if ($user && $user->role instanceof Role && $user->role === Role::Agent) {
            return redirect()->route('agent.crm.dashboard');
        }

        // 2) Se è ADMIN / SUPERADMIN o ha permesso view.admin → area admin
        $goAdmin = $user
            && (
                (method_exists($user, 'hasPermission') && $user->hasPermission('view.admin')) ||
                (method_exists($user, 'isAdmin') && $user->isAdmin())
            );

        $target = $goAdmin
            ? route('admin.dashboard', absolute: false)
            : route('dashboard', absolute: false);

        // 🔄 forza sempre il redirect alla destinazione calcolata
        return redirect()->to($target);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
