<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Project;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination;

    public bool $registrationEnabled = false;

    public string $search = '';

    public ?int $editingUserId = null;

    public string $editingUserName = '';

    public ?int $selectedRoleId = null;

    public string $newName = '';

    public string $newEmail = '';

    public string $newPassword = '';

    public int|string|null $newRoleId = null;

    public function mount(): void
    {
        $this->authorizeAdministrator();
        $this->registrationEnabled = SystemSetting::booleanFor(SystemSetting::REGISTRATION_ENABLED);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function closeCreateUserModal(): void
    {
        $this->authorizeAdministrator();

        $this->reset(['newName', 'newEmail', 'newPassword', 'newRoleId']);
        $this->resetValidation();
    }

    public function createUserAccount(): void
    {
        $actor = $this->authorizeAdministrator();
        $this->newName = trim($this->newName);
        $this->newEmail = Str::lower(trim($this->newEmail));
        $this->newRoleId = $this->newRoleId === '' ? null : $this->newRoleId;

        try {
            $validated = $this->validate([
                'newName' => ['required', 'string', 'max:255'],
                'newEmail' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
                'newPassword' => ['required', 'string', 'min:12', 'max:255'],
                'newRoleId' => ['nullable', 'integer', 'min:1', Rule::exists('roles', 'id')],
            ]);
        } catch (ValidationException $exception) {
            $this->newPassword = '';

            throw $exception;
        }

        $plainPassword = $validated['newPassword'];
        $this->newPassword = '';

        DB::transaction(function () use ($actor, $plainPassword, $validated): void {
            $this->lockAndAuthorizeAdministrator($actor);

            $role = $validated['newRoleId'] === null
                ? Role::query()->where('slug', Role::PROJECT_OWNER_SLUG)->lockForUpdate()->firstOrFail()
                : Role::query()->lockForUpdate()->findOrFail((int) $validated['newRoleId']);

            $user = User::query()->create([
                'name' => $validated['newName'],
                'email' => $validated['newEmail'],
                'password' => Hash::make($plainPassword),
            ]);
            $user->roles()->attach($role->getKey());

            $this->createStarterWorkspace($user);
        });

        $this->reset(['newName', 'newEmail', 'newPassword', 'newRoleId']);
        $this->resetValidation();
        $this->resetPage();
        session()->flash('admin-user-created', 'The account, starter project, board, and columns are ready.');
        $this->dispatch('user-account-created');
    }

    public function toggleRegistration(): void
    {
        $actor = $this->authorizeAdministrator();

        $this->registrationEnabled = DB::transaction(function () use ($actor): bool {
            $this->lockAndAuthorizeAdministrator($actor);

            $setting = SystemSetting::query()
                ->where('key', SystemSetting::REGISTRATION_ENABLED)
                ->lockForUpdate()
                ->first();

            $currentlyEnabled = filter_var($setting?->value ?? 'false', FILTER_VALIDATE_BOOLEAN);
            $enabled = ! $currentlyEnabled;

            SystemSetting::query()->updateOrCreate(
                ['key' => SystemSetting::REGISTRATION_ENABLED],
                ['value' => $enabled ? 'true' : 'false'],
            );

            return $enabled;
        });

        session()->flash('admin-settings-status', 'Registration setting updated.');
    }

    public function openRoleEditor(int $userId): void
    {
        $this->authorizeAdministrator();

        $targetUser = User::query()->findOrFail($userId);
        $selectedRoleId = $targetUser->roles()->orderBy('roles.id')->value('roles.id');

        $this->editingUserId = (int) $targetUser->getKey();
        $this->editingUserName = $targetUser->name;
        $this->selectedRoleId = is_numeric($selectedRoleId) ? (int) $selectedRoleId : null;

        $this->resetValidation();
    }

    public function closeRoleEditor(): void
    {
        $this->authorizeAdministrator();

        $this->resetRoleEditor();
    }

    public function saveUserRole(): void
    {
        $this->authorizeAdministrator();

        if ($this->editingUserId === null || $this->selectedRoleId === null) {
            throw ValidationException::withMessages([
                'roleId' => 'Select a role before saving.',
            ]);
        }

        $this->updateUserRole($this->editingUserId, $this->selectedRoleId);
    }

    public function updateUserRole(int $userId, int $roleId): void
    {
        $actor = $this->authorizeAdministrator();

        Validator::make(
            ['userId' => $userId, 'roleId' => $roleId],
            [
                'userId' => ['required', 'integer', 'min:1', 'exists:users,id'],
                'roleId' => ['required', 'integer', 'min:1', 'exists:roles,id'],
            ],
        )->validate();

        DB::transaction(function () use ($actor, $roleId, $userId): void {
            $administratorRole = $this->lockAndAuthorizeAdministrator($actor);
            $targetUser = User::query()->lockForUpdate()->findOrFail($userId);
            $targetRole = Role::query()->lockForUpdate()->findOrFail($roleId);

            if ((int) $targetUser->getKey() === (int) $actor->getKey() && (int) $targetRole->getKey() !== (int) $administratorRole->getKey()) {
                throw ValidationException::withMessages([
                    'roleId' => 'You cannot remove your own administrator access.',
                ]);
            }

            if ((int) $targetRole->getKey() !== (int) $administratorRole->getKey()) {
                $this->ensureAnotherAdministratorRemains($targetUser, $administratorRole);
            }

            $targetUser->roles()->sync([$targetRole->getKey()]);
        });

        $this->resetRoleEditor();
        session()->flash('admin-user-status', 'User role updated.');
        $this->dispatch('user-role-updated');
    }

    public function resetUserPassword(int $userId, string $newPlainPassword): void
    {
        $actor = $this->authorizeAdministrator();

        $validated = Validator::make(
            ['newPlainPassword' => $newPlainPassword],
            ['newPlainPassword' => ['required', 'string', 'min:12', 'max:255']],
        )->validate();

        DB::transaction(function () use ($actor, $userId, $validated): void {
            $this->lockAndAuthorizeAdministrator($actor);
            $targetUser = User::query()->lockForUpdate()->findOrFail($userId);

            $targetUser->forceFill([
                'password' => Hash::make($validated['newPlainPassword']),
            ])->save();
        });

        session()->flash('admin-user-status', 'User password reset successfully.');
        $this->dispatch('user-password-reset');
    }

    public function deleteUserAccount(int $userId, string $confirmationInput): void
    {
        $actor = $this->authorizeAdministrator();

        Validator::make(
            ['confirmationInput' => $confirmationInput],
            ['confirmationInput' => ['required', 'string', 'max:255']],
        )->validate();

        DB::transaction(function () use ($actor, $confirmationInput, $userId): void {
            $administratorRole = $this->lockAndAuthorizeAdministrator($actor);
            $targetUser = User::query()->lockForUpdate()->findOrFail($userId);

            if ((int) $targetUser->getKey() === (int) $actor->getKey()) {
                throw ValidationException::withMessages([
                    'deleteUser' => 'You cannot delete the account you are currently using.',
                ]);
            }

            if (! hash_equals($targetUser->name, $confirmationInput)) {
                throw ValidationException::withMessages([
                    'confirmationInput' => 'The confirmation text does not match the user name.',
                ]);
            }

            $this->ensureAnotherAdministratorRemains($targetUser, $administratorRole);

            $targetUser->delete();
        });

        session()->flash('admin-user-status', 'User account and associated workspace data permanently deleted.');
        $this->dispatch('user-account-deleted');
    }

    public function render(): View
    {
        $this->authorizeAdministrator();

        $this->registrationEnabled = SystemSetting::booleanFor(SystemSetting::REGISTRATION_ENABLED);

        $search = trim($this->search);
        $users = User::query()
            ->with('roles')
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $query->where(static function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15);

        $roles = Role::query()->orderBy('name')->get();

        return view('livewire.admin.user-management', compact('roles', 'users'));
    }

    private function authorizeAdministrator(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User && $user->isAdmin(), 403, 'Unauthorized');

        return $user;
    }

    private function lockAndAuthorizeAdministrator(User $actor): Role
    {
        $administratorRole = Role::query()
            ->where('slug', Role::ADMIN_SLUG)
            ->lockForUpdate()
            ->firstOrFail();
        $lockedActor = User::query()->lockForUpdate()->find($actor->getKey());

        abort_unless(
            $lockedActor instanceof User && $lockedActor->roles()->where('roles.id', $administratorRole->getKey())->exists(),
            403,
            'Unauthorized',
        );

        return $administratorRole;
    }

    private function ensureAnotherAdministratorRemains(User $targetUser, Role $administratorRole): void
    {
        $targetIsAdministrator = $targetUser->roles()
            ->where('roles.id', $administratorRole->getKey())
            ->exists();

        if (! $targetIsAdministrator) {
            return;
        }

        $administratorCount = User::query()
            ->whereHas('roles', static function (Builder $roles) use ($administratorRole): void {
                $roles->whereKey($administratorRole->getKey());
            })
            ->count();

        if ($administratorCount <= 1) {
            throw ValidationException::withMessages([
                'deleteUser' => 'The last administrator account cannot be removed or demoted.',
                'roleId' => 'The last administrator account cannot be removed or demoted.',
            ]);
        }
    }

    private function resetRoleEditor(): void
    {
        $this->editingUserId = null;
        $this->editingUserName = '';
        $this->selectedRoleId = null;
        $this->resetValidation();
    }

    /**
     * Create the starter workspace without letting the tenant observer assign the acting admin as owner.
     */
    private function createStarterWorkspace(User $user): void
    {
        $project = Project::withoutEvents(
            fn (): Project => $user->projects()->create(['name' => 'My First Project']),
        );
        $board = $project->boards()->create(['name' => 'Default Board']);

        $board->columns()->createMany([
            ['name' => 'Todo', 'position' => 1, 'color' => '#6366f1'],
            ['name' => 'In Progress', 'position' => 2, 'color' => '#f59e0b'],
            ['name' => 'Done', 'position' => 3, 'color' => '#10b981'],
            ['name' => 'Deployed', 'position' => 4, 'color' => '#06b6d4'],
        ]);
    }
}
