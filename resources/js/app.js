const locale = document.documentElement.lang;

document.documentElement.dir = locale === 'ar' ? 'rtl' : 'ltr';

const supportedThemes = ['light', 'dark', 'system'];

const getStoredTheme = () => {
    try {
        return localStorage.getItem('bookresa-theme');
    } catch {
        return null;
    }
};

const storedTheme = getStoredTheme();
const theme = supportedThemes.includes(storedTheme ?? '') ? storedTheme : 'system';

const applyTheme = (nextTheme) => {
    const shouldUseDark =
        nextTheme === 'dark' ||
        (nextTheme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

    document.documentElement.classList.toggle('dark', shouldUseDark);
    document.documentElement.dataset.theme = nextTheme;
};

const syncThemeButtons = () => {
    document.querySelectorAll('[data-bookresa-theme]').forEach((button) => {
        const active = button.dataset.bookresaTheme === document.documentElement.dataset.theme;

        button.classList.toggle('bg-slate-900', active);
        button.classList.toggle('text-white', active);
        button.classList.toggle('dark:bg-white', active);
        button.classList.toggle('dark:text-slate-900', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
};

applyTheme(theme);

window.BookResaTheme = {
    set(nextTheme) {
        const next = supportedThemes.includes(nextTheme) ? nextTheme : 'system';

        try {
            localStorage.setItem('bookresa-theme', next);
        } catch {
            // Keep theme switching functional when storage is unavailable.
        }

        applyTheme(next);
        syncThemeButtons();
    },

    current() {
        return document.documentElement.dataset.theme ?? 'system';
    },
};

const setupTheme = () => {
    syncThemeButtons();

    document.querySelectorAll('[data-bookresa-theme]').forEach((button) => {
        button.addEventListener('click', () => {
            window.BookResaTheme.set(button.dataset.bookresaTheme);
        });
    });

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (window.BookResaTheme.current() === 'system') {
            applyTheme('system');
            syncThemeButtons();
        }
    });
};

const setupSidebar = () => {
    const shell = document.querySelector('.br-shell');
    const sidebar = document.querySelector('[data-bookresa-sidebar]');
    const backdrop = document.querySelector('[data-bookresa-sidebar-backdrop]');
    const mobileToggleButtons = document.querySelectorAll('[data-bookresa-sidebar-toggle]');
    const mobileCloseButtons = document.querySelectorAll('[data-bookresa-sidebar-close]');
    const collapseButton = document.querySelector('[data-bookresa-sidebar-collapse]');
    const userMenus = document.querySelectorAll('[data-user-menu]');
    const workspaceMenus = document.querySelectorAll('[data-workspace-switcher]');

    if (!shell || !sidebar) {
        return;
    }

    const desktopQuery = window.matchMedia('(min-width: 1024px)');
    let mobileOpen = false;

    const getCollapsed = () => {
        try {
            return localStorage.getItem('bookresa-sidebar-collapsed') === '1';
        } catch {
            return false;
        }
    };

    const setCollapsed = (collapsed, persist = true) => {
        const next = Boolean(collapsed);

        shell.classList.toggle('sidebar-collapsed', next);
        collapseButton?.setAttribute('aria-expanded', next ? 'false' : 'true');

        if (collapseButton) {
            const label = next
                ? (collapseButton.dataset.labelExpand ?? 'Expand sidebar')
                : (collapseButton.dataset.labelCollapse ?? 'Collapse sidebar');

            collapseButton.setAttribute('aria-label', label);
            collapseButton.setAttribute('title', label);
        }

        if (!persist) {
            return;
        }

        try {
            localStorage.setItem('bookresa-sidebar-collapsed', next ? '1' : '0');
        } catch {
            // Keep the sidebar usable when storage is unavailable.
        }
    };

    const syncAccessibility = () => {
        const visible = desktopQuery.matches || mobileOpen;
        sidebar.setAttribute('aria-hidden', visible ? 'false' : 'true');
    };

    const setMobileOpen = (open, restoreFocus = true) => {
        mobileOpen = Boolean(open);
        sidebar.classList.toggle('is-open', mobileOpen);
        backdrop?.classList.toggle('is-open', mobileOpen);
        document.body.classList.toggle('overflow-hidden', mobileOpen);

        mobileToggleButtons.forEach((button) => {
            button.setAttribute('aria-expanded', mobileOpen ? 'true' : 'false');
        });

        syncAccessibility();

        if (mobileOpen) {
            mobileCloseButtons[0]?.focus();
        } else if (restoreFocus) {
            mobileToggleButtons[0]?.focus();
        }
    };

    const closeMenus = (except = null) => {
        document.querySelectorAll('[data-user-menu-panel].is-open, [data-workspace-switcher-menu].is-open').forEach((panel) => {
            if (panel !== except) {
                panel.classList.remove('is-open');
            }
        });

        document.querySelectorAll('[data-user-menu-toggle][aria-expanded="true"], [data-workspace-switcher-toggle][aria-expanded="true"]').forEach((button) => {
            const panel = button.closest('[data-user-menu], [data-workspace-switcher]')?.querySelector('[data-user-menu-panel], [data-workspace-switcher-menu]');

            if (!except || panel !== except) {
                button.setAttribute('aria-expanded', 'false');
            }
        });
    };

    const setupDropdown = (root, toggleSelector, panelSelector) => {
        const toggle = root.querySelector(toggleSelector);
        const panel = root.querySelector(panelSelector);

        if (!toggle || !panel) {
            return;
        }

        toggle.addEventListener('click', (event) => {
            event.stopPropagation();
            const open = panel.classList.contains('is-open');

            closeMenus(panel);
            panel.classList.toggle('is-open', !open);
            toggle.setAttribute('aria-expanded', !open ? 'true' : 'false');
        });
    };

    setCollapsed(desktopQuery.matches ? getCollapsed() : false);
    syncAccessibility();

    collapseButton?.addEventListener('click', () => {
        setCollapsed(!shell.classList.contains('sidebar-collapsed'));
    });

    mobileToggleButtons.forEach((button) => {
        button.addEventListener('click', () => setMobileOpen(true));
    });

    mobileCloseButtons.forEach((button) => {
        button.addEventListener('click', () => setMobileOpen(false));
    });

    backdrop?.addEventListener('click', () => setMobileOpen(false));

    sidebar.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            if (!desktopQuery.matches) {
                setMobileOpen(false, false);
            }
        });
    });

    userMenus.forEach((root) => setupDropdown(root, '[data-user-menu-toggle]', '[data-user-menu-panel]'));
    workspaceMenus.forEach((root) => setupDropdown(root, '[data-workspace-switcher-toggle]', '[data-workspace-switcher-menu]'));

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-user-menu], [data-workspace-switcher]')) {
            closeMenus();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMenus();

            if (mobileOpen) {
                setMobileOpen(false);
            }
        }
    });

    desktopQuery.addEventListener('change', (event) => {
        if (event.matches) {
            setMobileOpen(false, false);
            setCollapsed(getCollapsed());
        } else {
            setCollapsed(false, false);
            syncAccessibility();
        }
    });
};

const setupResponsiveAdminTables = () => {
    if (!document.body.classList.contains('br-admin-shell')) {
        return;
    }

    document.querySelectorAll('main table').forEach((table) => {
        const headerCells = Array.from(table.querySelectorAll(':scope > thead > tr:last-child > th'));

        if (headerCells.length === 0) {
            return;
        }

        const headers = headerCells.map((cell) => cell.textContent.trim().replace(/\s+/g, ' '));
        table.classList.add('br-data-table');

        table.querySelectorAll(':scope > tbody > tr').forEach((row) => {
            Array.from(row.children).forEach((cell, index) => {
                if (!(cell instanceof HTMLTableCellElement) || cell.hasAttribute('colspan')) {
                    return;
                }

                const label = headers[index] ?? '';

                if (label !== '') {
                    cell.setAttribute('data-label', label);
                }
            });
        });
    });
};


const setupResponsiveAdminShell = () => {
    if (!document.body.classList.contains('br-admin-shell')) {
        return;
    }

    document.querySelectorAll('main form').forEach((form) => {
        form.classList.add('br-admin-form');
    });

    document.querySelectorAll('main [class*="space-y-6"], main [class*="space-y-5"]').forEach((container) => {
        const first = container.firstElementChild;

        if (first instanceof HTMLElement && first.querySelector(':scope > .flex')) {
            first.classList.add('br-admin-page-header');
        }
    });
};

const setupUtilities = () => {
    setupTheme();
    setupSidebar();
    setupResponsiveAdminTables();
    setupResponsiveAdminShell();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupUtilities, { once: true });
} else {
    setupUtilities();
}
