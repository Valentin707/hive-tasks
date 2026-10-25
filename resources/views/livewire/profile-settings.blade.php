<section aria-labelledby="profile-settings-title">
    <div class="mb-8">
        <p class="mb-2 text-sm font-medium text-indigo-600">Account</p>
        <h1 id="profile-settings-title" class="text-2xl font-semibold tracking-tight text-gray-900 sm:text-3xl">Profile settings</h1>
        <p class="mt-2 text-sm text-gray-500">Manage your personal information and account password.</p>
    </div>

    <div class="grid items-start gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7" aria-labelledby="profile-information-title">
            <div>
                <h2 id="profile-information-title" class="text-lg font-semibold text-gray-900">Profile Information</h2>
                <p class="mt-1 text-sm text-gray-500">Update the name and email address associated with your account.</p>
            </div>

            @if (session('profile-settings-status'))
                <div role="status" class="mt-5 flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-3.5 py-3 text-sm font-medium text-green-800">
                    <svg class="size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.78-9.97a.75.75 0 0 0-1.06-1.06l-3.6 3.6-1.84-1.84a.75.75 0 1 0-1.06 1.06l2.37 2.37a.75.75 0 0 0 1.06 0l4.13-4.13Z" clip-rule="evenodd" />
                    </svg>
                    {{ session('profile-settings-status') }}
                </div>
            @endif

            <form wire:submit="updateProfile" class="mt-6">
                <div class="mb-5">
                    <label for="profile-name" class="form-label">Name</label>
                    <input
                        id="profile-name"
                        type="text"
                        wire:model="name"
                        autocomplete="name"
                        maxlength="255"
                        required
                        class="form-input"
                        @error('name') aria-invalid="true" aria-describedby="profile-name-error" @enderror
                    >
                    @error('name')
                        <p id="profile-name-error" class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="profile-email" class="form-label">Email address</label>
                    <input
                        id="profile-email"
                        type="email"
                        wire:model="email"
                        autocomplete="email"
                        maxlength="255"
                        required
                        class="form-input"
                        @error('email') aria-invalid="true" aria-describedby="profile-email-error" @enderror
                    >
                    @error('email')
                        <p id="profile-email-error" class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="button-primary" wire:loading.attr="disabled" wire:target="updateProfile">
                        <span wire:loading.remove wire:target="updateProfile">Save profile</span>
                        <span wire:loading wire:target="updateProfile">Saving...</span>
                    </button>
                </div>
            </form>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7" aria-labelledby="update-password-title">
            <div>
                <h2 id="update-password-title" class="text-lg font-semibold text-gray-900">Update Password</h2>
                <p class="mt-1 text-sm text-gray-500">Confirm your current password before choosing a new one.</p>
            </div>

            @if (session('password-settings-status'))
                <div role="status" class="mt-5 flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-3.5 py-3 text-sm font-medium text-green-800">
                    <svg class="size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.78-9.97a.75.75 0 0 0-1.06-1.06l-3.6 3.6-1.84-1.84a.75.75 0 1 0-1.06 1.06l2.37 2.37a.75.75 0 0 0 1.06 0l4.13-4.13Z" clip-rule="evenodd" />
                    </svg>
                    {{ session('password-settings-status') }}
                </div>
            @endif

            <form wire:submit="updatePassword" class="mt-6">
                <div class="mb-5">
                    <label for="current-password" class="form-label">Current password</label>
                    <input
                        id="current-password"
                        type="password"
                        wire:model="currentPassword"
                        autocomplete="current-password"
                        required
                        class="form-input"
                        @error('currentPassword') aria-invalid="true" aria-describedby="current-password-error" @enderror
                    >
                    @error('currentPassword')
                        <p id="current-password-error" class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-5">
                    <label for="new-password" class="form-label">New password</label>
                    <input
                        id="new-password"
                        type="password"
                        wire:model="newPassword"
                        autocomplete="new-password"
                        minlength="12"
                        required
                        class="form-input"
                        @error('newPassword') aria-invalid="true" aria-describedby="new-password-error" @enderror
                    >
                    @error('newPassword')
                        <p id="new-password-error" class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="new-password-confirmation" class="form-label">Confirm new password</label>
                    <input
                        id="new-password-confirmation"
                        type="password"
                        wire:model="newPasswordConfirmation"
                        autocomplete="new-password"
                        minlength="12"
                        required
                        class="form-input"
                    >
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="button-primary" wire:loading.attr="disabled" wire:target="updatePassword">
                        <span wire:loading.remove wire:target="updatePassword">Update password</span>
                        <span wire:loading wire:target="updatePassword">Updating...</span>
                    </button>
                </div>
            </form>
        </section>
    </div>
</section>
