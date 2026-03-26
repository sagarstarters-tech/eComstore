/**
 * Theme Toggle Script for Dark/Light Mode
 */
document.addEventListener('DOMContentLoaded', () => {
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    if (!themeToggleBtn) return;

    const icon = themeToggleBtn.querySelector('i');
    
    // Check local storage for theme preference
    const currentTheme = localStorage.getItem('theme') || 'light';
    
    // Apply initial theme
    applyTheme(currentTheme);

    themeToggleBtn.addEventListener('click', () => {
        const theme = document.documentElement.getAttribute('data-mdb-theme') === 'dark' ? 'light' : 'dark';
        applyTheme(theme);
        // Fallback for native bootstrap 5
        document.documentElement.setAttribute('data-bs-theme', theme);
    });

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-mdb-theme', theme);
        document.body.setAttribute('data-mdb-theme', theme);
        document.documentElement.setAttribute('data-bs-theme', theme);
        localStorage.setItem('theme', theme);
        
        // Custom CSS class toggle for specific styling overrides
        if (theme === 'dark') {
            document.body.classList.add('dark-mode-active');
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        } else {
            document.body.classList.remove('dark-mode-active');
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
        }
    }
});
