<?php
// includes/admin_footer.php - Admin footer
?>
        </div> <!-- End content wrapper -->
    </div> <!-- End main-content -->
    
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Logout confirmation for all logout links
        document.querySelectorAll('a[href="logout.php"]').forEach(function(link) {
            link.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to logout?')) {
                    e.preventDefault();
                }
            });
        });
        
        // Mobile menu toggle
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const topNavbar = document.querySelector('.top-navbar');
            
            sidebar.classList.toggle('mobile-hidden');
            mainContent.classList.toggle('mobile-full');
            topNavbar.classList.toggle('mobile-full');
        }
    </script>
</body>
</html>