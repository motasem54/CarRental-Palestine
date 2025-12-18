<?php
require_once '../config/settings.php';

$db = Database::getInstance()->getConnection();

// Get available cars
$stmt = $db->query("
    SELECT * FROM cars 
    WHERE status = 'available'
    ORDER BY created_at DESC
    LIMIT 12
");
$cars = $stmt->fetchAll();

// Get testimonials
$stmt = $db->query("
    SELECT * FROM testimonials 
    WHERE is_approved = 1
    ORDER BY is_featured DESC, created_at DESC
    LIMIT 6
");
$testimonials = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - أفضل خدمات تأجير السيارات في فلسطين</title>
    
    <!-- Bootstrap 5 RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts - Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #FF5722;
            --primary-dark: #E64A19;
            --dark: #121212;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            overflow-x: hidden;
        }

        /* Navbar */
        .navbar {
            background: rgba(18, 18, 18, 0.95) !important;
            backdrop-filter: blur(10px);
            padding: 15px 0;
            box-shadow: 0 2px 20px rgba(0,0,0,0.3);
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: white !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar-brand i {
            font-size: 2rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-link {
            color: #ccc !important;
            font-weight: 600;
            margin: 0 10px;
            transition: all 0.3s;
        }

        .nav-link:hover {
            color: var(--primary) !important;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--dark) 0%, #1a1a1a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600"><path fill="%23FF5722" fill-opacity="0.05" d="M0,300 Q300,100 600,300 T1200,300 L1200,600 L0,600 Z"/></svg>');
            background-size: cover;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: bold;
            color: white;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .hero h1 .highlight {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: 1.3rem;
            color: #ccc;
            margin-bottom: 30px;
        }

        .btn-hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 15px 40px;
            border-radius: 50px;
            font-weight: bold;
            border: none;
            font-size: 1.1rem;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(255, 87, 34, 0.3);
        }

        .btn-hero:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 87, 34, 0.5);
            color: white;
        }

        /* Features */
        .features {
            padding: 80px 0;
            background: white;
        }

        .feature-card {
            text-align: center;
            padding: 40px 30px;
            border-radius: 20px;
            background: white;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.3s;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 40px rgba(255, 87, 34, 0.2);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }

        /* Cars Grid */
        .cars-section {
            padding: 80px 0;
            background: #f8f9fa;
        }

        .section-title {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-title h2 {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--dark);
        }

        .section-title .highlight {
            color: var(--primary);
        }

        .car-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s;
            height: 100%;
        }

        .car-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(255, 87, 34, 0.2);
        }

        .car-image {
            height: 200px;
            background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: #ccc;
        }

        .car-body {
            padding: 25px;
        }

        .car-title {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: var(--dark);
        }

        .car-specs {
            display: flex;
            gap: 15px;
            margin: 15px 0;
            flex-wrap: wrap;
        }

        .car-spec {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            color: #666;
        }

        .car-spec i {
            color: var(--primary);
        }

        .car-price {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary);
            margin: 15px 0;
        }

        .car-price span {
            font-size: 0.9rem;
            color: #666;
            font-weight: normal;
        }

        /* Footer */
        .footer {
            background: var(--dark);
            color: white;
            padding: 50px 0 20px;
        }

        .footer h5 {
            color: var(--primary);
            margin-bottom: 20px;
            font-weight: bold;
        }

        .footer a {
            color: #ccc;
            text-decoration: none;
            transition: all 0.3s;
        }

        .footer a:hover {
            color: var(--primary);
        }

        .social-links a {
            display: inline-block;
            width: 40px;
            height: 40px;
            line-height: 40px;
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            margin: 0 5px;
            transition: all 0.3s;
        }

        .social-links a:hover {
            background: var(--primary);
            transform: translateY(-3px);
        }

        @media (max-width: 768px) {
            .hero h1 { font-size: 2rem; }
            .hero p { font-size: 1rem; }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-car"></i>
                <?php echo SITE_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#home">الرئيسية</a></li>
                    <li class="nav-item"><a class="nav-link" href="#cars">السيارات</a></li>
                    <li class="nav-item"><a class="nav-link" href="#features">المميزات</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">اتصل بنا</a></li>
                    <li class="nav-item">
                        <a class="btn btn-hero btn-sm ms-3" href="<?php echo ADMIN_URL; ?>/login.php">
                            <i class="fas fa-sign-in-alt me-2"></i>تسجيل الدخول
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content" data-aos="fade-right">
                    <h1>
                        أفضل خدمة<br>
                        <span class="highlight">تاجير سيارات</span><br>
                        في فلسطين 🇵🇸
                    </h1>
                    <p>
                        نوفر لك أفضل السيارات بأسعار تنافسية<br>
                        وخدمة عملاء مميزة على مدار الساعة
                    </p>
                    <a href="#cars" class="btn btn-hero">
                        <i class="fas fa-car me-2"></i>استعرض السيارات
                    </a>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <i class="fas fa-car" style="font-size: 20rem; color: rgba(255,87,34,0.1);"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>لماذا <span class="highlight">تختارنا؟</span></h2>
            </div>
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4>تأمين شامل</h4>
                        <p>جميع سياراتنا مؤمنة بالكامل لراحة بالك</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-tag"></i>
                        </div>
                        <h4>أسعار تنافسية</h4>
                        <p>أفضل العروض والخصومات في فلسطين</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h4>خدمة 24/7</h4>
                        <p>دعم فني على مدار الساعة لخدمتك</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Cars Section -->
    <section class="cars-section" id="cars">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>استعرض <span class="highlight">سياراتنا</span></h2>
                <p>اختر السيارة المناسبة لك</p>
            </div>
            <div class="row g-4">
                <?php foreach ($cars as $car): ?>
                <div class="col-lg-4 col-md-6" data-aos="fade-up">
                    <div class="car-card">
                        <div class="car-image">
                            <i class="fas fa-car"></i>
                        </div>
                        <div class="car-body">
                            <div class="car-title"><?php echo $car['brand'] . ' ' . $car['model']; ?></div>
                            <div class="car-specs">
                                <div class="car-spec">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo $car['year']; ?>
                                </div>
                                <div class="car-spec">
                                    <i class="fas fa-cog"></i>
                                    <?php echo TRANSMISSION_TYPES[$car['transmission']]; ?>
                                </div>
                                <div class="car-spec">
                                    <i class="fas fa-users"></i>
                                    <?php echo $car['seats']; ?> راكب
                                </div>
                            </div>
                            <div class="car-price">
                                <?php echo formatCurrency($car['daily_rate']); ?>
                                <span>/ يوم</span>
                            </div>
                            <a href="#" class="btn btn-hero w-100">
                                <i class="fas fa-calendar-check me-2"></i>احجز الآن
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5><i class="fas fa-car me-2"></i><?php echo SITE_NAME; ?></h5>
                    <p>نظام تأجير سيارات فلسطيني متكامل يوفر أفضل الخدمات وأحدث السيارات.</p>
                    <div class="social-links mt-3">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-whatsapp"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <h5>روابط سريعة</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#home"><i class="fas fa-chevron-left me-2"></i>الرئيسية</a></li>
                        <li class="mb-2"><a href="#cars"><i class="fas fa-chevron-left me-2"></i>السيارات</a></li>
                        <li class="mb-2"><a href="#features"><i class="fas fa-chevron-left me-2"></i>المميزات</a></li>
                        <li class="mb-2"><a href="#contact"><i class="fas fa-chevron-left me-2"></i>اتصل بنا</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 mb-4">
                    <h5>تواصل معنا</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2 text-primary"></i><?php echo COMPANY_ADDRESS; ?></li>
                        <li class="mb-2"><i class="fas fa-phone me-2 text-primary"></i><?php echo COMPANY_PHONE; ?></li>
                        <li class="mb-2"><i class="fas fa-envelope me-2 text-primary"></i><?php echo COMPANY_EMAIL; ?></li>
                    </ul>
                </div>
            </div>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <div class="text-center">
                <p class="mb-0">&copy; 2024 <?php echo SITE_NAME; ?>. جميع الحقوق محفوظة | 🇵🇸 صُنع بكل حب في فلسطين</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>
</html>