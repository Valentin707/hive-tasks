# HiveTasks Project Status

- **Project:** HiveTasks, a personal project and task workspace
- **Current revision:** Platform Version 1.5.0 (Hive Hub OAuth2 SSO and UUID identities)
- **Status:** Workspace, administration, and centralized sign-in are operational.
- **Tech stack:** PHP 8.5, Laravel 13, Livewire 4, Tailwind CSS 4, Alpine.js, Vite, Quill, and MySQL.

## Core Platform

- Tenant isolation is enforced through the global Eloquent `TenantScope` for projects, boards, columns, tasks, checklist items, notes, and labels.
- The app shell provides a responsive collapsible sidebar, dynamic page heading, account menu, theme selector, and date/live-clock footer.
- Sidebar open/closed preference persists across navigation.
- Light, dark, and Hive Command themes are available. Each user's theme preference remains stored in HiveTasks.
- Hive Command uses the `#0a0a0c` canvas, fixed grid background, monospace typography, and translucent workspace surfaces. Rich-text previews use compact spacing, including 10px list line-height.
- `README.md` contains local setup instructions, the user guide, and a suggested project presentation flow.

## Authentication and Hive Hub SSO

- Sign-in uses OAuth2 Authorization Code flow through Laravel Socialite and the custom `hive` provider.
- `GET /login` redirects to Hive Hub. `GET /auth/callback` validates the returned UUID, name, and email, synchronizes the local identity, logs the user into the session, and redirects to `/dashboard`.
- The custom provider uses `{HIVE_BASE_URL}/oauth/authorize`, `/oauth/token`, and `/api/user`. OAuth state validation remains enabled.
- Local logout invalidates the HiveTasks session and displays a logged-out screen with a deliberate “Login with Hive Hub” action.
- Local password login, password reset controls, and public registration routes and forms have been removed. Hive Hub manages identity name and email; local role, workspace, and theme data remain in HiveTasks.
- The main navigation includes a HIVE HUB link. The profile screen displays synchronized identity details and links back to the central hub.

### OAuth Configuration and TLS

- Configure `HIVE_BASE_URL`, `HIVE_CLIENT_ID`, `HIVE_CLIENT_SECRET`, and `HIVE_REDIRECT_URI` in `.env`.
- `HIVE_VERIFY_SSL=true` keeps certificate verification enabled. `HIVE_CA_BUNDLE` optionally points Guzzle to a trusted CA certificate, including a local mkcert `rootCA.pem`.
- The local environment uses the mkcert CA bundle so PHP/cURL can validate the Hive Hub certificate without disabling TLS verification.

## UUID Identity and Data Preservation

- `users.id` is a UUID primary key and `users.password` is nullable. `theme_preference` retains its `light` default.
- `sessions.user_id` is a nullable indexed UUID without a foreign key.
- `projects.user_id`, `notes.user_id`, `labels.user_id`, and `role_user.user_id` use UUID foreign keys with cascading deletes. User ownership foreign keys also cascade key updates to support first-time identity linking.
- `role_user` retains its composite primary key on `user_id` and `role_id`.
- The forward migration converts existing user IDs and all related references while preserving role links, projects, notes, labels, timestamps, and theme preferences. Existing local password hashes are cleared as authentication moves to Hive Hub.
- On first sign-in, a local account with the same normalized email is linked to the UUID supplied by Hive Hub. Existing local roles and owned workspace records remain attached through cascading key updates.
- The UUID conversion and email-index normalization migrations were applied to the local MySQL database. Post-migration checks found no orphaned project, note, label, or role references.
- The UUID conversion is forward-only; `migrate:fresh --seed` uses the UUID schema directly and remains supported.

## Dashboard

- Authenticated `/dashboard` landing page; `/` redirects to it.
- Workspace metrics show total tasks, notes, and completion percentage. Completion counts tasks in columns named `Done` or `Deployed`.
- Project health progress bars link directly to each project's board.
- The recent task monitor lists the five most recently updated workspace tasks.

## Projects and Kanban

- Projects use a responsive grid showing title, creation date, task count, and board link. Project descriptions remain on the project page.
- Project creation initializes a Main Board with Todo, In Progress, Done, and Deployed columns.
- Project pages separate the rich-text description, Kanban board, and related notes into tabs. The selected tab persists in the URL hash.
- Project deletion uses a detailed confirmation dialog, displays child counts, and requires typing the project name. Child boards, columns, and tasks are deleted with the project.
- Kanban columns scroll horizontally without shrinking. Mouse dragging pans across the board, columns preserve a usable height, and their cards scroll within each column.
- Card movement starts from the dedicated drag handle. The linked `drag-drop-manager` restores draggable state after Livewire DOM morphs and releases stale listeners.
- Columns can be added, reordered, renamed, recolored, and deleted. Column deletion requires typing `confirm` and removes its tasks.
- Tasks support rich-text descriptions, labels, checklists, movement, and deletion from the task edit modal.
- Checklist items use the cascading `checklist_items` table. Task cards show a completion count and micro progress bar; checklist edits save with a 1000 ms debounce.
- Tenant-owned labels use the normalized `labels` table and `label_task` pivot with a composite primary key and cascading foreign keys. Task forms create, select, and remove labels; cards show read-only color badges below their dates.
- Board search matches task titles and label names. Labels and checklists are eagerly loaded with board tasks.

## Notes and Profile

- Notes support title search, creation, selection, deletion, rich-text editing, automatic saving, and optional project linking.
- Project note tabs reuse the Notes workspace layout and support note creation in a modal, editing, and deletion.
- Profile identity details are synchronized from Hive Hub. Theme preference remains editable in the application shell and stored locally.

## Platform Administration, RBAC, and Provisioning

### Normalized RBAC Matrix

| Role | Slug | Access |
|---|---|---|
| Administrator | `admin` | Platform administration, user provisioning, local role management, and account purges. |
| Project Owner | `po` | Standard authenticated workspace access. |

- Roles are stored in `roles`; assignments use the `role_user` many-to-many pivot.
- `SystemAdminSeeder` creates the `admin@tasks.hive` operator profile and attaches the Administrator role without a local password.
- First-time OAuth provisioning assigns `po`. The first account receives `admin`, and `HIVE_INITIAL_ADMIN_EMAIL` can identify the initial operator.

### Admin User Management

- `/admin/users` is protected by authentication and `EnsureUserIsAdmin` middleware; unauthorized users receive a 403 response.
- Administrators can search local profiles, assign `admin` or `po`, create a local profile, and permanently delete an account with exact-name confirmation.
- Manual profile creation attaches the selected role and scaffolds a `My First Project`, a `Default Board`, and the four standard columns. The profile links to Hive Hub by email at first sign-in.
- Password reset and public-registration controls have been removed from the Admin Desk.
- The last administrator cannot be demoted or deleted, and administrators cannot delete their active account.
- Deleting a user cascades through role links, notes, projects, boards, columns, tasks, and labels according to the database foreign keys.

## Recent Verification

- `php artisan migrate --no-interaction` applied the UUID conversion and email-index normalization migrations to the local database.
- Post-migration schema inspection confirmed UUID columns, nullable user passwords, the `users_email_unique` index, and the `role_user` composite primary key.
- Read-only ownership checks found zero orphan references in projects, notes, labels, and `role_user`.
- Laravel Pint, PHP syntax checks, Composer validation, route listing, and Blade view caching passed for the SSO implementation.
- A local HTTPS request to Hive Hub using the mkcert CA bundle completed certificate validation and returned HTTP 302.
- Automated tests were not run during the SSO implementation.

## Future Upgrade Possibilities

- Full label-catalog management, including renaming and deleting labels.
- File attachments within task cards.
- Optional centralized single logout if Hive Hub exposes a compatible logout endpoint.
