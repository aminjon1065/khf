/**
 * No-flash theme bootstrap. Runs before paint: when the user's preference is "system", apply the
 * dark class if the OS prefers dark. Explicit light/dark is already server-rendered on <html>.
 * Kept as a static 'self' file so the CSP needs no 'unsafe-inline' script-src (ТЗ §12.2).
 */
(function () {
    var el = document.documentElement;

    if (
        el.getAttribute('data-appearance') === 'system' &&
        window.matchMedia('(prefers-color-scheme: dark)').matches
    ) {
        el.classList.add('dark');
    }
})();
