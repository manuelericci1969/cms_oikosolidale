<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    /**
     * Pagina account personale (cambio password, ecc.)
     */
    public function edit(Request $request)
    {
        $user = $request->user(); // utente loggato (admin / superadmin)

        return view('admin.account.edit', compact('user'));
    }

    /**
     * Cambio password dell'utente loggato
     */
    public function updatePassword(Request $request)
    {
        // Validazione
        $request->validate([
            'current_password'      => ['required', 'string'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:8'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        // Verifica password attuale
        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()
                ->withErrors([
                    'current_password' => 'La password attuale non è corretta.',
                ])
                ->withInput($request->except('current_password', 'password', 'password_confirmation'));
        }

        // Aggiorna password
        $user->password = Hash::make($request->input('password'));
        $user->save();

        // (Opzionale) disconnette le altre sessioni dell’utente
        // auth()->logoutOtherDevices($request->input('password'));

        return back()->with('ok', 'Password aggiornata correttamente.');
    }
}
