<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Label;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function redirectToIdentityProvider(): RedirectResponse
    {
        return Socialite::driver('hive')->redirect();
    }

    public function handleIdentityProviderCallback(Request $request): RedirectResponse
    {
        if ($request->filled('error')) {
            return redirect()->route('logout.complete')->with('auth-error', 'Hive Hub sign-in was not completed.');
        }

        $hiveUser = Socialite::driver('hive')->user();
        $profile = Validator::make([
            'id' => $hiveUser->getId(),
            'name' => $hiveUser->getName(),
            'email' => $hiveUser->getEmail(),
        ], [
            'id' => ['required', 'string', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
        ])->validate();

        $identity = [
            'id' => Str::lower($profile['id']),
            'name' => trim($profile['name']),
            'email' => Str::lower(trim($profile['email'])),
        ];

        if ($identity['name'] === '') {
            throw ValidationException::withMessages([
                'name' => 'Hive Hub returned an empty display name.',
            ]);
        }

        $user = DB::transaction(fn (): User => $this->synchronizeUser($identity));

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('logout.complete');
    }

    /**
     * @param  array{id: string, name: string, email: string}  $identity
     */
    private function synchronizeUser(array $identity): User
    {
        $user = User::query()->lockForUpdate()->find($identity['id']);

        if ($user instanceof User) {
            $this->ensureEmailIsAvailable($identity['email'], $identity['id']);
            $user->forceFill([
                'name' => $identity['name'],
                'email' => $identity['email'],
            ])->save();

            return $user;
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$identity['email']])
            ->lockForUpdate()
            ->first();

        if ($user instanceof User) {
            $user->setAttribute('id', $identity['id']);
            $user->forceFill([
                'name' => $identity['name'],
                'email' => $identity['email'],
            ])->save();

            $administratorRole = Role::query()
                ->where('slug', Role::ADMIN_SLUG)
                ->lockForUpdate()
                ->firstOrFail();
            $initialAdministratorEmail = Str::lower(trim((string) config('services.hive.initial_admin_email')));

            if (! $administratorRole->users()->exists() || $identity['email'] === $initialAdministratorEmail) {
                $user->roles()->syncWithoutDetaching([$administratorRole->getKey()]);
            }

            return $user;
        }

        $administratorRole = Role::query()
            ->where('slug', Role::ADMIN_SLUG)
            ->lockForUpdate()
            ->firstOrFail();
        $projectOwnerRole = Role::query()
            ->where('slug', Role::PROJECT_OWNER_SLUG)
            ->lockForUpdate()
            ->firstOrFail();

        $user = new User;
        $user->forceFill([
            'id' => $identity['id'],
            'name' => $identity['name'],
            'email' => $identity['email'],
            'password' => null,
        ])->save();
        $user->roles()->attach($projectOwnerRole->getKey());

        $initialAdministratorEmail = Str::lower(trim((string) config('services.hive.initial_admin_email')));
        $hasAdministrator = $administratorRole->users()->exists();

        if (! $hasAdministrator || $identity['email'] === $initialAdministratorEmail) {
            $user->roles()->attach($administratorRole->getKey());
        }

        Label::ensureDefaultLabelsFor($user);

        return $user;
    }

    private function ensureEmailIsAvailable(string $email, string $userId): void
    {
        $emailBelongsToAnotherUser = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('id', '<>', $userId)
            ->exists();

        if ($emailBelongsToAnotherUser) {
            throw ValidationException::withMessages([
                'email' => 'This Hive Hub email is already linked to a different local account.',
            ]);
        }
    }
}
