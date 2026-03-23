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
        const sidebar = document.querySelector('.admin-sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        const sidebarLinks = document.querySelectorAll('.admin-sidebar .list-group-item[href]');
        
        if (sidebarToggle && sidebar && backdrop) {
            function openSidebar() {
                sidebar.classList.add('show');
                backdrop.classList.add('show');
                document.body.classList.add('sidebar-open');
                document.body.style.overflow = 'hidden';
            }
            
            function closeSidebar() {
                sidebar.classList.remove('show');
                backdrop.classList.remove('show');
                document.body.classList.remove('sidebar-open');
                document.body.style.overflow = '';
            }
            
            function toggleSidebar() {
                if (sidebar.classList.contains('show')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            }
            
            sidebarToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleSidebar();
            });
            
            backdrop.addEventListener('click', closeSidebar);
            
            // Handle sidebar items on mobile
            document.querySelectorAll('.admin-sidebar .list-group-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    // If it's a collapse toggle (dropdown), don't close the sidebar
                    if (this.hasAttribute('data-mdb-toggle') || this.getAttribute('href') === '#') {
                        e.stopPropagation();
                        return;
                    }
                    
                    // If it's a real link and we are on mobile, close the sidebar after a delay
                    if (window.innerWidth < 992 && this.hasAttribute('href')) {
                        setTimeout(closeSidebar, 200);
                    }
                });
            });

            // Prevent dropdown clicks from closing sidebar
            document.querySelectorAll('.dropdown-toggle, .dropdown-menu').forEach(el => {
                el.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            });

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth < 992 && 
                    sidebar.classList.contains('show') && 
                    !sidebar.contains(e.target) && 
                    !sidebarToggle.contains(e.target)) {
                    closeSidebar();
                }
            });
        }
    });
</script>
<?php endif; ?>
<!-- MDB JS -->
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.js"></script>
</body>
</html>
