<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            // In Laravel 12, Request::string() ritorna Stringable -> termina con toString()
            'name'      => $request->string('name')->trim()->toString(),
            'email'     => $request->string('email')->lower()->toString(),
            'password'  => Hash::make($request->input('password')),
            'role'      => Role::User,
            'is_active' => true,
        ]);

        event(new Registered($user)); // se usi email verification, lascialo

        Auth::login($user);

        $goAdmin = $user
            && (
                (method_exists($user, 'hasPermission') && $user->hasPermission('view.admin')) ||
                (method_exists($user, 'isAdmin') && $user->isAdmin())
            );

        $target = $goAdmin
            ? route('admin.dashboard', absolute: false)
            : route('dashboard', absolute: false);

        return redirect()->intended($target);
    }
}
