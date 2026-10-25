# HiveTasks Project Status

- **Project:** HiveTasks (Personal Kanban tool)
- **Tech Stack:** Laravel 13.17, PHP 8.5.12, Livewire, Tailwind CSS, Local Apache (httpd), MySQL
- **Current Status:** Core Milestone 1.0.0 Completed.
- **Core Modules Delivered:**
  - Database schema and seeders for Projects, Boards, Kanban Columns (Todo, In Progress, Done, Deployed), and Tasks.
  - Responsive Tailwind dashboard shell with sidebar and header.
  - Private JavaScript drag-and-drop manager installed from a local `package.json` path and connected to Livewire through native DOM events.
  - Tenant-owned Notes schema and model foundation.
  - Custom session-based authentication with registration, login, secure session regeneration, logout, and protected workspace routes.
  - Global Eloquent `TenantScope` enforcing `user_id` ownership for Projects and Notes and filtering Boards, Columns, and Tasks through their owning Project.
  - Controller-based routing that keeps Livewire component rendering out of `routes/web.php`.
- **Phase 5 Upgrade Possibilities:**
  - Horizontal column dragging.
  - Task tags and labels.
  - File attachments on cards.
