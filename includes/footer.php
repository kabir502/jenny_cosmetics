<?php
// includes/footer.php
?>
        </main> <!-- Close main container from header -->
        
        <!-- Footer -->
        <footer class="footer">
            <div class="footer-main">
                <div class="container">
                    <div class="row g-4">
                        <div class="col-lg-4" data-aos="fade-up">
                            <div class="footer-about">
                                <div class="footer-logo">
                                    <i class="fas fa-gem"></i>
                                    <span><?php echo SITE_NAME; ?></span>
                                </div>
                                <p class="footer-description">
                                    Discover the epitome of elegance with our curated collection of fine cosmetics and imitation jewelry. Each piece is crafted to make you feel extraordinary.
                                </p>
                                <div class="footer-social">
                                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                                    <a href="#" class="social-link"><i class="fab fa-pinterest-p"></i></a>
                                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-2 col-md-4" data-aos="fade-up" data-aos-delay="100">
                            <div class="footer-links">
                                <h5>Quick Links</h5>
                                <ul class="list-unstyled">
                                    <li><a href="index.php"><i class="fas fa-chevron-right"></i>Home</a></li>
                                    <li><a href="products.php"><i class="fas fa-chevron-right"></i>Products</a></li>
                                    <li><a href="about.php"><i class="fas fa-chevron-right"></i>About Us</a></li>
                                    <li><a href="contact.php"><i class="fas fa-chevron-right"></i>Contact</a></li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-4" data-aos="fade-up" data-aos-delay="200">
                            <div class="footer-links">
                                <h5>Customer Service</h5>
                                <ul class="list-unstyled">
                                    <li><a href="#"><i class="fas fa-chevron-right"></i>Shipping Policy</a></li>
                                    <li><a href="#"><i class="fas fa-chevron-right"></i>Returns & Exchanges</a></li>
                                    <li><a href="#"><i class="fas fa-chevron-right"></i>FAQ</a></li>
                                    <li><a href="#"><i class="fas fa-chevron-right"></i>Size Guide</a></li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-4" data-aos="fade-up" data-aos-delay="300">
                            <div class="footer-contact">
                                <h5>Contact Info</h5>
                                <p class="contact-item">
                                    <i class="fas fa-envelope"></i>
                                    <span><?php echo ADMIN_EMAIL; ?></span>
                                </p>
                                <p class="contact-item">
                                    <i class="fas fa-phone-alt"></i>
                                    <span>+1 (234) 567-8900</span>
                                </p>
                                <p class="contact-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>123 Beauty Street, Beverly Hills, CA 90210</span>
                                </p>
                                <div class="payment-methods">
                                    <i class="fab fa-cc-visa"></i>
                                    <i class="fab fa-cc-mastercard"></i>
                                    <i class="fab fa-cc-amex"></i>
                                    <i class="fab fa-cc-paypal"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <p class="copyright">
                                &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. 
                                <span class="d-block d-sm-inline">All rights reserved.</span>
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p class="crafted-by">
                                <i class="fas fa-gem"></i> 
                                Crafted with elegance
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
        
        <!-- Back to Top Button -->
        <button id="backToTop" class="back-to-top" title="Back to Top">
            <i class="fas fa-arrow-up"></i>
        </button>
        
        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        
        <!-- AOS Animation Library -->
        <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
        
        <script>
            // Initialize AOS
            AOS.init({
                duration: 800,
                once: true,
                offset: 100
            });
            
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
            
            // Back to Top Button
            var backToTop = document.getElementById('backToTop');
            
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    backToTop.classList.add('show');
                } else {
                    backToTop.classList.remove('show');
                }
            });
            
            backToTop.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        </script>
        
        <style>
            /* ===== FOOTER STYLES ===== */
            .footer {
                background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
                color: var(--pearl);
                margin-top: 5rem;
                position: relative;
                overflow: hidden;
            }
            
            .footer::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 3px;
                background: linear-gradient(90deg, 
                    transparent 0%, 
                    var(--gold) 20%, 
                    var(--gold) 80%, 
                    transparent 100%
                );
            }
            
            .footer-main {
                padding: 4rem 0;
                position: relative;
                z-index: 2;
            }
            
            .footer::after {
                content: '';
                position: absolute;
                top: -50%;
                right: -50%;
                width: 200%;
                height: 200%;
                background: radial-gradient(circle, rgba(212,175,55,0.1) 0%, transparent 70%);
                animation: rotate 30s linear infinite;
                pointer-events: none;
            }
            
            @keyframes rotate {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            
            .footer-logo {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                margin-bottom: 1.5rem;
            }
            
            .footer-logo i {
                font-size: 2.5rem;
                background: linear-gradient(135deg, var(--gold-light) 0%, var(--gold) 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                animation: sparkle 2s infinite;
            }
            
            .footer-logo span {
                font-family: 'Playfair Display', serif;
                font-size: 1.8rem;
                font-weight: 700;
                background: linear-gradient(135deg, var(--gold-light) 0%, var(--gold) 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            
            .footer-description {
                color: rgba(255,255,255,0.8);
                line-height: 1.8;
                margin-bottom: 1.5rem;
                font-family: 'Cormorant Garamond', serif;
                font-size: 1.1rem;
            }
            
            .footer-social {
                display: flex;
                gap: 1rem;
            }
            
            .social-link {
                width: 40px;
                height: 40px;
                background: rgba(212, 175, 55, 0.1);
                border: 1px solid rgba(212, 175, 55, 0.3);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--gold);
                text-decoration: none;
                transition: var(--transition);
            }
            
            .social-link:hover {
                background: var(--gold);
                color: var(--navy);
                transform: translateY(-5px);
                border-color: var(--gold);
            }
            
            .footer-links h5,
            .footer-contact h5 {
                font-family: 'Playfair Display', serif;
                color: var(--gold);
                font-size: 1.2rem;
                font-weight: 700;
                margin-bottom: 1.5rem;
                position: relative;
                padding-bottom: 0.5rem;
            }
            
            .footer-links h5::after,
            .footer-contact h5::after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 0;
                width: 50px;
                height: 2px;
                background: linear-gradient(90deg, var(--gold) 0%, transparent 100%);
            }
            
            .footer-links ul li {
                margin-bottom: 0.8rem;
            }
            
            .footer-links ul li a {
                color: rgba(255,255,255,0.8);
                text-decoration: none;
                transition: var(--transition);
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
            }
            
            .footer-links ul li a i {
                color: var(--gold);
                font-size: 0.8rem;
                transition: var(--transition);
            }
            
            .footer-links ul li a:hover {
                color: var(--gold);
                transform: translateX(5px);
            }
            
            .footer-links ul li a:hover i {
                transform: translateX(3px);
            }
            
            .contact-item {
                display: flex;
                align-items: flex-start;
                gap: 1rem;
                margin-bottom: 1rem;
                color: rgba(255,255,255,0.8);
            }
            
            .contact-item i {
                color: var(--gold);
                font-size: 1.2rem;
                margin-top: 0.2rem;
            }
            
            .contact-item span {
                flex: 1;
                line-height: 1.6;
            }
            
            .payment-methods {
                display: flex;
                gap: 0.8rem;
                margin-top: 1.5rem;
            }
            
            .payment-methods i {
                font-size: 2rem;
                color: rgba(255,255,255,0.6);
                transition: var(--transition);
            }
            
            .payment-methods i:hover {
                color: var(--gold);
                transform: translateY(-3px);
            }
            
            .footer-bottom {
                background: rgba(0, 0, 0, 0.2);
                padding: 1.5rem 0;
                position: relative;
                z-index: 2;
            }
            
            .copyright {
                color: rgba(255,255,255,0.7);
                font-size: 0.9rem;
                margin: 0;
            }
            
            .crafted-by {
                color: rgba(255,255,255,0.7);
                font-size: 0.9rem;
                margin: 0;
            }
            
            .crafted-by i {
                color: var(--gold);
                animation: sparkle 2s infinite;
            }
            
            /* ===== BACK TO TOP ===== */
            .back-to-top {
                position: fixed;
                bottom: 30px;
                right: 30px;
                width: 50px;
                height: 50px;
                background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
                border: none;
                border-radius: 50%;
                color: var(--navy);
                font-size: 1.2rem;
                cursor: pointer;
                opacity: 0;
                visibility: hidden;
                transition: var(--transition);
                z-index: 1000;
                box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            }
            
            .back-to-top.show {
                opacity: 1;
                visibility: visible;
            }
            
            .back-to-top:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 25px var(--gold);
            }
            
            /* ===== RESPONSIVE ===== */
            @media (max-width: 768px) {
                .footer-main {
                    padding: 3rem 0;
                }
                
                .footer-logo span {
                    font-size: 1.5rem;
                }
                
                .payment-methods {
                    flex-wrap: wrap;
                }
                
                .back-to-top {
                    bottom: 20px;
                    right: 20px;
                    width: 40px;
                    height: 40px;
                    font-size: 1rem;
                }
            }
            
            @media (max-width: 576px) {
                .footer-links,
                .footer-contact {
                    text-align: center;
                }
                
                .footer-links h5::after,
                .footer-contact h5::after {
                    left: 50%;
                    transform: translateX(-50%);
                }
                
                .footer-links ul li a {
                    justify-content: center;
                }
                
                .contact-item {
                    justify-content: center;
                }
                
                .payment-methods {
                    justify-content: center;
                }
                
                .copyright,
                .crafted-by {
                    text-align: center;
                }
                
                .crafted-by {
                    margin-top: 1rem;
                }
            }
        </style>
    </body>
</html>