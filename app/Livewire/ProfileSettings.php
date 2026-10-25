<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class ProfileSettings extends Component
{
    public string $name = '';

    public string $email = '';

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    public function mount(): void
    {
        $user = $this->authenticatedUser();

        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function updateProfile(): void
    {
        $user = $this->authenticatedUser();

        $this->name = trim($this->name);
        $this->email = Str::lower(trim($this->email));

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->getKey()),
            ],
        ]);

        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ])->save();

        session()->flash('profile-settings-status', 'Profile information updated.');
    }

    public function updatePassword(): void
    {
        $user = $this->authenticatedUser();

        $validated = $this->validate([
            'currentPassword' => ['required', 'string'],
            'newPassword' => ['required', 'string', 'min:12', 'confirmed:newPasswordConfirmation'],
        ]);

        if (! Hash::check($validated['currentPassword'], $user->getAuthPassword())) {
            $this->addError('currentPassword', 'The current password is incorrect.');

            return;
        }

        $user->forceFill([
            'password' => Hash::make($validated['newPassword']),
        ])->save();

        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        $this->resetValidation();

        session()->flash('password-settings-status', 'Password updated successfully.');
    }

    public function render(): View
    {
        return view('livewire.profile-settings');
    }

    private function authenticatedUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 401);

        return $user;
    }
}
