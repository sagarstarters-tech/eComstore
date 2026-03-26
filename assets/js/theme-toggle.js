/**
 * Theme Toggle Script for Dark/Light Mode
 */
document.addEventListener('DOMContentLoaded', () => {
    console.log("Theme Toggle Script Loaded!");
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    if (!themeToggleBtn) {
        console.error("Theme Toggle Button not found in the DOM.");
        return;
    }

    const icon = themeToggleBtn.querySelector('i');
    
    // Check local storage for theme preference
    const currentTheme = localStorage.getItem('theme') || 'light';
    console.log("Initial theme is:", currentTheme);
    
    // Apply initial theme
    applyTheme(currentTheme);

    themeToggleBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const isDark = document.body.classList.contains('dark-mode-active');
        const newTheme = isDark ? 'light' : 'dark';
        console.log("Toggling theme to:", newTheme);
        applyTheme(newTheme);
        
        // Refresh the page after localized state is saved
        setTimeout(() => {
            window.location.reload();
        }, 100);
    });

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-mdb-theme', theme);
        document.body.setAttribute('data-mdb-theme', theme);
        document.documentElement.setAttribute('data-bs-theme', theme);
        localStorage.setItem('theme', theme);
        
        // Custom CSS class toggle for specific styling overrides
        if (theme === 'dark') {
            document.body.classList.add('dark-mode-active');
            if(icon) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            }
        } else {
            document.body.classList.remove('dark-mode-active');
            if(icon) {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        }
    }
});
