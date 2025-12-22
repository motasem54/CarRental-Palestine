    <!-- Footer -->
    <footer class="footer" style="background: var(--darker); color: white; padding: 60px 0 20px 0; margin-top: 80px;">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <h5 class="fw-bold mb-4" style="color: var(--primary);">
                        <i class="fas fa-car me-2"></i><?php echo SITE_NAME; ?>
                    </h5>
                    <p style="color: rgba(255,255,255,0.8); line-height: 1.8;">
                        أفضل خدمة تأجير سيارات في فلسطين. نوفر لكم أحدث السيارات بأفضل الأسعار وخدمة متميزة على مدار الساعة.
                    </p>
                    <div class="social-links mt-3">
                        <a href="#" style="color: white; font-size: 1.5rem; margin-left: 15px; transition: 0.3s;">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="#" style="color: white; font-size: 1.5rem; margin-left: 15px; transition: 0.3s;">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" style="color: white; font-size: 1.5rem; margin-left: 15px; transition: 0.3s;">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <h5 class="fw-bold mb-4" style="color: var(--primary);">روابط سريعة</h5>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 10px;">
                            <a href="index.php" style="color: rgba(255,255,255,0.8); text-decoration: none; transition: 0.3s;">
                                <i class="fas fa-chevron-left me-2"></i>الرئيسية
                            </a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <a href="cars.php" style="color: rgba(255,255,255,0.8); text-decoration: none; transition: 0.3s;">
                                <i class="fas fa-chevron-left me-2"></i>السيارات
                            </a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <a href="services.php" style="color: rgba(255,255,255,0.8); text-decoration: none; transition: 0.3s;">
                                <i class="fas fa-chevron-left me-2"></i>الخدمات
                            </a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <a href="about.php" style="color: rgba(255,255,255,0.8); text-decoration: none; transition: 0.3s;">
                                <i class="fas fa-chevron-left me-2"></i>من نحن
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <h5 class="fw-bold mb-4" style="color: var(--primary);">اتصل بنا</h5>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 15px; color: rgba(255,255,255,0.8);">
                            <i class="fas fa-phone me-2" style="color: var(--primary);"></i>
                            <?php echo COMPANY_PHONE; ?>
                        </li>
                        <li style="margin-bottom: 15px; color: rgba(255,255,255,0.8);">
                            <i class="fas fa-envelope me-2" style="color: var(--primary);"></i>
                            <?php echo COMPANY_EMAIL; ?>
                        </li>
                        <li style="margin-bottom: 15px; color: rgba(255,255,255,0.8);">
                            <i class="fas fa-map-marker-alt me-2" style="color: var(--primary);"></i>
                            فلسطين 🇵🇸
                        </li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <h5 class="fw-bold mb-4" style="color: var(--primary);">ساعات العمل</h5>
                    <ul style="list-style: none; padding: 0; color: rgba(255,255,255,0.8);">
                        <li style="margin-bottom: 10px;">
                            <i class="fas fa-clock me-2" style="color: var(--primary);"></i>
                            السبت - الخميس: 8 صباحاً - 10 مساءً
                        </li>
                        <li style="margin-bottom: 10px;">
                            <i class="fas fa-clock me-2" style="color: var(--primary);"></i>
                            الجمعة: 2 ظهراً - 10 مساءً
                        </li>
                        <li style="margin-bottom: 10px;">
                            <i class="fas fa-headset me-2" style="color: var(--primary);"></i>
                            دعم فني 24/7
                        </li>
                    </ul>
                </div>
            </div>
            
            <hr style="border-color: rgba(255,255,255,0.1); margin: 40px 0 20px 0;">
            
            <div class="text-center" style="color: rgba(255,255,255,0.7);">
                <p class="mb-2">&copy; 2024 <?php echo SITE_NAME; ?> - جميع الحقوق محفوظة</p>
                <p class="mb-0">🇵🇸 صُنع بكل حب في فلسطين</p>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>