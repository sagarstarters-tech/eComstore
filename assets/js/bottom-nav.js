document.addEventListener('DOMContentLoaded', () => {
    const navItems = document.querySelectorAll('.bottom-nav-item');

    // 1. Set active item based on current URL pattern
    const currentPath = window.location.pathname;

    // We try to match the link href with the current path
    navItems.forEach(item => {
        try {
            const itemPath = new URL(item.href).pathname;

            // Basic path matching (e.g. /index.php == /index.php)
            if (currentPath === itemPath || (currentPath.endsWith('/') && itemPath.endsWith('index.php'))) {
                navItems.forEach(nav => nav.classList.remove('active'));
                item.classList.add('active');
            }
        } catch (e) { /* ignore invalid URLs */ }
    });

    // 2. Ripple click effect logic (Android-style)
    navItems.forEach(item => {
        item.addEventListener('click', function (e) {

            // Remove any existing ripples to prevent stacking
            let oldRipple = this.querySelector('.ripple');
            if (oldRipple) {
                oldRipple.remove();
            }

            // Create new ripple span element
            let ripple = document.createElement('span');
            ripple.classList.add('ripple');
            this.appendChild(ripple);

            // Calculate exact position relative to click
            let rect = this.getBoundingClientRect();
            // Size of ripple (max of width or height of nav item)
            let size = Math.max(rect.width, rect.height);
            ripple.style.width = size + 'px';
            ripple.style.height = size + 'px';

            // Center the ripple spawn on the exact click coordinates
            ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
            ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';

            // Visually change active state instantly for fast feedback
            // Because PHP causes a reload, this simply adds polish before the page clears.
            navItems.forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');
        });
    });
});
