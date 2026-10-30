<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login', [
            'registrationEnabled' => SystemSetting::booleanFor(SystemSetting::REGISTRATION_ENABLED),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $this->normalizeEmail($request);

        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister(): View|RedirectResponse
    {
        if (! SystemSetting::booleanFor(SystemSetting::REGISTRATION_ENABLED)) {
            return redirect()->route('register.closed');
        }

        return view('auth.register');
    }

    public function showRegistrationClosed(): View|RedirectResponse
    {
        if (SystemSetting::booleanFor(SystemSetting::REGISTRATION_ENABLED)) {
            return redirect()->route('register');
        }

        return view('auth.registration-closed');
    }

    public function register(Request $request): RedirectResponse
    {
        if (! SystemSetting::booleanFor(SystemSetting::REGISTRATION_ENABLED)) {
            return redirect()->route('register.closed');
        }

        $this->normalizeEmail($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $projectOwnerRole = Role::query()
                ->where('slug', Role::PROJECT_OWNER_SLUG)
                ->firstOrFail();
            $user = User::query()->create($validated);
            $user->roles()->attach($projectOwnerRole->getKey());

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function normalizeEmail(Request $request): void
    {
        $email = $request->input('email');

        if (is_string($email)) {
            $request->merge(['email' => Str::lower(trim($email))]);
        }
    }
}
