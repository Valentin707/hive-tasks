import { DragDropManager } from 'drag-drop-manager';
import './quill-rich-text-editor.js';

document.addEventListener('DOMContentLoaded', () => {
    const dragDropManagers = new WeakMap();

    const liveDate = document.querySelector('[data-live-date]');
    const liveClock = document.querySelector('[data-live-clock]');

    const updateDateAndClock = () => {
        const currentTime = new Date();

        if (liveDate instanceof HTMLTimeElement) {
            liveDate.textContent = new Intl.DateTimeFormat(undefined, {
                weekday: 'long',
                month: 'long',
                day: 'numeric',
                year: 'numeric',
            }).format(currentTime);
            liveDate.dateTime = [
                currentTime.getFullYear(),
                String(currentTime.getMonth() + 1).padStart(2, '0'),
                String(currentTime.getDate()).padStart(2, '0'),
            ].join('-');
        }

        if (liveClock instanceof HTMLTimeElement) {
            const hours = String(currentTime.getHours()).padStart(2, '0');
            const minutes = String(currentTime.getMinutes()).padStart(2, '0');

            liveClock.textContent = `${hours}:${minutes}`;
            liveClock.dateTime = `${hours}:${minutes}`;
        }
    };

    updateDateAndClock();
    window.setInterval(updateDateAndClock, 1000);

    const themeToggle = document.querySelector('[data-theme-toggle]');

    if (themeToggle instanceof HTMLButtonElement) {
        const themeStatus = document.querySelector('[data-theme-status]');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        const applyTheme = (theme) => {
            const isDark = theme === 'dark';
            const nextTheme = isDark ? 'light' : 'dark';

            document.documentElement.classList.toggle('dark', isDark);
            themeToggle.setAttribute('aria-pressed', String(isDark));
            themeToggle.setAttribute('aria-label', `Switch to ${nextTheme} mode`);

            const themeLabel = themeToggle.querySelector('[data-theme-label]');
            if (themeLabel) {
                themeLabel.textContent = `${nextTheme === 'dark' ? 'Dark' : 'Light'} theme`;
            }

            themeToggle.querySelectorAll('[data-theme-icon]').forEach((icon) => {
                icon.classList.toggle('hidden', icon.dataset.themeIcon !== nextTheme);
            });
        };

        themeToggle.addEventListener('click', async () => {
            const nextTheme = document.documentElement.classList.contains('dark') ? 'light' : 'dark';

            themeToggle.disabled = true;
            themeToggle.setAttribute('aria-busy', 'true');
            if (themeStatus) {
                themeStatus.textContent = '';
            }

            try {
                const response = await fetch(themeToggle.dataset.themeUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ theme: nextTheme }),
                });
                const payload = await response.json();

                if (!response.ok || payload.theme !== nextTheme) {
                    throw new Error('Unable to save the theme preference.');
                }

                applyTheme(payload.theme);
            } catch {
                if (themeStatus) {
                    themeStatus.textContent = 'Could not save the theme preference. Please try again.';
                }
            } finally {
                themeToggle.disabled = false;
                themeToggle.removeAttribute('aria-busy');
            }
        });
    }

    document.querySelectorAll('[data-kanban-board]').forEach((board) => {
        const dragDropManager = new DragDropManager();

        dragDropManager.enableDropzone(board.querySelectorAll('[data-kanban-column]'));
        dragDropManagers.set(board, dragDropManager);

        let columnDragState = null;

        const getColumns = (container) => [...container.children]
            .filter((element) => element.matches('[data-kanban-column-wrapper]'));

        const restoreColumnOrder = (state) => {
            state.originalColumns.forEach((column, index) => {
                state.container.insertBefore(column, state.container.children[index] ?? null);
            });
        };

        const finishColumnDrag = (pointerId, shouldSaveOrder) => {
            const state = columnDragState;

            if (!state || state.pointerId !== pointerId) {
                return;
            }

            const columns = getColumns(state.container);
            const newPosition = columns.indexOf(state.column);
            const orderChanged = columns.some((column, index) => column !== state.originalColumns[index]);

            if (shouldSaveOrder && orderChanged && newPosition >= 0) {
                state.column.dispatchEvent(new CustomEvent('column-reordered', {
                    bubbles: true,
                    detail: {
                        columnId: Number(state.column.dataset.columnId),
                        newPosition,
                        originalColumnIds: state.originalColumns.map((column) => Number(column.dataset.columnId)),
                    },
                }));
            } else if (!shouldSaveOrder || !orderChanged) {
                restoreColumnOrder(state);
            }

            state.column.classList.remove('opacity-60');
            state.handle.removeAttribute('aria-grabbed');
            state.boardSection.dispatchEvent(new CustomEvent('column-reorder-end'));

            if (state.handle.hasPointerCapture(pointerId)) {
                state.handle.releasePointerCapture(pointerId);
            }

            columnDragState = null;
        };

        board.addEventListener('pointerdown', (event) => {
            const handle = event.target instanceof Element
                ? event.target.closest('[data-column-drag-handle]')
                : null;
            const column = handle?.closest('[data-kanban-column-wrapper]');
            const container = column?.parentElement;
            const boardSection = container?.closest('[data-kanban-board-section]');

            if (
                columnDragState
                || event.button !== 0
                || event.isPrimary === false
                || !handle
                || !column
                || !container?.matches('[data-kanban-columns]')
                || !boardSection
            ) {
                return;
            }

            const originalColumns = getColumns(container);

            columnDragState = {
                pointerId: event.pointerId,
                handle,
                column,
                container,
                boardSection,
                originalColumns,
                startX: event.clientX,
                startY: event.clientY,
                moved: false,
            };

            try {
                handle.setPointerCapture(event.pointerId);
            } catch {
                // Document-level listeners still complete the drag if capture is unavailable.
            }
            boardSection.dispatchEvent(new CustomEvent('column-reorder-start'));
            handle.setAttribute('aria-grabbed', 'true');
        });

        document.addEventListener('pointermove', (event) => {
            const state = columnDragState;

            if (!state || state.pointerId !== event.pointerId) {
                return;
            }

            const movedX = event.clientX - state.startX;
            const movedY = event.clientY - state.startY;

            if (!state.moved && Math.hypot(movedX, movedY) < 4) {
                return;
            }

            state.moved = true;
            state.column.classList.add('opacity-60');
            event.preventDefault();

            const containerRect = state.container.getBoundingClientRect();

            if (
                event.clientX < containerRect.left
                || event.clientX > containerRect.right
                || event.clientY < containerRect.top
                || event.clientY > containerRect.bottom
            ) {
                return;
            }

            const columns = getColumns(state.container).filter((column) => column !== state.column);

            if (columns.length === 0) {
                return;
            }

            const hitElement = document.elementFromPoint(event.clientX, event.clientY);
            const hitColumn = hitElement instanceof Element
                ? hitElement.closest('[data-kanban-column-wrapper]')
                : null;
            if (hitColumn === state.column) {
                return;
            }

            const target = hitColumn && hitColumn.parentElement === state.container
                ? hitColumn
                : columns.reduce((closest, candidate) => {
                    const rect = candidate.getBoundingClientRect();
                    const distance = ((event.clientX - (rect.left + rect.width / 2)) / rect.width) ** 2
                        + ((event.clientY - (rect.top + rect.height / 2)) / rect.height) ** 2;

                    return distance < closest.distance ? { column: candidate, distance } : closest;
                }, { column: columns[0], distance: Number.POSITIVE_INFINITY }).column;

            const rect = target.getBoundingClientRect();
            const gridColumnCount = getComputedStyle(state.container).gridTemplateColumns.split(' ').length;
            const useHorizontalAxis = gridColumnCount > 1;
            const insertAfter = useHorizontalAxis
                ? event.clientX > rect.left + rect.width / 2
                : event.clientY > rect.top + rect.height / 2;
            const anchor = insertAfter ? target.nextElementSibling : target;

            if (anchor !== state.column) {
                state.container.insertBefore(state.column, anchor);
            }
        });

        document.addEventListener('pointerup', (event) => {
            const state = columnDragState;

            if (!state || state.pointerId !== event.pointerId) {
                return;
            }

            const rect = state.container.getBoundingClientRect();
            const pointerInsideContainer = event.clientX >= rect.left
                && event.clientX <= rect.right
                && event.clientY >= rect.top
                && event.clientY <= rect.bottom;

            finishColumnDrag(event.pointerId, pointerInsideContainer);
        });

        document.addEventListener('pointercancel', (event) => {
            finishColumnDrag(event.pointerId, false);
        });
    });

    new MutationObserver((records) => {
        records.flatMap((record) => [...record.removedNodes]).forEach((node) => {
            if (!(node instanceof Element)) {
                return;
            }

            const removedBoards = [node, ...node.querySelectorAll('[data-kanban-board]')];

            removedBoards.forEach((board) => {
                if (board.matches('[data-kanban-board]') && !board.isConnected) {
                    dragDropManagers.get(board)?.destroy();
                    dragDropManagers.delete(board);
                }
            });
        });
    }).observe(document.body, { childList: true, subtree: true });

    const sidebar = document.querySelector('[data-mobile-sidebar]');
    const overlay = document.querySelector('[data-sidebar-overlay]');
    const menuButtons = document.querySelectorAll('[data-sidebar-toggle]');

    if (!sidebar || !overlay || menuButtons.length === 0) {
        return;
    }

    const desktopBreakpoint = window.matchMedia('(min-width: 1024px)');

    const closeSidebar = () => {
        sidebar.classList.add('-translate-x-full');
        sidebar.inert = !desktopBreakpoint.matches;
        overlay.classList.add('hidden');
        menuButtons.forEach((button) => button.setAttribute('aria-expanded', 'false'));
    };

    const openSidebar = () => {
        sidebar.classList.remove('-translate-x-full');
        sidebar.inert = false;
        overlay.classList.remove('hidden');
        menuButtons.forEach((button) => button.setAttribute('aria-expanded', 'true'));
    };

    sidebar.inert = !desktopBreakpoint.matches;

    menuButtons.forEach((button) => {
        button.addEventListener('click', openSidebar);
    });

    overlay.addEventListener('click', closeSidebar);

    sidebar.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            if (!desktopBreakpoint.matches) {
                closeSidebar();
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !desktopBreakpoint.matches) {
            closeSidebar();
        }
    });

    desktopBreakpoint.addEventListener('change', closeSidebar);
});
