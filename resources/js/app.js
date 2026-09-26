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

const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

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

const setupMobileNavigation = () => {
    const drawer = document.querySelector('[data-bookresa-sidebar]');
    const backdrop = document.querySelector('[data-bookresa-sidebar-backdrop]');
    const closeButtons = document.querySelectorAll('[data-bookresa-sidebar-close]');
    const toggleButtons = document.querySelectorAll('[data-bookresa-sidebar-toggle]');
    const desktopQuery = window.matchMedia('(min-width: 1024px)');
    let isOpen = false;

    if (!drawer || !backdrop) {
        return;
    }

    const syncAccessibility = () => {
        drawer.setAttribute('aria-hidden', desktopQuery.matches || isOpen ? 'false' : 'true');
    };

    const setOpen = (open, restoreFocus = true) => {
        isOpen = open;
        drawer.classList.toggle('is-open', open);
        backdrop.classList.toggle('is-open', open);
        document.body.classList.toggle('overflow-hidden', open);

        toggleButtons.forEach((button) => {
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        syncAccessibility();

        if (open) {
            closeButtons[0]?.focus();
        } else if (restoreFocus) {
            toggleButtons[0]?.focus();
        }
    };

    syncAccessibility();

    toggleButtons.forEach((button) => {
        button.addEventListener('click', () => setOpen(true));
    });

    backdrop.addEventListener('click', () => setOpen(false));

    closeButtons.forEach((button) => {
        button.addEventListener('click', () => setOpen(false));
    });

    drawer.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setOpen(false, false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isOpen) {
            setOpen(false);
        }
    });

    desktopQuery.addEventListener('change', (event) => {
        if (event.matches) {
            setOpen(false, false);
            return;
        }

        syncAccessibility();
    });
};

const setupUtilities = () => {
    setupTheme();
    setupMobileNavigation();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupUtilities, { once: true });
} else {
    setupUtilities();
}
