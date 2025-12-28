    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <h5><i class="fas fa-car me-2"></i><?php echo SITE_NAME; ?></h5>
                    <p style="color: rgba(255,255,255,0.7); line-height: 1.8;">
                        نظام تأجير سيارات احترافي في فلسطين. نقدم أفضل الأسعار وأحدث السيارات مع خدمة مميزة على مدار الساعة.
                    </p>
                    <div class="social-links mt-3">
                        <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="https://wa.me/<?php echo str_replace(['+', ' '], '', WHATSAPP_NUMBER); ?>" target="_blank" title="WhatsApp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <h5>روابط سريعة</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php"><i class="fas fa-chevron-left me-2"></i>الرئيسية</a></li>
                        <li class="mb-2"><a href="cars.php"><i class="fas fa-chevron-left me-2"></i>السيارات</a></li>
                        <li class="mb-2"><a href="about.php"><i class="fas fa-chevron-left me-2"></i>من نحن</a></li>
                        <li class="mb-2"><a href="services.php"><i class="fas fa-chevron-left me-2"></i>الخدمات</a></li>
                        <li class="mb-2"><a href="contact.php"><i class="fas fa-chevron-left me-2"></i>اتصل بنا</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <h5>خدماتنا</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#"><i class="fas fa-chevron-left me-2"></i>تأجير يومي</a></li>
                        <li class="mb-2"><a href="#"><i class="fas fa-chevron-left me-2"></i>تأجير شهري</a></li>
                        <li class="mb-2"><a href="#"><i class="fas fa-chevron-left me-2"></i>تأجير مع سائق</a></li>
                        <li class="mb-2"><a href="#"><i class="fas fa-chevron-left me-2"></i>توصيل للمطار</a></li>
                        <li class="mb-2"><a href="#"><i class="fas fa-chevron-left me-2"></i>عروض خاصة</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <h5>تواصل معنا</h5>
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <i class="fas fa-map-marker-alt me-2" style="color: var(--primary);"></i>
                            <span style="color: rgba(255,255,255,0.8);"><?php echo COMPANY_ADDRESS; ?></span>
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-phone me-2" style="color: var(--primary);"></i>
                            <a href="tel:<?php echo COMPANY_PHONE; ?>" style="color: rgba(255,255,255,0.8);">
                                <?php echo COMPANY_PHONE; ?>
                            </a>
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-envelope me-2" style="color: var(--primary);"></i>
                            <a href="mailto:<?php echo COMPANY_EMAIL; ?>" style="color: rgba(255,255,255,0.8);">
                                <?php echo COMPANY_EMAIL; ?>
                            </a>
                        </li>
                        <li class="mb-3">
                            <i class="fab fa-whatsapp me-2" style="color: var(--primary);"></i>
                            <a href="https://wa.me/<?php echo str_replace(['+', ' '], '', WHATSAPP_NUMBER); ?>" target="_blank" style="color: rgba(255,255,255,0.8);">
                                <?php echo WHATSAPP_NUMBER; ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <hr style="border-color: rgba(255,255,255,0.1); margin: 40px 0 20px 0;">
            
            <div class="text-center" style="color: rgba(255,255,255,0.6);">
                <p class="mb-2">
                    &copy; <?php echo date('Y'); ?> <?php echo COMPANY_NAME; ?>. جميع الحقوق محفوظة.
                </p>
                <p class="mb-0">
                    🇵🇸 Made with <span style="color: var(--primary);">❤️</span> in Palestine
                </p>
            </div>
        </div>
    </footer>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap 5 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Scroll to Top Button
        window.onscroll = function() {
            const scrollBtn = document.getElementById('scrollTop');
            if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
                scrollBtn.classList.add('show');
            } else {
                scrollBtn.classList.remove('show');
            }
        };
        
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
        
        // Close mobile menu on link click
        document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992) {
                    const navbarCollapse = document.querySelector('.navbar-collapse');
                    const bsCollapse = new bootstrap.Collapse(navbarCollapse, {
                        toggle: true
                    });
                }
            });
        });
    </script>
    
    <?php if (isset($extra_js)) echo $extra_js; ?>
</body>
</html>