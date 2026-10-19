(() => {
    const storageKey = 'moelci-admin-theme';
    let savedTheme;
    try { savedTheme = localStorage.getItem(storageKey); } catch (_) { /* Use the system preference when storage is unavailable. */ }
    const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    const initialTheme = ['light', 'dark'].includes(savedTheme) ? savedTheme : systemTheme;
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        const toggle = document.getElementById('theme-toggle');
        if (toggle) {
            toggle.hidden = false;
            toggle.setAttribute('aria-checked', String(theme === 'dark'));
            document.getElementById('theme-icon').setAttribute('href', theme === 'dark' ? '#icon-sun' : '#icon-moon');
        }
        document.dispatchEvent(new CustomEvent('admin-theme-change', { detail: { theme } }));
    }
    // Apply before styles load to avoid a flash of the other theme.
    applyTheme(initialTheme);
    document.addEventListener('DOMContentLoaded', () => {
        applyTheme(document.documentElement.getAttribute('data-bs-theme'));
        document.getElementById('theme-toggle').addEventListener('click', () => {
            const theme = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            try { localStorage.setItem(storageKey, theme); } catch (_) { /* Switching still works without persistence. */ }
            applyTheme(theme);
        });
    });
})();
