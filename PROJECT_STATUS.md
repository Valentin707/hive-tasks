# HiveTasks Project Status

- **Project:** HiveTasks, a personal project and task workspace
- **Current revision:** Platform Version 1.2.5 (Normalized RBAC & Admin Desk Fully Stable)
- **Status:** Core workspace, normalized role access control, and platform administration are operational.
- **Tech stack:** PHP 8.5, Laravel 13.38, Livewire 4.4, Tailwind CSS 4, Alpine.js, Vite 8, Quill 2, and MySQL (`tasks_hive`). The local development setup uses Apache (httpd).

## Core Platform

- Session-based registration, login, logout, and authenticated workspace routes.
- Tenant isolation through the global Eloquent `TenantScope` for projects, boards, columns, tasks, and notes.
- Controller-based route entry points for the dashboard, projects, Kanban boards, notes, and profile settings.
- Responsive application shell with a collapsible sidebar, dynamic page heading, account menu, theme toggle, and date/live-clock footer.
- Sidebar expanded/collapsed preference persists while navigating between pages.
- Light and dark themes are available across workspace and administration views.
- `README.md` contains local setup instructions, a user guide, seeded demo credentials, and a suggested presentation flow.

## Dashboard

- Authenticated `/dashboard` landing page; `/` redirects to it, and login/registration use it as the default destination.
- Workspace metrics calculated from current records for total tasks, notes, and completion percentage. Completion counts tasks in columns named `Done` or `Deployed`.
- Project health list with task completion progress per project and direct links to each project's board.
- Recent task monitor showing the five most recently updated tasks across the authenticated user's workspace, with links to their boards.
- Dashboard queries use the signed-in user's project and note relationships and the existing tenant-scoped task model.

## Projects and Kanban

- Responsive project grid with project title, creation date, task count, and board links; descriptions are kept on the project page.
- Project creation initializes a Main Board with Todo, In Progress, Done, and Deployed columns.
- Project page separates the rich-text description and Kanban board into tabs; the selected tab is preserved in the URL hash.
- Project deletion uses a detailed confirmation dialog that shows child counts and requires typing the project name. Related boards, columns, and tasks are deleted with the project.
- Kanban columns scroll horizontally without shrinking; mouse dragging pans across the board, and columns preserve a usable height with their own task scrolling.
- Columns can be added, reordered, renamed, recolored, or deleted. Column deletion requires typing `confirm` and removes the column's tasks.
- Tasks can be created, moved between columns, edited with rich-text descriptions, and deleted with confirmation.
- Board, column, and task updates retain their existing Livewire behavior and persistence.

## Notes and Profile

- Notes workspace supports title search, note creation, selection, deletion, and rich-text editing with automatic saving.
- Profile settings support name/email updates, password changes with current-password verification, and theme preference.

## Platform Administration, RBAC, and Provisioning

### Normalized RBAC Matrix

User access is stored in a standalone `roles` table and the many-to-many `role_user` pivot table. Both pivot foreign keys cascade on deletion, and the composite primary key prevents duplicate user-role assignments.

| Role | Slug | Access |
|---|---|---|
| Administrator | `admin` | Platform administration, user and role management, registration controls, password resets, and account purges. |
| Project Owner | `po` | Standard authenticated workspace access. This is the default role for manually provisioned accounts. |

### Platform Controls

- The `system_settings` key-value table stores platform-wide settings with a unique key and text value.
- The Admin Desk provides a reactive switch for `registration_enabled`; the registration form and login link follow this setting.
- Registration requests are redirected to the registration-closed page when public registration is disabled.

### Admin User Provisioning

- The authenticated `/admin/users` workspace lists user accounts and their associated roles, and supports role changes and password resets.
- Administrators can create accounts with a validated name, unique email, temporary password, and selected role. Passwords are hashed before storage.
- Account creation runs in a database transaction, attaches the selected role through Eloquent's many-to-many relationship, and provisions a `My First Project` with a `Default Board`.
- The starter board receives four positioned columns: Todo (1), In Progress (2), Done (3), and Deployed (4). Each column receives its own accent color.
- Starter project ownership is assigned to the newly created user, including when the account is created by an administrator.

### Security Enforcements

- The `/admin/users` route is protected by the authenticated session middleware and `EnsureUserIsAdmin`; unauthorized requests receive a 403 response.
- The Livewire component verifies administrator access when rendering records and before administrative actions.
- Role and account operations use database transactions. The final administrator cannot be demoted or deleted, and administrators cannot delete their active account.
- User deletion requires typing the target user's exact name. Alpine disables the purge action until it matches, and the server independently checks the exact value before deletion.
- Deleting a user cascades through their role links, notes, projects, boards, columns, and tasks using the configured database foreign keys.

## Recent Verification

- Laravel Pint and PHP syntax checks passed for the administration and provisioning changes.
- `php artisan view:cache` completed successfully.
- `php artisan route:list --path=admin -v` confirmed the admin route has both authentication and `EnsureUserIsAdmin` middleware.
- `npm run build` completed successfully. Vite warned that the local Node.js 20.17.0 is below its supported minimum of 20.19.0 (or 22.12.0).
- Automated tests were not run during the administration implementation.

## Future Upgrade Possibilities

- Task tagging and labels.
- File attachments within task cards.
