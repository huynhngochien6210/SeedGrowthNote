
/* Light/dark mode */
const themeBtn = document.getElementById('themeBtn');
const themeIcon = document.getElementById('themeIcon');

/* Update icon state */
function updateThemeIcon(isDark) {
    if (!themeIcon) return;

    themeIcon.classList.toggle('bi-sun-fill', !isDark);
    themeIcon.classList.toggle('bi-moon-stars-fill', isDark);
}

/* Toggle when click */
function toggleTheme() {
    document.body.classList.toggle('dark-mode');

    const isDark = document.body.classList.contains('dark-mode');

    updateThemeIcon(isDark);
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
}

/* Read saved theme */
function initTheme() {
    const savedTheme = localStorage.getItem('theme');
    const isDark = savedTheme === 'dark';

    document.body.classList.toggle('dark-mode', isDark);

    updateThemeIcon(isDark);
    document.documentElement.classList.remove('dark-mode-preload');
}

/* Register theme event only if button exists */
if (themeBtn) {
    themeBtn.addEventListener('click', toggleTheme);
}

initTheme();


/* Menu */
const menuBtn = document.getElementById('menuBtn');
const appSidebar = document.querySelector('.app-sidebar');

function toggleSidebar() {
    if (!appSidebar) return;

    appSidebar.classList.toggle('show');
}

/* Register menu event only if button and sidebar exist */
if (menuBtn && appSidebar) {
    menuBtn.addEventListener('click', toggleSidebar);
}

/* Resize to desktop: close sidebar if open */
window.addEventListener('resize', () => {
    if (
        window.innerWidth >= 768 &&
        appSidebar &&
        appSidebar.classList.contains('show')
    ) {
        appSidebar.classList.remove('show');
    }
});
