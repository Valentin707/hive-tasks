<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Label;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    public string $search = '';

    public ?string $editingUserId = null;

    public string $editingUserName = '';

    public ?int $selectedRoleId = null;

    public string $newName = '';

    public string $newEmail = '';

    public int|string|null $newRoleId = null;

    public function mount(): void
    {
        $this->authorizeAdministrator();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function closeCreateUserModal(): void
    {
        $this->authorizeAdministrator();

        $this->reset(['newName', 'newEmail', 'newRoleId']);
        $this->resetValidation();
    }

    public function createUserAccount(): void
    {
        $actor = $this->authorizeAdministrator();
        $this->newName = trim($this->newName);
        $this->newEmail = Str::lower(trim($this->newEmail));
        $this->newRoleId = $this->newRoleId === '' ? null : $this->newRoleId;

        $validated = $this->validate([
            'newName' => ['required', 'string', 'max:255'],
            'newEmail' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'newRoleId' => ['nullable', 'integer', 'min:1', Rule::exists('roles', 'id')],
        ]);

        DB::transaction(function () use ($actor, $validated): void {
            $this->lockAndAuthorizeAdministrator($actor);

            $role = $validated['newRoleId'] === null
                ? Role::query()->where('slug', Role::PROJECT_OWNER_SLUG)->lockForUpdate()->firstOrFail()
                : Role::query()->lockForUpdate()->findOrFail((int) $validated['newRoleId']);

            $user = User::query()->create([
                'name' => $validated['newName'],
                'email' => $validated['newEmail'],
            ]);
            $user->roles()->attach($role->getKey());
            Label::ensureDefaultLabelsFor($user);

            $this->createStarterWorkspace($user);
        });

        $this->reset(['newName', 'newEmail', 'newRoleId']);
        $this->resetValidation();
        $this->resetPage();
        session()->flash('admin-user-created', 'The local profile and starter workspace are ready. The profile will sync with Hive Hub on first sign-in.');
        $this->dispatch('user-account-created');
    }

    public function openRoleEditor(string $userId): void
    {
        $this->authorizeAdministrator();
        Validator::make(['userId' => $userId], ['userId' => ['required', 'uuid', 'exists:users,id']])->validate();

        $targetUser = User::query()->findOrFail($userId);
        $selectedRoleId = $targetUser->roles()->orderBy('roles.id')->value('roles.id');

        $this->editingUserId = (string) $targetUser->getKey();
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

    public function updateUserRole(string $userId, int $roleId): void
    {
        $actor = $this->authorizeAdministrator();

        Validator::make(
            ['userId' => $userId, 'roleId' => $roleId],
            [
                'userId' => ['required', 'uuid', 'exists:users,id'],
                'roleId' => ['required', 'integer', 'min:1', 'exists:roles,id'],
            ],
        )->validate();

        DB::transaction(function () use ($actor, $roleId, $userId): void {
            $administratorRole = $this->lockAndAuthorizeAdministrator($actor);
            $targetUser = User::query()->lockForUpdate()->findOrFail($userId);
            $targetRole = Role::query()->lockForUpdate()->findOrFail($roleId);

            if ($targetUser->getKey() === $actor->getKey() && (int) $targetRole->getKey() !== (int) $administratorRole->getKey()) {
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

    public function deleteUserAccount(string $userId, string $confirmationInput): void
    {
        $actor = $this->authorizeAdministrator();

        Validator::make(
            ['userId' => $userId, 'confirmationInput' => $confirmationInput],
            [
                'userId' => ['required', 'uuid', 'exists:users,id'],
                'confirmationInput' => ['required', 'string', 'max:255'],
            ],
        )->validate();

        DB::transaction(function () use ($actor, $confirmationInput, $userId): void {
            $administratorRole = $this->lockAndAuthorizeAdministrator($actor);
            $targetUser = User::query()->lockForUpdate()->findOrFail($userId);

            if ($targetUser->getKey() === $actor->getKey()) {
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
