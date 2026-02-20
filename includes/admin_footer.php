<?php
// includes/admin_footer.php - Admin Footer
?>
            </div> <!-- End content-wrapper -->
        </main> <!-- End main-content -->
    </div> <!-- End admin-wrapper -->

    <!-- Footer - NOW OUTSIDE admin-wrapper, properly positioned -->
    <footer class="admin-footer">
        <div class="container-fluid px-3 px-md-4">
            <div class="row align-items-center gy-3 gy-md-0">
                <div class="col-12 col-md-6 text-center text-md-start">
                    <p class="footer-copyright mb-0 small">
                        &copy; <?php echo date('Y'); ?> <strong><?php echo defined('SITE_NAME') ? SITE_NAME : 'Jenny\'s Cosmetics & Jewelry'; ?></strong>.
                        <span class="d-block d-sm-inline mt-1 mt-sm-0 ms-sm-1">All rights reserved.</span>
                    </p>
                </div>
                <div class="col-12 col-md-6 text-center text-md-end">
                    <p class="footer-links mb-0 d-flex flex-wrap align-items-center justify-content-center justify-content-md-end gap-2 gap-sm-3">
                        <span class="version-badge">v1.0.0</span>
                        <span class="separator d-none d-sm-inline">|</span>
                        <a href="#" class="footer-link" data-bs-toggle="modal" data-bs-target="#aboutModal">
                            <i class="fas fa-info-circle"></i> <span class="d-inline">About</span>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Modals and Scripts -->
    <!-- About Modal -->
    <div class="modal fade" id="aboutModal" tabindex="-1" aria-labelledby="aboutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="aboutModalLabel">
                        <i class="fas fa-info-circle me-2" style="color: var(--primary);"></i>
                        About <?php echo defined('SITE_NAME') ? SITE_NAME : 'Jenny\'s Cosmetics & Jewelry'; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div class="about-icon-wrapper mx-auto">
                            <i class="fas fa-gem"></i>
                        </div>
                        <h4 class="mt-3 mb-1">Admin Panel</h4>
                        <p class="text-muted small">Enterprise Dashboard</p>
                    </div>
                    
                    <div class="about-info-grid">
                        <div class="info-row">
                            <span class="info-label">Application:</span>
                            <span class="info-value"><?php echo defined('SITE_NAME') ? SITE_NAME : 'Jenny\'s Cosmetics & Jewelry'; ?> - Admin Dashboard</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Version:</span>
                            <span class="info-value">1.0.0</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Developed by:</span>
                            <span class="info-value">Kabir Baloch</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Last Updated:</span>
                            <span class="info-value"><?php echo date('F j, Y'); ?></span>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="system-info">
                        <h6 class="system-info-title">System Information</h6>
                        <ul class="system-info-list">
                            <li><i class="fas fa-code me-2"></i>PHP Version: <strong><?php echo phpversion(); ?></strong></li>
                            <li><i class="fas fa-database me-2"></i>Database: <strong>MySQL</strong></li>
                            <li><i class="fas fa-server me-2"></i>Server: <strong><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Apache'; ?></strong></li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">
                        <i class="fas fa-sign-out-alt me-2" style="color: var(--warning);"></i>
                        Confirm Logout
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center py-3">
                        <i class="fas fa-question-circle" style="font-size: 3rem; color: var(--warning);"></i>
                        <p class="mt-3 mb-0">Are you sure you want to logout from the admin panel?</p>
                        <p class="small text-muted">You will be redirected to the login page.</p>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-center justify-content-sm-end gap-2">
                    <button type="button" class="btn btn-light w-100 w-sm-auto" data-bs-dismiss="modal">Cancel</button>
                    <a href="../admin/logout.php" class="btn btn-warning w-100 w-sm-auto">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal (Generic) -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="fas fa-trash-alt me-2" style="color: var(--danger);"></i>
                        Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center py-3">
                        <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--danger);"></i>
                        <p class="mt-3 mb-0">Are you sure you want to delete this item?</p>
                        <p class="small text-danger">This action cannot be undone.</p>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-center justify-content-sm-end gap-2">
                    <button type="button" class="btn btn-light w-100 w-sm-auto" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger w-100 w-sm-auto" id="confirmDeleteBtn">
                        <i class="fas fa-trash-alt me-2"></i>Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div class="loading-spinner" id="loadingSpinner">
        <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Back to Top Button -->
    <button class="back-to-top" id="backToTop" onclick="scrollToTop()" aria-label="Back to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Scripts -->
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Moment.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    
    <!-- Custom Admin Scripts -->
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Initialize popovers
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl)
        });

        // Back to top button
        window.onscroll = function() {
            if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
                document.getElementById("backToTop").style.display = "flex";
            } else {
                document.getElementById("backToTop").style.display = "none";
            }
        };

        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Loading spinner
        function showLoading() {
            document.getElementById('loadingSpinner').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loadingSpinner').style.display = 'none';
        }

        // Delete confirmation
        function confirmDelete(itemId, deleteUrl) {
            $('#deleteModal').modal('show');
            $('#confirmDeleteBtn').off('click').on('click', function() {
                window.location.href = deleteUrl + '?id=' + itemId;
            });
        }

        // Currency formatting
        function formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', { 
                style: 'currency', 
                currency: 'USD' 
            }).format(amount);
        }

        // Date formatting
        function formatDate(dateString, format = 'MMMM D, YYYY') {
            return moment(dateString).format(format);
        }

        // Notification system
        function showNotification(message, type = 'success') {
            if (!$('#toastContainer').length) {
                $('body').append('<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-2 p-sm-3"></div>');
            }
            
            const toastId = 'toast_' + Date.now();
            const icon = type === 'success' ? 'check-circle' : 
                        type === 'danger' ? 'exclamation-circle' :
                        type === 'warning' ? 'exclamation-triangle' : 'info-circle';
            
            const bgColor = type === 'success' ? '#d1e7dd' :
                           type === 'danger' ? '#f8d7da' :
                           type === 'warning' ? '#fff3cd' : '#cff4fc';
            
            const textColor = type === 'success' ? '#0a3622' :
                             type === 'danger' ? '#842029' :
                             type === 'warning' ? '#856404' : '#055160';
            
            const toastHTML = `
                <div id="${toastId}" class="toast show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="3000" style="background: ${bgColor}; color: ${textColor}; border-left: 4px solid var(--${type === 'danger' ? 'danger' : type === 'warning' ? 'warning' : type === 'info' ? 'info' : 'success'});">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-${icon} me-2"></i>${message}
                        </div>
                        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            $('#toastContainer').append(toastHTML);
            
            setTimeout(() => {
                $(`#${toastId}`).remove();
            }, 3000);
        }

        // Auto-hide alerts
        setTimeout(function() {
            $('.alert-dismissible').fadeOut('slow');
        }, 5000);

        // AJAX error handling
        $(document).ajaxError(function(event, jqxhr, settings, error) {
            console.error('AJAX Error:', error);
            showNotification('An error occurred. Please try again.', 'danger');
        });

        // Prevent double form submission
        $('form').on('submit', function() {
            if ($(this).data('submitted') === true) {
                return false;
            }
            $(this).data('submitted', true);
            showLoading();
        });

        // Session timeout warning
        let sessionTimer;
        function resetSessionTimer() {
            clearTimeout(sessionTimer);
            sessionTimer = setTimeout(function() {
                showNotification('Your session will expire soon. Please save your work.', 'warning');
            }, <?php echo (defined('SESSION_TIMEOUT') ? (SESSION_TIMEOUT - 60) : 1740) * 1000; ?>);
        }

        $(document).on('mousemove keydown click', resetSessionTimer);
        resetSessionTimer();

        // DataTables initialization
        if ($('#dataTable').length) {
            $('#dataTable').DataTable({
                pageLength: 10,
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                responsive: true
            });
        }

        // CSV Export
        function exportToCSV(data, filename = 'export.csv') {
            let csv = data.map(row => row.join(',')).join('\n');
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.click();
            window.URL.revokeObjectURL(url);
        }

        // Sidebar toggle for mobile
        window.toggleSidebar = function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('mobile-hidden');
        }

        // Handle responsive behavior
        function handleResponsive() {
            const width = window.innerWidth;
            const backToTop = document.getElementById('backToTop');
            
            if (backToTop) {
                if (width <= 576) {
                    backToTop.style.width = '40px';
                    backToTop.style.height = '40px';
                    backToTop.style.bottom = '15px';
                    backToTop.style.right = '15px';
                } else {
                    backToTop.style.width = '50px';
                    backToTop.style.height = '50px';
                    backToTop.style.bottom = '30px';
                    backToTop.style.right = '30px';
                }
            }
        }

        window.addEventListener('resize', handleResponsive);
        handleResponsive();

        $(document).ready(function() {
            console.log('Admin panel loaded successfully');
        });
    </script>

    <!-- Page-specific scripts -->
    <?php if (isset($extra_scripts)): ?>
        <?php echo $extra_scripts; ?>
    <?php endif; ?>

    <style>
        /* ===== PROFESSIONAL CORPORATE FOOTER STYLES ===== */
        :root {
            --primary: #1e3a5f;
            --primary-light: #2b4c7c;
            --secondary: #2c3e50;
            --success: #198754;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #0dcaf0;
            --dark: #212529;
            --dark-gray: #6c757d;
            --light: #f8fafc;
            --light-gray: #e9ecef;
            --border: #dee2e6;
        }

        /* Footer Styles - Now outside admin-wrapper */
        .admin-footer {
            background: linear-gradient(to right, var(--light), white);
            padding: 1rem 0;
            border-top: 2px solid var(--border);
            font-size: 0.9rem;
            color: var(--dark);
            box-shadow: 0 -4px 6px rgba(0,0,0,0.02);
            width: 100%;
            flex-shrink: 0;
            margin-top: auto;
            position: relative;
            z-index: 100;
            clear: both;
        }

        /* Adjust for sidebar on desktop */
        @media (min-width: 769px) {
            .admin-footer {
                margin-left: var(--sidebar-width);
                width: calc(100% - var(--sidebar-width));
            }
        }

        /* Mobile footer */
        @media (max-width: 768px) {
            .admin-footer {
                margin-left: 0;
                width: 100%;
            }
        }

        .footer-copyright {
            color: var(--dark-gray);
            line-height: 1.5;
        }

        .footer-copyright strong {
            color: var(--primary);
            font-weight: 600;
        }

        .footer-links {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 0.5rem 1rem;
        }

        .version-badge {
            background: var(--light);
            border: 1px solid var(--border);
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.8rem;
            color: var(--dark-gray);
        }

        .separator {
            color: var(--border);
            font-weight: 300;
        }

        .footer-link {
            color: var(--primary);
            text-decoration: none;
            transition: color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .footer-link:hover {
            color: var(--primary-light);
            text-decoration: underline;
        }

        .footer-link i {
            font-size: 0.85rem;
        }

        /* Modal Styles */
        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .modal-header {
            background: var(--light);
            border-bottom: 1px solid var(--border);
            padding: 1.25rem 1.5rem;
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            background: var(--light);
            border-top: 1px solid var(--border);
            padding: 1.25rem 1.5rem;
        }

        /* About Modal Specific */
        .about-icon-wrapper {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }

        .about-icon-wrapper i {
            font-size: 2.5rem;
            color: white;
        }

        .about-info-grid {
            background: var(--light);
            border-radius: 8px;
            padding: 1.25rem;
        }

        .info-row {
            display: flex;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px dashed var(--border);
        }

        .info-row:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .info-label {
            width: 120px;
            color: var(--dark-gray);
            font-weight: 500;
        }

        .info-value {
            flex: 1;
            color: var(--dark);
            font-weight: 500;
        }

        .system-info-title {
            color: var(--dark);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .system-info-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .system-info-list li {
            padding: 0.5rem 0;
            color: var(--dark);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
        }

        .system-info-list li i {
            color: var(--primary);
            width: 20px;
        }

        /* Button Styles */
        .btn {
            padding: 0.5rem 1.25rem;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .btn-light {
            background: white;
            border: 1px solid var(--border);
            color: var(--dark);
        }

        .btn-light:hover {
            background: var(--light);
            border-color: var(--dark-gray);
        }

        .btn-warning {
            background: var(--warning);
            border: none;
            color: #000;
        }

        .btn-warning:hover {
            background: #e6b000;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255,193,7,0.3);
        }

        .btn-danger {
            background: var(--danger);
            border: none;
            color: white;
        }

        .btn-danger:hover {
            background: #bb2d3b;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220,53,69,0.3);
        }

        /* Loading Spinner */
        .loading-spinner {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(3px);
        }

        .loading-spinner .spinner-border {
            width: 3rem;
            height: 3rem;
            color: var(--primary);
        }

        /* Back to Top Button */
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: none;
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: var(--primary);
            color: white;
            border: none;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            cursor: pointer;
            transition: all 0.2s;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .back-to-top:hover {
            background: var(--primary-light);
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }

        /* Toast Container */
        #toastContainer {
            z-index: 1100;
        }

        .toast {
            min-width: 350px;
            border-radius: 8px;
            font-size: 0.95rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
            margin-bottom: 0.75rem;
            border: none;
        }

        .toast-body {
            padding: 0.75rem 1rem;
        }

        /* Responsive Footer */
        @media (max-width: 768px) {
            .admin-footer {
                padding: 0.75rem 0;
            }

            .footer-links {
                justify-content: center;
                margin-top: 0.25rem;
            }

            .modal-dialog {
                margin: 1rem;
            }

            .info-row {
                flex-direction: column;
            }

            .info-label {
                width: 100%;
                margin-bottom: 0.25rem;
            }
            
            .toast {
                min-width: 300px;
            }
            
            .back-to-top {
                bottom: 20px;
                right: 20px;
                width: 40px;
                height: 40px;
            }
        }

        @media (max-width: 576px) {
            .footer-links {
                flex-direction: column;
                gap: 0.25rem;
            }

            .separator {
                display: none;
            }

            .modal-body {
                padding: 1.5rem;
            }
            
            .toast {
                min-width: auto;
                width: calc(100% - 2rem);
            }
            
            .modal-footer .btn {
                width: 100%;
            }
        }

        /* Print Styles */
        @media print {
            .admin-footer,
            .back-to-top,
            .modal,
            .toast-container {
                display: none !important;
            }
        }
    </style>
</body>
</html>