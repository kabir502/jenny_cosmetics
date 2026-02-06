<?php
// includes/footer.php
?>
        </main> <!-- Close main container from header -->
        
        <footer class="bg-dark text-white mt-5 py-4">
            <div class="container">
                <div class="row">
                    <div class="col-md-4">
                        <h5><?php echo SITE_NAME; ?></h5>
                        <p class="text-white-50">Your trusted source for quality cosmetics and jewelry.</p>
                    </div>
                    <div class="col-md-4">
                        <h5>Quick Links</h5>
                        <ul class="list-unstyled">
                            <li><a href="index.php" class="text-white-50 text-decoration-none">Home</a></li>
                            <li><a href="products.php" class="text-white-50 text-decoration-none">Products</a></li>
                            <li><a href="about.php" class="text-white-50 text-decoration-none">About Us</a></li>
                            <li><a href="contact.php" class="text-white-50 text-decoration-none">Contact</a></li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h5>Contact Info</h5>
                        <p class="mb-1"><i class="fas fa-envelope me-2"></i><?php echo ADMIN_EMAIL; ?></p>
                        <p class="mb-1"><i class="fas fa-phone me-2"></i>+1 (234) 567-8900</p>
                        <p class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>123 Beauty Street</p>
                    </div>
                </div>
                <hr class="bg-white my-4">
                <div class="text-center">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
                </div>
            </div>
        </footer>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Auto-dismiss alerts after 5 seconds
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    var alerts = document.querySelectorAll('.alert');
                    alerts.forEach(function(alert) {
                        var bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    });
                }, 5000);
            });
        </script>
    </body>
</html>