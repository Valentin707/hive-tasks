# HiveTasks Project Status

- **Project:** HiveTasks, a personal Kanban tool
- **Tech Stack:** Laravel 13.17, PHP 8.5.12, Livewire, Tailwind CSS, Vite, Alpine.js, local Apache (httpd), and MySQL (`tasks_hive`)
- **Current Revision:** Core Stable Version 1.1.0; core workspace features are operational.

## Core Platform

- Relational data model and seeders for Projects, Boards, Columns, Tasks, and Notes.
- New projects are created transactionally with a default Main Board and ordered Todo, In Progress, Done, and Deployed columns.
- Responsive Tailwind dashboard shell with sidebar navigation, header, and mobile menu.
- Vite-managed frontend assets, including the local drag-and-drop manager dependency and its native event bridge to Livewire.
- Custom session-based registration, login, logout, and authentication-protected workspace routes.
- Tenant isolation through a global Eloquent `TenantScope` on workspace models; project and note records are associated with their owning user.
- Controller-based route entry points for Projects, Kanban, Notes, and Profile instead of mounting Livewire components in `web.php`.

## Workspace Features

- Project list with debounced live search and a validated project creation modal.
- Kanban board with the four standard columns, debounced project selection, and live task movement with sequential position updates.
- Add-task modal in each column; task creation assigns the selected column and appends to its task order.
- Task cards show the title and creation date in the board view. Opening a card displays its description and editing controls in a modal.
- Column reordering is enabled while holding the reorder handle; changes persist through the `column-reordered` event and sequential positions.
- Notes workspace with debounced live search, note creation modal, delete confirmation, and tenant-aware note ownership.
- Notes display in preview mode by default; the pencil control opens editing, with title and content autosaving after a 1000 ms debounce.
- Profile settings at `/profile` support validated name/email updates and password changes with current-password verification and secure hashing.
- Shared form styling includes focus states, validation messages, and primary/secondary action buttons.

## Future Upgrade Possibilities

- Task tagging and labels.
- File attachments within cards.
