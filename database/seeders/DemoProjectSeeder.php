<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Label;
use App\Models\Note;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoProjectSeeder extends Seeder
{
    private const string ADMIN_EMAIL = 'admin@tasks.hive';

    private const string PROJECT_NAME = 'HiveTasks Demo Project';

    /** @var array<string, array{position: int, color: string}> */
    private const array COLUMNS = [
        'Todo' => ['position' => 1, 'color' => '#6366f1'],
        'In Progress' => ['position' => 2, 'color' => '#f59e0b'],
        'Done' => ['position' => 3, 'color' => '#22c55e'],
        'Deployed' => ['position' => 4, 'color' => '#06b6d4'],
    ];

    /** @var array<string, string> */
    private const array LABELS = [
        'feature' => '#22c55e',
        'bug' => '#ef4444',
        'support' => '#3b82f6',
    ];

    public function run(): void
    {
        $administrator = User::query()->where('email', self::ADMIN_EMAIL)->first();

        if (! $administrator instanceof User || ! $administrator->isAdmin()) {
            throw new RuntimeException('Run SystemAdminSeeder first to create the HiveTasks administrator account.');
        }

        DB::transaction(function () use ($administrator): void {
            $project = $administrator->projects()->updateOrCreate(
                ['name' => self::PROJECT_NAME],
                ['description' => $this->projectDescription()],
            );
            $board = $project->boards()->updateOrCreate(['name' => 'Demo Board'], []);
            $labels = $this->seedLabels($administrator);

            foreach ($this->taskDefinitions() as $columnName => $tasks) {
                $column = $board->columns()->updateOrCreate(
                    ['name' => $columnName],
                    self::COLUMNS[$columnName],
                );

                foreach ($tasks as $taskPosition => $taskDefinition) {
                    $task = $column->tasks()->updateOrCreate(
                        ['title' => $taskDefinition['title']],
                        [
                            'description' => $taskDefinition['description'],
                            'position' => $taskPosition + 1,
                        ],
                    );

                    $task->labels()->sync(array_map(
                        static fn (string $labelName): int => (int) $labels[$labelName]->getKey(),
                        $taskDefinition['labels'],
                    ));

                    $task->checklists()->delete();

                    foreach ($taskDefinition['checklist'] ?? [] as $checklistPosition => $checklistItem) {
                        $task->checklists()->create([
                            'title' => $checklistItem['title'],
                            'is_completed' => $checklistItem['completed'] ?? false,
                            'position' => $checklistPosition,
                        ]);
                    }
                }
            }

            $this->seedNotes($project, $administrator);
        });
    }

    /**
     * @return array<string, Label>
     */
    private function seedLabels(User $administrator): array
    {
        $labels = [];

        foreach (self::LABELS as $name => $colorCode) {
            $label = Label::query()
                ->where('user_id', $administrator->getKey())
                ->where('name', $name)
                ->first();

            if (! $label instanceof Label) {
                $label = new Label;
                $label->user_id = $administrator->getKey();
                $label->name = $name;
            }

            $label->color_code = $colorCode;
            $label->save();
            $labels[$name] = $label;
        }

        return $labels;
    }

    private function seedNotes(Project $project, User $administrator): void
    {
        $this->saveProjectNote(
            $project,
            $administrator,
            'Product discovery and rollout plan',
            <<<'HTML'
                <h2>Project purpose</h2>
                <p>HiveTasks Demo Project is a sample workspace for exploring the full project workflow, from early planning through delivery and post-release support. It gives administrators realistic cards, labels, checklists, and project notes to review without creating sample content by hand.</p>
                <p>The initial release focuses on a clear Kanban workflow, predictable project ownership, and a comfortable experience for teams that switch between planning, implementation, review, and release work throughout the day.</p>
                <h2>Product goals</h2>
                <ul>
                    <li>Make current work and the next useful action easy to understand at a glance.</li>
                    <li>Keep each project’s board, notes, labels, and progress visible in one place.</li>
                    <li>Support accessible editing for task descriptions, labels, and checklists.</li>
                    <li>Give administrators enough sample data to validate everyday workflows and edge cases.</li>
                </ul>
                <h2>Delivery approach</h2>
                <p>Work moves through Todo, In Progress, Done, and Deployed. Each card should have a concise title, enough description to explain its purpose, and one or more labels that make it easier to scan the board. Checklist items are used when a task contains several separately verifiable steps.</p>
                <p>Changes should be reviewed against both light and dark themes, checked at desktop and mobile widths, and confirmed against the correct project owner. Any issue affecting tenant boundaries, data loss, or saved rich text should be treated as a release blocker.</p>
                <h2>Success measures</h2>
                <ol>
                    <li>Project members can locate current work and understand its state without opening every card.</li>
                    <li>Task edits, label assignments, checklist progress, and project notes remain saved after navigation or reload.</li>
                    <li>Administrative actions remain limited to authorized accounts and confirm destructive changes.</li>
                    <li>The demo workspace can be re-seeded to restore a consistent baseline for reviews and manual QA.</li>
                </ol>
                <h2>Open questions for the pilot</h2>
                <p>Collect feedback about which board signals are most useful, whether label colors are distinct enough, and which project details teams want to keep in notes. Record confusing behavior with a short reproduction path and the browser viewport where it occurred.</p>
                HTML,
        );

        $this->saveProjectNote(
            $project,
            $administrator,
            'QA checklist and release handoff',
            <<<'HTML'
                <h2>Before the release review</h2>
                <p>Use this note as a handoff checklist for the demo workspace. Verify that common project actions work together and saved state remains consistent after a page refresh.</p>
                <ul>
                    <li>Open every board column and confirm cards remain readable without horizontal compression.</li>
                    <li>Move a task between columns, reload the board, and confirm its new position remains saved.</li>
                    <li>Edit a task description, add and remove labels, and complete a checklist item.</li>
                    <li>Open project notes, switch between notes, edit rich text, and confirm it survives navigation.</li>
                    <li>Compare dashboard completion against the tasks in Done and Deployed.</li>
                </ul>
                <h2>Support handoff</h2>
                <p>For each reported issue, capture the project, task or note title, the action taken, and the expected result. Include whether the issue can be reproduced in both themes. Keep personal or customer data out of screenshots shared for the demo review.</p>
                <blockquote>Release readiness means the saved project state is understandable, recoverable, and scoped to the correct account.</blockquote>
                <h2>Known review areas</h2>
                <p>Pay extra attention to label contrast in dark theme, checklist progress on cards with no completed items, and the empty states shown when a project has no notes. These are intentional review points for the sample data.</p>
                HTML,
        );
    }

    private function saveProjectNote(Project $project, User $administrator, string $title, string $content): void
    {
        $note = $project->notes()->where('title', $title)->first() ?? new Note;
        $note->user_id = $administrator->getKey();
        $note->project_id = $project->getKey();
        $note->title = $title;
        $note->content = $content;
        $note->save();
    }

    private function projectDescription(): string
    {
        return <<<'HTML'
            <h2>Overview</h2>
            <p>This is a fully populated HiveTasks demonstration project for product reviews, development walkthroughs, and hands-on testing. It includes a Kanban board with twenty representative tasks across the delivery lifecycle, three reusable labels, selected multi-step checklists, and two detailed project notes.</p>
            <p>The sample represents a small team preparing a collaborative project workspace for a pilot release. The team is improving planning, board usability, task editing, project documentation, and support handoff while keeping tenant ownership and saved content reliable.</p>
            <h2>What to explore</h2>
            <ul>
                <li>Compare planned work in Todo with active implementation and completed delivery work.</li>
                <li>Open cards to review rich descriptions, change labels, and work through checklist items.</li>
                <li>Use the feature, bug, and support labels to scan the board by type of work.</li>
                <li>Open the Notes tab to review the release plan and QA handoff content.</li>
                <li>Check project progress and recent activity from the home dashboard.</li>
            </ul>
            <h2>Working agreements</h2>
            <p>Keep task titles short and action-oriented. Put implementation context and acceptance details in the description, then use checklist items for concrete steps that can be verified independently. Add labels when they help someone understand the work at a glance.</p>
            <p>Before moving a task to Done, verify its checklist, confirm that the saved description reflects the delivered behavior, and note follow-up work as a separate card. Move work to Deployed only after release checks and support handoff are complete.</p>
            <h2>Testing notes</h2>
            <p>This project is safe to use for manual UI testing. Re-running the demo seeder refreshes its description, seeded task content, label assignments, checklist items, and notes. Other projects and accounts are left untouched.</p>
            HTML;
    }

    /**
     * @return array<string, list<array{
     *     title: string,
     *     description: string,
     *     labels: list<string>,
     *     checklist?: list<array{title: string, completed?: bool}>
     * }>>
     */
    private function taskDefinitions(): array
    {
        return [
            'Todo' => [
                [
                    'title' => 'Map the product onboarding journey',
                    'description' => 'Document the steps a new project owner follows from first login through creating a project, organizing a board, and inviting collaborators. Highlight confusing decisions and places where clear guidance could reduce support requests.',
                    'labels' => ['feature', 'support'],
                    'checklist' => [
                        ['title' => 'List first-login decisions'],
                        ['title' => 'Review project creation flow'],
                        ['title' => 'Collect pilot feedback questions'],
                    ],
                ],
                [
                    'title' => 'Define workspace roles and permissions',
                    'description' => 'Write down which actions belong to project owners and platform administrators. Use current behavior as the baseline and call out permission gaps that need product review.',
                    'labels' => ['feature'],
                ],
                [
                    'title' => 'Inventory tenant data isolation checks',
                    'description' => 'Prepare a review of project, task, label, checklist, and note access boundaries. Include direct URL access and Livewire actions so both page rendering and server-side updates are covered.',
                    'labels' => ['bug', 'support'],
                    'checklist' => [
                        ['title' => 'Review project ownership queries'],
                        ['title' => 'Check task action ownership guards'],
                        ['title' => 'Verify note and label scoping'],
                    ],
                ],
                [
                    'title' => 'Gather pilot team feedback categories',
                    'description' => 'Create a feedback template covering board navigation, card editing, rich text, labels, checklist usefulness, accessibility, and visual contrast. Keep reports specific enough to turn into actionable work.',
                    'labels' => ['support'],
                ],
                [
                    'title' => 'Document release rollback steps',
                    'description' => 'Describe how the team will pause a rollout, communicate a known issue, and restore stable behavior if the pilot finds a blocker. Include a decision owner and recovery status.',
                    'labels' => ['feature', 'support'],
                ],
            ],
            'In Progress' => [
                [
                    'title' => 'Implement reusable invitation flow',
                    'description' => 'Build a consistent invitation experience that gives project owners clear feedback when a teammate is added. Validate duplicate addresses, explain existing accounts, and keep permission changes explicit.',
                    'labels' => ['feature'],
                    'checklist' => [
                        ['title' => 'Confirm invitation requirements', 'completed' => true],
                        ['title' => 'Build success and error states'],
                        ['title' => 'Review role assignment behavior'],
                    ],
                ],
                [
                    'title' => 'Fix stale task counts after drag and drop',
                    'description' => 'Reproduce cases where board or dashboard counts appear out of date after a card moves. Check for stale relationships, incomplete Livewire refreshes, or counts that still include the previous column.',
                    'labels' => ['bug'],
                ],
                [
                    'title' => 'Add accessibility labels to rich text controls',
                    'description' => 'Review the editor toolbar and provide accessible names for formatting controls. Ensure focus remains visible and keyboard users can reach the content area and save or cancel actions.',
                    'labels' => ['feature', 'support'],
                    'checklist' => [
                        ['title' => 'Audit editor toolbar buttons'],
                        ['title' => 'Verify keyboard focus order'],
                    ],
                ],
                [
                    'title' => 'Resolve note project-link selector edge case',
                    'description' => 'Check behavior when a note is unlinked, linked to a project, and moved between projects. Verify that the selected context remains correct when returning to the notes workspace.',
                    'labels' => ['bug', 'feature'],
                ],
                [
                    'title' => 'Prepare support escalation playbook',
                    'description' => 'Give the pilot support contact a concise path for collecting reproducible issue details, identifying a likely owner, and communicating the next update without promising an unverified fix date.',
                    'labels' => ['support'],
                ],
            ],
            'Done' => [
                [
                    'title' => 'Add project health metrics to dashboard',
                    'description' => 'Summarize total task and note counts and show a completion rate based on Done and Deployed work. Include project-level progress so an administrator can spot active workspaces.',
                    'labels' => ['feature'],
                    'checklist' => [
                        ['title' => 'Define completion columns', 'completed' => true],
                        ['title' => 'Review project progress display', 'completed' => true],
                        ['title' => 'Check zero-task workspace state', 'completed' => true],
                    ],
                ],
                [
                    'title' => 'Prevent duplicate checklist entries in board saves',
                    'description' => 'Confirm checklist positions remain stable and repeated save requests do not create duplicate entries. Verify checklist rows are removed when the parent task is deleted.',
                    'labels' => ['bug'],
                ],
                [
                    'title' => 'Review admin user creation validation',
                    'description' => 'Verify the administrator can create a user with a unique email, a secure temporary password, and an attached role. Confirm that the account receives a usable starter project structure.',
                    'labels' => ['support', 'feature'],
                ],
                [
                    'title' => 'Create responsive project summary cards',
                    'description' => 'Present projects as a responsive card grid that preserves useful project metadata on smaller screens and provides a clear path to the project board.',
                    'labels' => ['feature'],
                ],
                [
                    'title' => 'Audit label ownership checks',
                    'description' => 'Review label creation and assignment paths to ensure a task cannot be associated with a label owned by another account. Confirm invalid IDs fail safely without changing existing assignments.',
                    'labels' => ['bug', 'support'],
                ],
            ],
            'Deployed' => [
                [
                    'title' => 'Ship Kanban drag-scroll interactions',
                    'description' => 'Allow the board to be panned horizontally by holding and dragging on empty board space while preserving normal card clicks and controls. Confirm it remains usable on trackpads and touch devices.',
                    'labels' => ['feature'],
                ],
                [
                    'title' => 'Repair dark theme selected note contrast',
                    'description' => 'Adjust the selected note state so it remains easy to identify without creating a harsh white block in dark theme. Check text, border, and focus contrast together.',
                    'labels' => ['bug'],
                    'checklist' => [
                        ['title' => 'Review selected note background', 'completed' => true],
                        ['title' => 'Verify title and body contrast', 'completed' => true],
                    ],
                ],
                [
                    'title' => 'Publish onboarding guide for pilot admins',
                    'description' => 'Provide a walkthrough of the main workspace, project tabs, board controls, task editor, labels, checklists, and project notes so first-time pilot administrators can start using the demo.',
                    'labels' => ['support'],
                ],
                [
                    'title' => 'Verify note rich editor autosave flow',
                    'description' => 'Check that rich text content is preserved while editing and after saving. Include lists, headings, links, and empty content, then reload the page to verify the stored result.',
                    'labels' => ['feature', 'bug'],
                    'checklist' => [
                        ['title' => 'Save formatted paragraph', 'completed' => true],
                        ['title' => 'Save heading and list content', 'completed' => true],
                        ['title' => 'Reload and compare saved content', 'completed' => true],
                    ],
                ],
                [
                    'title' => 'Add seeded sample labels to demo tenant',
                    'description' => 'Provide feature, bug, and support labels with distinct colors so the demo board can be scanned by work type and task label controls can be exercised during product reviews.',
                    'labels' => ['support', 'feature'],
                ],
            ],
        ];
    }
}
