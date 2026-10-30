# HiveTasks Project Status

- **Project:** HiveTasks, a personal project and task workspace
- **Status:** Core workspace features are operational. The centralized Project Hub dashboard (Option C) is implemented.
- **Tech stack:** PHP 8.5, Laravel 13.38, Livewire 4.4, Tailwind CSS 4, Alpine.js, Vite 8, Quill 2, and MySQL (`tasks_hive`). The local development setup uses Apache (httpd).

## Core Platform

- Session-based registration, login, logout, and authenticated workspace routes.
- Tenant isolation through the global Eloquent `TenantScope` for projects, boards, columns, tasks, and notes.
- Controller-based route entry points for the dashboard, projects, Kanban boards, notes, and profile settings.
- Responsive application shell with a collapsible sidebar, dynamic page heading, account menu, theme toggle, and date/live-clock footer.
- Sidebar expanded/collapsed preference persists while navigating between pages.
- Light and dark themes are available across workspace views.
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

## Recent Verification

- Laravel Pint and PHP syntax checks passed for the dashboard changes.
- `php artisan route:list --except-vendor` confirmed the authenticated `/dashboard` route and root redirect.
- `php artisan view:cache` completed successfully.
- `npm run build` completed successfully. Vite warned that the local Node.js 20.17.0 is below its supported minimum of 20.19.0 (or 22.12.0).
- Automated tests were not run during the dashboard implementation.

## Future Upgrade Possibilities

- Task tagging and labels.
- File attachments within task cards.
