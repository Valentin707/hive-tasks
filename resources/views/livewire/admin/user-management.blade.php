<section
    x-data="{
        activeDeleteUserTargetId: null,
        targetUserName: '',
        confirmationInput: '',
        activeResetUserTargetId: null,
        resetUserName: '',
        resetPasswordInput: '',
        createUserModalOpen: false,
        closeDeleteModal() {
            this.activeDeleteUserTargetId = null;
            this.targetUserName = '';
            this.confirmationInput = '';
        },
        closeResetModal() {
            this.activeResetUserTargetId = null;
            this.resetUserName = '';
            this.resetPasswordInput = '';
        },
    }"
    x-on:user-account-deleted.window="closeDeleteModal()"
    x-on:user-password-reset.window="closeResetModal()"
    x-on:user-account-created.window="createUserModalOpen = false"
    x-on:keydown.escape.window="if (createUserModalOpen) { createUserModalOpen = false; $wire.closeCreateUserModal() }; if (activeDeleteUserTargetId !== null) closeDeleteModal(); if (activeResetUserTargetId !== null) closeResetModal()"
>
    <div class="mb-8 flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="mb-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">Platform administration</p>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 sm:text-3xl">User Management</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-500">Manage platform access, account credentials, and registration availability.</p>
        </div>

        <div class="flex w-full flex-col gap-3 sm:flex-row xl:w-auto">
            <label class="relative min-w-0 flex-1 sm:w-72" for="admin-user-search">
                <span class="sr-only">Search users</span>
                <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <circle cx="10.8" cy="10.8" r="6.3" />
                    <path stroke-linecap="round" d="m15.5 15.5 4.25 4.25" />
                </svg>
                <input id="admin-user-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search by name or email" class="form-input pl-9">
            </label>
            <button type="button" @click="createUserModalOpen = true; $nextTick(() => $refs.newUserName.focus())" class="button-primary w-full whitespace-nowrap sm:w-auto">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" d="M12 5v14m-7-7h14" />
                    <circle cx="12" cy="12" r="9" />
                </svg>
                Add New User
            </button>
        </div>
    </div>

    @if (session()->has('admin-user-created'))
        <div role="status" class="mb-5 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-900/70 dark:bg-emerald-950/30">
            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-200">
                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                </svg>
                Created
            </span>
            <p class="text-sm font-medium text-emerald-800 dark:text-emerald-200">{{ session('admin-user-created') }}</p>
        </div>
    @endif

    @if (session()->has('admin-settings-status') || session()->has('admin-user-status'))
        <div role="status" class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900/70 dark:bg-emerald-950/30 dark:text-emerald-200">
            {{ session('admin-settings-status') ?? session('admin-user-status') }}
        </div>
    @endif

    <section aria-labelledby="platform-settings-heading" class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm shadow-gray-900/5 sm:p-6 dark:border-gray-200 dark:bg-gray-100 dark:shadow-black/20">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-4">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-300">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75v2m0 12.5v2m8.25-8.25h-2m-12.5 0h-2m14.08-5.83-1.42 1.42M7.59 16.41l-1.42 1.42m11.66 0-1.42-1.42M7.59 7.59 6.17 6.17" />
                        <circle cx="12" cy="12" r="3.25" />
                    </svg>
                </span>
                <div>
                    <h2 id="platform-settings-heading" class="text-base font-semibold text-gray-900">Platform settings</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-500">Control whether new accounts can be created from the public registration page.</p>
                </div>
            </div>

            <button
                type="button"
                role="switch"
                aria-label="Public user registration"
                aria-checked="{{ $registrationEnabled ? 'true' : 'false' }}"
                wire:click="toggleRegistration"
                wire:loading.attr="disabled"
                wire:target="toggleRegistration"
                class="inline-flex w-full items-center justify-between gap-4 rounded-xl border border-gray-200 px-4 py-3 text-left transition hover:bg-gray-50 disabled:cursor-wait sm:w-auto sm:min-w-56 dark:border-gray-200 dark:hover:bg-gray-50"
            >
                <span>
                    <span class="block text-sm font-semibold text-gray-900">Public registration</span>
                    <span class="mt-0.5 block text-xs text-gray-500" wire:loading.remove wire:target="toggleRegistration">{{ $registrationEnabled ? 'Enabled' : 'Disabled' }}</span>
                    <span class="mt-0.5 hidden text-xs text-gray-500" wire:loading wire:target="toggleRegistration">Saving setting…</span>
                </span>
                <span @class([
                    'relative inline-flex h-6 w-11 shrink-0 rounded-full transition-colors',
                    'bg-indigo-600' => $registrationEnabled,
                    'bg-gray-300 dark:bg-gray-400' => ! $registrationEnabled,
                ]) aria-hidden="true">
                    <span @class([
                        'absolute top-0.5 size-5 rounded-full bg-white shadow transition-transform',
                        'translate-x-5' => $registrationEnabled,
                        'translate-x-0.5' => ! $registrationEnabled,
                    ])></span>
                </span>
            </button>
        </div>
    </section>

    <section aria-labelledby="users-table-heading" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm shadow-gray-900/5 dark:border-gray-200 dark:bg-gray-100 dark:shadow-black/20">
        <div class="flex flex-col gap-2 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 dark:border-gray-200">
            <div>
                <h2 id="users-table-heading" class="text-base font-semibold text-gray-900">System users</h2>
                <p class="mt-1 text-sm text-gray-500">Assign roles and manage individual accounts.</p>
            </div>
            <span class="text-sm text-gray-500">{{ $users->total() }} {{ \Illuminate\Support\Str::plural('account', $users->total()) }}</span>
        </div>

        @if ($users->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-gray-200">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-50">
                        <tr>
                            <th scope="col" class="px-5 py-3.5 sm:px-6">User</th>
                            <th scope="col" class="px-5 py-3.5">Role</th>
                            <th scope="col" class="px-5 py-3.5">Joined</th>
                            <th scope="col" class="px-5 py-3.5 text-right">Account actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-200">
                        @foreach ($users as $user)
                            <tr wire:key="admin-user-{{ $user->id }}" class="transition hover:bg-gray-50/70 dark:hover:bg-gray-50">
                                <td class="whitespace-nowrap px-5 py-4 sm:px-6">
                                    <div class="flex items-center gap-3">
                                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-xs font-semibold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300" aria-hidden="true">
                                            {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block max-w-56 truncate font-semibold text-gray-900">{{ $user->name }}</span>
                                            <span class="mt-0.5 block max-w-56 truncate text-xs text-gray-500">{{ $user->email }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    @forelse ($user->roles as $role)
                                        <span wire:key="admin-user-{{ $user->id }}-role-{{ $role->id }}" class="mr-1 inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-200 dark:text-gray-700">{{ $role->name }}</span>
                                    @empty
                                        <span class="text-xs text-gray-400">Unassigned</span>
                                    @endforelse
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-gray-500">{{ $user->created_at?->format('M j, Y') ?? '—' }}</td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" wire:click="openRoleEditor({{ (int) $user->getKey() }})" wire:loading.attr="disabled" wire:target="openRoleEditor" class="rounded-lg px-2.5 py-2 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-50 disabled:opacity-50 dark:text-indigo-300 dark:hover:bg-indigo-950/50">Change role</button>
                                        <button
                                            type="button"
                                            @click="activeResetUserTargetId = {{ (int) $user->getKey() }}; resetUserName = @js($user->name); resetPasswordInput = ''; $nextTick(() => $refs.resetPasswordInput.focus())"
                                            class="rounded-lg px-2.5 py-2 text-xs font-semibold text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-700 dark:hover:bg-gray-200 dark:hover:text-gray-900"
                                        >Reset password</button>
                                        <button
                                            type="button"
                                            @click="activeDeleteUserTargetId = {{ (int) $user->getKey() }}; targetUserName = @js($user->name); confirmationInput = ''; $nextTick(() => $refs.deleteConfirmationInput.focus())"
                                            aria-label="Delete {{ $user->name }}"
                                            class="rounded-lg px-2.5 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40"
                                        >Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 px-5 py-4 sm:px-6 dark:border-gray-200">
                {{ $users->links() }}
            </div>
        @else
            <div class="px-6 py-16 text-center">
                <span class="mx-auto flex size-12 items-center justify-center rounded-xl bg-gray-100 text-gray-400 dark:bg-gray-200">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="9" cy="8" r="3.25" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 19.25a5.25 5.25 0 0 1 10.5 0M16 5.25a3.25 3.25 0 0 1 0 6.5m1.25 2a5.25 5.25 0 0 1 3 5.5" />
                    </svg>
                </span>
                <h3 class="mt-4 text-sm font-semibold text-gray-900">{{ trim($search) === '' ? 'No user accounts found' : 'No matching users' }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ trim($search) === '' ? 'User accounts will appear here after registration.' : 'Try another name or email address.' }}</p>
            </div>
        @endif
    </section>

    <div x-cloak x-show="createUserModalOpen" x-transition.opacity class="fixed inset-0 z-[70] flex items-start justify-center overflow-y-auto bg-gray-950/50 p-4 sm:items-center" @click.self="createUserModalOpen = false; $wire.closeCreateUserModal()">
        <section role="dialog" aria-modal="true" aria-labelledby="create-user-title" class="my-auto w-full max-w-xl rounded-2xl border border-gray-200 bg-white p-6 shadow-xl sm:p-7 dark:border-gray-200 dark:bg-gray-100">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-300">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="9" cy="8" r="3.25" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 19.25a5.25 5.25 0 0 1 10.5 0M18 8v6m-3-3h6" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">Account provisioning</p>
                        <h2 id="create-user-title" class="mt-1 text-lg font-semibold text-gray-900">Add a new user</h2>
                        <p class="mt-1 text-sm leading-6 text-gray-500">A starter project and Kanban board will be created automatically.</p>
                    </div>
                </div>
                <button type="button" @click="createUserModalOpen = false; $wire.closeCreateUserModal()" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-200" aria-label="Close new user form">×</button>
            </div>

            <form wire:submit.prevent="createUserAccount" class="space-y-5">
                <div>
                    <label for="new-user-name" class="form-label">Full name</label>
                    <input id="new-user-name" x-ref="newUserName" type="text" wire:model="newName" maxlength="255" autocomplete="name" required class="form-input" @error('newName') aria-invalid="true" aria-describedby="new-user-name-error" @enderror>
                    @error('newName')
                        <p id="new-user-name-error" class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="new-user-email" class="form-label">Email address</label>
                    <input id="new-user-email" type="email" wire:model="newEmail" maxlength="255" autocomplete="email" required class="form-input" @error('newEmail') aria-invalid="true" aria-describedby="new-user-email-error" @enderror>
                    @error('newEmail')
                        <p id="new-user-email-error" class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="new-user-password" class="form-label">Temporary password</label>
                    <input id="new-user-password" type="password" wire:model="newPassword" minlength="12" maxlength="255" autocomplete="new-password" required class="form-input" @error('newPassword') aria-invalid="true" aria-describedby="new-user-password-error" @enderror>
                    @error('newPassword')
                        <p id="new-user-password-error" class="form-error">{{ $message }}</p>
                    @enderror
                    <p class="mt-1.5 text-xs text-gray-500">Use at least 12 characters.</p>
                </div>

                <div>
                    <label for="new-user-role" class="form-label">Role</label>
                    <select id="new-user-role" wire:model.number="newRoleId" class="form-input" @error('newRoleId') aria-invalid="true" aria-describedby="new-user-role-error" @enderror>
                        <option value="">Project Owner (default)</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                    @error('newRoleId')
                        <p id="new-user-role-error" class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end dark:border-gray-200">
                    <button type="button" @click="createUserModalOpen = false; $wire.closeCreateUserModal()" class="button-secondary">Cancel</button>
                    <button type="submit" class="button-primary" wire:loading.attr="disabled" wire:target="createUserAccount">
                        <span wire:loading.remove wire:target="createUserAccount">Create user account</span>
                        <span wire:loading wire:target="createUserAccount">Creating account…</span>
                    </button>
                </div>
            </form>
        </section>
    </div>

    @if ($editingUserId !== null)
        <div class="fixed inset-0 z-[70] flex items-start justify-center overflow-y-auto bg-gray-950/50 p-4 sm:items-center" wire:key="role-editor-modal">
            <section role="dialog" aria-modal="true" aria-labelledby="role-editor-title" class="my-auto w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-200 dark:bg-gray-100">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">Access control</p>
                        <h2 id="role-editor-title" class="mt-1 text-lg font-semibold text-gray-900">Change user role</h2>
                        <p class="mt-1 max-w-sm truncate text-sm text-gray-500">{{ $editingUserName }}</p>
                    </div>
                    <button type="button" wire:click="closeRoleEditor" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-200" aria-label="Close role editor">×</button>
                </div>

                <form wire:submit="saveUserRole">
                    <div class="mb-6">
                        <label for="admin-user-role" class="form-label">Assigned role</label>
                        <select id="admin-user-role" wire:model.number="selectedRoleId" class="form-input" @error('roleId') aria-invalid="true" aria-describedby="admin-user-role-error" @enderror>
                            <option value="">Choose a role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('roleId')
                            <p id="admin-user-role-error" class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="closeRoleEditor" class="button-secondary">Cancel</button>
                        <button type="submit" class="button-primary" wire:loading.attr="disabled" wire:target="saveUserRole, updateUserRole">
                            <span wire:loading.remove wire:target="saveUserRole, updateUserRole">Save role</span>
                            <span wire:loading wire:target="saveUserRole, updateUserRole">Saving…</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    <div x-cloak x-show="activeResetUserTargetId !== null" x-transition.opacity class="fixed inset-0 z-[70] flex items-start justify-center overflow-y-auto bg-gray-950/50 p-4 sm:items-center" @click.self="closeResetModal()">
        <section role="dialog" aria-modal="true" aria-labelledby="reset-user-password-title" class="my-auto w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-200 dark:bg-gray-100">
            <div class="mb-6 flex items-start gap-4">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="4.75" y="10.25" width="14.5" height="10" rx="2" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10.25V7a4 4 0 0 1 8 0v3.25m-4 4v2.5" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h2 id="reset-user-password-title" class="text-lg font-semibold text-gray-900">Reset account password</h2>
                    <p class="mt-1 truncate text-sm text-gray-500" x-text="resetUserName"></p>
                </div>
            </div>

            <div class="mb-6">
                <label for="admin-new-password" class="form-label">New password</label>
                <input id="admin-new-password" x-ref="resetPasswordInput" x-model="resetPasswordInput" type="password" autocomplete="new-password" minlength="12" maxlength="255" class="form-input" @error('newPlainPassword') aria-invalid="true" aria-describedby="admin-new-password-error" @enderror>
                @error('newPlainPassword')
                    <p id="admin-new-password-error" class="form-error">{{ $message }}</p>
                @enderror
                <p class="mt-1.5 text-xs text-gray-500">Use at least 12 characters.</p>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" @click="closeResetModal()" class="button-secondary">Cancel</button>
                <button type="button" @click="$wire.resetUserPassword(activeResetUserTargetId, resetPasswordInput)" :disabled="resetPasswordInput.length < 12" class="button-primary" wire:loading.attr="disabled" wire:target="resetUserPassword">
                    <span wire:loading.remove wire:target="resetUserPassword">Save new password</span>
                    <span wire:loading wire:target="resetUserPassword">Saving…</span>
                </button>
            </div>
        </section>
    </div>

    <div x-cloak x-show="activeDeleteUserTargetId !== null" x-transition.opacity class="fixed inset-0 z-[80] flex items-start justify-center overflow-y-auto bg-gray-950/60 p-4 sm:items-center" @click.self="closeDeleteModal()">
        <section role="alertdialog" aria-modal="true" aria-labelledby="delete-user-title" aria-describedby="delete-user-description" class="my-auto w-full max-w-lg rounded-2xl border border-red-200 bg-white p-6 shadow-2xl shadow-gray-950/25 sm:p-7 dark:border-red-900/70 dark:bg-gray-100 dark:shadow-black/50">
            <div class="mb-5 flex items-start gap-4">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.3 3.9 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3l-7.5-13.1a2 2 0 0 0-3.4 0Z" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wide text-red-700 dark:text-red-300">Permanent account purge</p>
                    <h2 id="delete-user-title" class="mt-1 text-lg font-semibold text-gray-900">Delete this user and all data?</h2>
                    <p class="mt-1 truncate text-sm font-semibold text-gray-700 dark:text-gray-800" x-text="targetUserName"></p>
                </div>
            </div>

            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900/60 dark:bg-red-950/30">
                <p id="delete-user-description" class="text-sm leading-6 text-red-800 dark:text-red-200">This action is irreversible. The account, notes, projects, boards, columns, tasks, and role links will be permanently removed.</p>
            </div>

            <div class="mb-6">
                <label for="delete-user-confirmation" class="form-label">This action is irreversible. To confirm deletion, type <span class="rounded bg-gray-100 px-1.5 py-0.5 font-mono font-semibold text-gray-900 dark:bg-gray-800 dark:text-gray-100" x-text="targetUserName"></span> below.</label>
                <input id="delete-user-confirmation" x-ref="deleteConfirmationInput" x-model="confirmationInput" type="text" autocomplete="off" spellcheck="false" class="form-input" @error('confirmationInput') aria-invalid="true" aria-describedby="delete-user-confirmation-error" @enderror>
                @error('confirmationInput')
                    <p id="delete-user-confirmation-error" class="form-error">{{ $message }}</p>
                @enderror
                @error('deleteUser')
                    <p class="form-error">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-xs text-gray-500">The text must match the account name exactly, including capitalization.</p>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" @click="closeDeleteModal()" class="button-secondary">Cancel</button>
                <button type="button" @click="$wire.deleteUserAccount(activeDeleteUserTargetId, confirmationInput)" :disabled="confirmationInput !== targetUserName" class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-500/25 disabled:cursor-not-allowed disabled:opacity-50" wire:loading.attr="disabled" wire:target="deleteUserAccount">
                    <span wire:loading.remove wire:target="deleteUserAccount">ERASE ALL DATA PERMANENTLY</span>
                    <span wire:loading wire:target="deleteUserAccount">Purging account…</span>
                </button>
            </div>
        </section>
    </div>
</section>
