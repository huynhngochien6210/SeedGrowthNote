
/* ===== Theme ===== */

const themeBtn = document.getElementById('themeBtn');
const themeIcon = document.getElementById('themeIcon');

function updateThemeIcon(isDark) {
    if (!themeIcon) return;

    themeIcon.classList.toggle('bi-sun-fill', !isDark);
    themeIcon.classList.toggle('bi-moon-stars-fill', isDark);
}

function toggleTheme() {
    document.body.classList.toggle('dark-mode');

    const isDark = document.body.classList.contains('dark-mode');

    updateThemeIcon(isDark);
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
}

function initTheme() {
    const savedTheme = localStorage.getItem('theme');
    const isDark = savedTheme === 'dark';

    if (isDark) {
        document.body.classList.add('dark-mode');
    }

    updateThemeIcon(isDark);
    document.documentElement.classList.remove('dark-mode-preload');
}

themeBtn?.addEventListener('click', toggleTheme);
initTheme();


/* ===== Sidebar ===== */

const menuBtn = document.getElementById('menuBtn');
const appSidebar = document.querySelector('.app-sidebar');

function toggleSidebar() {
    appSidebar?.classList.toggle('show');
}

menuBtn?.addEventListener('click', toggleSidebar);

window.addEventListener('resize', () => {
    if (window.innerWidth >= 768) {
        appSidebar?.classList.remove('show');
    }
});

/* ===== Initial panel state ===== */

// Mặc định khi tải trang: hiển thị Notes
document.querySelectorAll('.app-panel').forEach(panel => {
    panel.classList.remove('active');
});

document.getElementById('noteBoardView')?.classList.remove('d-none');

document.getElementById('sharedBoardView')?.classList.add('d-none');

document.getElementById('filterTagsPanel')?.classList.add('d-none');

/* ===== Panel navigation ===== */

document.querySelectorAll('.nav-panel-btn').forEach(button => {
    button.addEventListener('click', function(event) {
        event.preventDefault();

        const target = this.dataset.panel;

        const noteBoard = document.getElementById('noteBoardView');
        const sharedBoard = document.getElementById('sharedBoardView');
        const filterPanel = document.getElementById('filterTagsPanel');

        const activePanel = document.querySelector('.app-panel.active');

        // Nhấn Account lần thứ hai -> quay về Notes
        const nextPanel =
            target === 'account' &&
            activePanel?.dataset.panelName === 'account'
                ? 'notes'
                : target;

        // Đóng toàn bộ Account/Password panel
        document.querySelectorAll('.app-panel').forEach(panel => {
            panel.classList.toggle(
                'active',
                panel.dataset.panelName === nextPanel
            );
        });

        // Ẩn Shared with me
        sharedBoard?.classList.add('d-none');

        // Ẩn Filter Tags
        if (filterPanel) {
            filterPanel.hidden = true;
            filterPanel.classList.add('d-none');

            const filterBtn = document.getElementById('filterTagsBtn');

            filterBtn?.setAttribute('aria-expanded', 'false');
            filterBtn?.classList.remove('is-active');
        }

        // Chỉ hiển thị Note Board khi quay về Notes
        noteBoard?.classList.toggle(
            'd-none',
            nextPanel !== 'notes'
        );

        // Đóng sidebar trên màn hình nhỏ
        appSidebar?.classList.remove('show');
    });
});
