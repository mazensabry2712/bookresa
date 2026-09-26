const locale = document.documentElement.lang;

if (locale === 'ar') {
    document.documentElement.dir = 'rtl';
} else {
    document.documentElement.dir = 'ltr';
}
