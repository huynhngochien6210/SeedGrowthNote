// Light/dark mode
const themeBtn= document.getElementById('themeBtn');
const themeIcon= document.getElementById('themeIcon');

// Update icon state
function updateThemeIcon(isDark){
    themeIcon.classList.toggle('bi-sun-fill', !isDark);
    themeIcon.classList.toggle('bi-moon-stars-fill', isDark);
}

// Toggle when click
function toggleTheme(){
    document.body.classList.toggle('dark-mode');
    const isDark= document.body.classList.contains('dark-mode');
    updateThemeIcon(isDark);
    localStorage.setItem('theme', isDark? 'dark': 'light');
}

// Read saved theme
function initTheme(){
    const savedTheme = localStorage.getItem('theme')
    const isDark = savedTheme === 'dark';
    if(isDark){
        document.body.classList.add('dark-mode');
    }
    updateThemeIcon(isDark);
    document.documentElement.classList.remove('dark-mode-preload');
}
themeBtn.addEventListener('click', toggleTheme);
initTheme();


// Menu
const menuBtn = document.getElementById('menuBtn');
const appSidebar = document.querySelector('.app-sidebar');
function toggleSidebar() {
  appSidebar.classList.toggle('show');
}
menuBtn.addEventListener('click', toggleSidebar);

// Resize lên desktop mà sidebar đang mở -> tự đóng lại
window.addEventListener('resize', () => {
  if (window.innerWidth >= 768 && appSidebar.classList.contains('show')) {
    appSidebar.classList.remove('show');
  } 
});


/* Panel navigation */
document.querySelectorAll('.nav-panel-btn').forEach(button => {
    button.addEventListener('click', function (event) {
        event.preventDefault();

        const target = this.dataset.panel;

        document.querySelectorAll('.app-panel').forEach(panel => {
            panel.classList.toggle(
                'active',
                panel.dataset.panelName === target
            );
        });
    });
});