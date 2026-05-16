<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('admin.profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $data = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'           => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
            'whatsapp_opt_in' => ['nullable', 'boolean'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $this->normalizePhone($data['phone'] ?? null);
        $user->whatsapp_opt_in = (bool) ($data['whatsapp_opt_in'] ?? false);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('ok', 'Profilo aggiornato correttamente.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password'      => ['required', 'string'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:8'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()
                ->withErrors([
                    'current_password' => 'La password attuale non è corretta.',
                ])
                ->withInput($request->except('current_password', 'password', 'password_confirmation'));
        }

        $user->password = Hash::make($request->input('password'));
        $user->save();

        return back()->with('ok', 'Password aggiornata correttamente.');
    }

    /**
     * Normalize phone number for storage.
     */
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
