# HiveTasks

HiveTasks is a project planning workspace for organizing work on flexible Kanban boards and keeping project notes together. Projects, boards, tasks, and notes are managed in one responsive interface, with light and dark themes.

## Highlights

- Create projects with rich-text descriptions and open their boards from the project list.
- Organize tasks across customizable Kanban columns. Add, edit, reorder, recolor, and delete columns; create, edit, and delete tasks.
- Scroll wide boards horizontally, including by dragging across the board background.
- Keep notes in a searchable list and edit their rich-text content with automatic saving.
- Use the application on desktop or mobile, switch themes, and collapse the sidebar to make more room for the workspace.

## Run locally

### Requirements

- PHP 8.3 or later and Composer
- Node.js and npm
- SQLite support in PHP (or another database configured in `.env`)

### Setup

From the project directory, run:

```bash
composer setup
```

This installs the PHP and JavaScript dependencies, creates the application key, runs database migrations, and builds the frontend assets. The example environment uses SQLite by default. If you use another database, configure its connection in `.env` before running the migrations.

Add sample presentation data, including a demo user and a sample project board:

```bash
php artisan db:seed
```

Start the application and its development services:

```bash
composer run dev
```

Open [http://localhost:8000](http://localhost:8000) in your browser. Sign in with the seeded local demo account below, or choose **Register** to create an account:

| Email | Password |
| --- | --- |
| `test@example.com` | `password` |

This account is for local demonstrations only. Register a separate account for normal use.

## User guide

### Projects and boards

The **Projects** page is the workspace home. Each project card shows its title, creation date, task count, and links to its boards. Use **New Project** to create a project and add an optional rich-text description.

Open a board from its project card. The project page separates the project description and Kanban board into tabs; the selected tab is kept in the page URL so it can be reopened directly. Edit the description from the description tab.

On the board:

1. Add a task to a column and enter its title and optional rich-text description.
2. Select a task card to edit it, then save your changes in the task editor. Move cards between columns to reflect progress.
3. Use the column controls to add a column, reorder columns, change a column's name or color, or delete it.
4. Delete a task or column from its controls. Deletion prompts for confirmation; column deletion also removes its tasks.
5. Drag across empty board space to scroll horizontally when columns extend beyond the viewport. The columns keep a readable width instead of shrinking.

Deleting a project permanently deletes its boards, columns, and tasks. The confirmation dialog shows what will be removed and asks you to type the project name.

### Notes

Open **Notes** in the sidebar to create, search by title, and manage notes. Select a note to read it, or choose its edit action to change the title and rich-text content. Edits are saved automatically. Notes can also be deleted from the list.

### Navigation and appearance

- Use the top navigation button to collapse or reopen the sidebar. Its state is remembered while navigating between pages.
- Open the account menu in the top navigation to access profile settings or log out.
- Choose a light or dark theme in profile settings. The theme applies across the workspace.
- On narrow screens, the project list and workspaces adapt to the available width; Kanban boards remain horizontally scrollable.

## Suggested presentation flow

1. Sign in with the seeded demo account and show the sample project list.
2. Open the sample project, review its description, and switch to its board.
3. Create a task, edit its rich-text description, then demonstrate adding or recoloring a column.
4. Show horizontal board navigation and the confirmation flow for a destructive action.
5. Open Notes, create a note, and show that editing is saved automatically.
6. Finish by toggling the theme and collapsing the sidebar to demonstrate the responsive workspace.

## Built with

Laravel, Livewire, Alpine.js, Tailwind CSS, Vite, and Quill.
