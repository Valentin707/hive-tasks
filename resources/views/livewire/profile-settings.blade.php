<section aria-labelledby="profile-settings-title">
    <div class="mb-8">
        <p class="mb-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">Account</p>
        <h1 id="profile-settings-title" class="text-2xl font-semibold tracking-tight text-gray-900 sm:text-3xl">Profile</h1>
        <p class="mt-2 text-sm text-gray-500">Your identity details are managed by Hive Hub. Theme preference remains saved in this workspace.</p>
    </div>

    <section class="max-w-2xl rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7 dark:border-gray-700 dark:bg-gray-900" aria-labelledby="profile-information-title">
        <div class="flex items-center gap-4">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-sm font-semibold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300" aria-hidden="true">
                {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
            </span>
            <div class="min-w-0">
                <h2 id="profile-information-title" class="truncate text-lg font-semibold text-gray-900">{{ $user->name }}</h2>
                <p class="truncate text-sm text-gray-500">{{ $user->email }}</p>
            </div>
        </div>

        <div class="mt-6 rounded-xl border border-indigo-100 bg-indigo-50/70 p-4 dark:border-indigo-900/60 dark:bg-indigo-950/30">
            <p class="text-sm font-medium text-indigo-900 dark:text-indigo-200">Hive Hub account</p>
            <p class="mt-1 text-sm leading-6 text-indigo-800/80 dark:text-indigo-300">Name and email are synchronized when you sign in. Local roles, projects, notes, labels, and theme settings stay in HiveTasks.</p>
            <a href="{{ rtrim((string) config('services.hive.base_url'), '/') }}" class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-indigo-700 transition hover:text-indigo-800 dark:text-indigo-300 dark:hover:text-indigo-200">
                Open HIVE HUB
                <span aria-hidden="true">↗</span>
            </a>
        </div>
    </section>
</section>
