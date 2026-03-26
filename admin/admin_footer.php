<?php if (basename($_SERVER['PHP_SELF']) !== 'admin_login.php'): ?>
            </div> <!-- End p-4 -->
        </div> <!-- End Main Content col-md-10 -->
    </div> <!-- End row -->
</div> <!-- End container-fluid -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show Password Toggle Logic
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('show-password-toggle')) {
            const parent = e.target.closest('.mb-3') || e.target.closest('.mb-4') || e.target.parentElement;
            const input = parent.querySelector('input[type="password"], input[data-show-pw="true"]');
            if (input) {
                if (e.target.checked) {
                    input.type = 'text';
                    input.setAttribute('data-show-pw', 'true');
                } else {
                    input.type = 'password';
                }
            }
        }
    });
});
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarCollapseBtn = document.getElementById('sidebarCollapseTrigger');
        const sidebar = document.querySelector('.admin-sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        const body = document.body;
        
        // Initial state from localStorage for Desktop
        if (window.innerWidth >= 992) {
            const isCollapsed = localStorage.getItem('adminSidebarCollapsed') === 'true';
            if (isCollapsed) {
                body.classList.add('sidebar-collapsed');
            }
        }

        if (sidebar && backdrop) {
            // Desktop Collapse Handler
            if (sidebarCollapseBtn) {
                sidebarCollapseBtn.addEventListener('click', function() {
                    if (window.innerWidth >= 992) {
                        body.classList.toggle('sidebar-collapsed');
                        localStorage.setItem('adminSidebarCollapsed', body.classList.contains('sidebar-collapsed'));
                    } else {
                        // On mobile, this button acts as another close trigger
                        closeSidebar();
                    }
                });
            }

            // Mobile Drawer Logic
            function openSidebar() {
                sidebar.classList.add('show');
                backdrop.classList.add('show');
                body.classList.add('sidebar-open');
                body.style.overflow = 'hidden';
            }
            
            function closeSidebar() {
                sidebar.classList.remove('show');
                backdrop.classList.remove('show');
                body.classList.remove('sidebar-open');
                body.style.overflow = '';
            }
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (sidebar.classList.contains('show')) {
                        closeSidebar();
                    } else {
                        openSidebar();
                    }
                });
            }
            
            backdrop.addEventListener('click', closeSidebar);
            
            // Auto close links on mobile
            document.querySelectorAll('.admin-sidebar .list-group-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    if (this.hasAttribute('data-mdb-toggle') || this.getAttribute('href') === '#') return;
                    if (window.innerWidth < 992) setTimeout(closeSidebar, 200);
                });
            });
        }
    });
</script>
<?php endif; ?>
<!-- MDB JS -->
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.js"></script>
</body>
</html>
