import { DragDropManager } from 'drag-drop-manager';

document.addEventListener('DOMContentLoaded', () => {
    const dragDropManagers = new WeakMap();

    document.querySelectorAll('[data-kanban-board]').forEach((board) => {
        const dragDropManager = new DragDropManager();

        dragDropManager.enableDropzone(board.querySelectorAll('[data-kanban-column]'));
        dragDropManagers.set(board, dragDropManager);
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
