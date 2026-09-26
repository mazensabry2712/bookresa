const locale = document.documentElement.lang;

document.documentElement.dir = locale === 'ar' ? 'rtl' : 'ltr';

const supportedThemes = ['light', 'dark', 'system'];
const storedTheme = localStorage.getItem('bookresa-theme');
const theme = supportedThemes.includes(storedTheme ?? '') ? storedTheme : 'system';

const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
const shouldUseDark = theme === 'dark' || (theme === 'system' && prefersDark);

document.documentElement.classList.toggle('dark', shouldUseDark);
document.documentElement.dataset.theme = theme;

window.BookResaTheme = {
    set(nextTheme) {
        const next = supportedThemes.includes(nextTheme) ? nextTheme : 'system';

        localStorage.setItem('bookresa-theme', next);
        document.documentElement.classList.toggle(
            'dark',
            next === 'dark' || (next === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches),
        );
        document.documentElement.dataset.theme = next;
    },

    current() {
        return document.documentElement.dataset.theme ?? 'system';
    },
};
