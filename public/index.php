<?php
require_once '../config/settings.php';
$db = Database::getInstance()->getConnection();

// Get available cars
try {
    $cars_stmt = $db->query("
        SELECT * FROM cars 
        WHERE status = 'available' 
        ORDER BY created_at DESC 
        LIMIT 9
    ");
    $featured_cars = $cars_stmt->fetchAll();
} catch (Exception $e) {
    $featured_cars = [];
}

// Get total stats
try {
    $stats = $db->query("
        SELECT 
            (SELECT COUNT(*) FROM cars WHERE status = 'available') as available_cars,
            (SELECT COUNT(*) FROM customers) as total_customers,
            (SELECT COUNT(*) FROM rentals WHERE status IN ('active', 'completed')) as total_rentals
    ")->fetch();
} catch (Exception $e) {
    $stats = ['available_cars' => 0, 'total_customers' => 0, 'total_rentals' => 0];
}

$page_title = SITE_NAME . ' - أفضل خدمة تأجير سيارات في فلسطين';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="أفضل خدمة تأجير سيارات في فلسطين - أسعار منافسة وسيارات حديثة">
    <title><?php echo $page_title; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #FF5722;
            --primary-dark: #E64A19;
            --primary-light: #FF7043;
            --dark: #1a1a2e;
            --darker: #16213e;
            --accent: #0f3460;
        }
        
        * { 
            font-family: 'Cairo', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html { scroll-behavior: smooth; }
        
        body { 
            background: #f8f9fa;
            overflow-x: hidden;
        }
        
        /* Navbar */
        .navbar {
            background: rgba(26, 26, 46, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .navbar.scrolled {
            background: var(--dark) !important;
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 900;
            color: white !important;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 600;
            transition: 0.3s;
            padding: 8px 15px !important;
        }
        
        .nav-link:hover {
            color: var(--primary) !important;
            transform: translateY(-2px);
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--darker) 0%, var(--dark) 50%, var(--accent) 100%);
            position: relative;
            overflow: hidden;
            padding: 120px 0;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(255, 87, 34, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 80% 50%, rgba(255, 87, 34, 0.15) 0%, transparent 50%);
            animation: pulse 8s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
            color: white;
        }
        
        .hero h1 {
            font-size: 3.5rem;
            font-weight: 900;
            margin-bottom: 20px;
            text-shadow: 2px 2px 10px rgba(0,0,0,0.3);
            animation: fadeInUp 0.8s ease-out;
        }
        
        .hero p {
            font-size: 1.4rem;
            margin-bottom: 40px;
            opacity: 0.95;
            animation: fadeInUp 1s ease-out;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .hero .btn-primary {
            animation: fadeInUp 1.2s ease-out;
        }
        
        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            padding: 14px 35px;
            font-weight: 700;
            font-size: 1.1rem;
            border-radius: 50px;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(255, 87, 34, 0.3);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(255, 87, 34, 0.5);
        }
        
        /* Stats Section */
        .stats-section {
            background: white;
            padding: 60px 0;
            box-shadow: 0 -5px 20px rgba(0,0,0,0.05);
        }
        
        .stat-box {
            text-align: center;
            padding: 30px;
        }
        
        .stat-box i {
            font-size: 3.5rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
        }
        
        .stat-box h3 {
            font-size: 3rem;
            font-weight: 900;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .stat-box p {
            color: #666;
            font-size: 1.1rem;
        }
        
        /* Features */
        .features {
            background: linear-gradient(to bottom, #f8f9fa 0%, white 100%);
            padding: 100px 0;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-title h2 {
            font-size: 3rem;
            font-weight: 900;
            color: var(--dark);
            margin-bottom: 15px;
        }
        
        .section-title p {
            font-size: 1.2rem;
            color: #666;
        }
        
        .feature-box {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.4s;
            height: 100%;
        }
        
        .feature-box:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .feature-box i {
            font-size: 4rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 25px;
        }
        
        .feature-box h5 {
            font-weight: 700;
            font-size: 1.4rem;
            color: var(--dark);
            margin-bottom: 15px;
        }
        
        .feature-box p {
            color: #666;
            font-size: 1rem;
        }
        
        /* Cars Section */
        .cars-section {
            background: white;
            padding: 100px 0;
        }
        
        .car-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.4s;
            height: 100%;
            background: white;
        }
        
        .car-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
        }
        
        .car-card .car-image {
            position: relative;
            overflow: hidden;
            height: 280px;
            background: #f8f9fa;
        }
        
        .car-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: 0.4s;
        }
        
        .car-card:hover img {
            transform: scale(1.1);
        }
        
        .car-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: 0 4px 15px rgba(255, 87, 34, 0.4);
        }
        
        .car-card .card-body {
            padding: 25px;
        }
        
        .car-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 15px;
        }
        
        .car-specs {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .car-spec {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            font-size: 0.95rem;
        }
        
        .car-spec i {
            color: var(--primary);
        }
        
        .car-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 2px solid #f8f9fa;
        }
        
        .car-price {
            font-size: 2rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .car-price small {
            font-size: 1rem;
            color: #666;
        }
        
        /* Contact */
        .contact-section {
            background: linear-gradient(135deg, var(--darker), var(--dark));
            color: white;
            padding: 80px 0;
        }
        
        .contact-box {
            text-align: center;
            padding: 30px;
        }
        
        .contact-box i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 20px;
        }
        
        .contact-box h5 {
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        /* Footer */
        .footer {
            background: var(--darker);
            color: white;
            padding: 40px 0;
            text-align: center;
        }
        
        .footer p {
            margin: 5px 0;
            opacity: 0.9;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 { font-size: 2rem; }
            .hero p { font-size: 1.1rem; }
            .section-title h2 { font-size: 2rem; }
            .stat-box h3 { font-size: 2rem; }
            .car-price { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-car me-2"></i>
                <?php echo SITE_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="#home">الرئيسية</a></li>
                    <li class="nav-item"><a class="nav-link" href="#cars">السيارات</a></li>
                    <li class="nav-item"><a class="nav-link" href="#features">المزايا</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">اتصل بنا</a></li>
                </ul>
                <a href="<?php echo ADMIN_URL; ?>/login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt me-2"></i>تسجيل الدخول
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <div class="hero" id="home">
        <div class="container">
            <div class="hero-content">
                <h1>🇵🇸 أهلاً بكم في نظام تأجير السيارات</h1>
                <p>أفضل أسعار تأجير السيارات في فلسطين - سيارات حديثة وخدمة مميزة</p>
                <a href="#cars" class="btn btn-primary btn-lg">
                    <i class="fas fa-search me-2"></i>ابحث عن سيارتك الآن
                </a>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <div class="stat-box">
                        <i class="fas fa-car"></i>
                        <h3><?php echo $stats['available_cars']; ?>+</h3>
                        <p>سيارة متاحة</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box">
                        <i class="fas fa-users"></i>
                        <h3><?php echo $stats['total_customers']; ?>+</h3>
                        <p>عميل سعيد</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box">
                        <i class="fas fa-calendar-check"></i>
                        <h3><?php echo $stats['total_rentals']; ?>+</h3>
                        <p>حجز ناجح</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features -->
    <div class="features" id="features">
        <div class="container">
            <div class="section-title">
                <h2>لماذا تختارنا؟</h2>
                <p>نقدم أفضل خدمات تأجير السيارات في فلسطين</p>
            </div>
            <div class="row g-4">
                <div class="col-md-3 col-sm-6">
                    <div class="feature-box">
                        <i class="fas fa-dollar-sign"></i>
                        <h5>أسعار تنافسية</h5>
                        <p>أفضل الأسعار في السوق الفلسطيني</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="feature-box">
                        <i class="fas fa-car"></i>
                        <h5>سيارات حديثة</h5>
                        <p>أحدث موديلات 2021-2023</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="feature-box">
                        <i class="fas fa-clock"></i>
                        <h5>خدمة 24/7</h5>
                        <p>دعم على مدار الساعة</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="feature-box">
                        <i class="fas fa-shield-alt"></i>
                        <h5>تأمين شامل</h5>
                        <p>جميع سياراتنا مؤمّنة</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cars -->
    <div class="cars-section" id="cars">
        <div class="container">
            <div class="section-title">
                <h2>السيارات المتاحة</h2>
                <p>اختر سيارتك المفضلة من أسطولنا</p>
            </div>
            <div class="row g-4">
                <?php if (count($featured_cars) > 0): ?>
                    <?php foreach ($featured_cars as $car): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="card car-card">
                            <div class="car-image">
                                <img src="<?php echo UPLOADS_URL . '/cars/' . $car['image']; ?>" 
                                     alt="<?php echo $car['brand'] . ' ' . $car['model']; ?>"
                                     onerror="this.src='<?php echo UPLOADS_URL; ?>/cars/no_image.jpg'">
                                <div class="car-badge"><?php echo CAR_TYPES[$car['type']]; ?></div>
                            </div>
                            <div class="card-body">
                                <h5 class="car-title"><?php echo $car['brand'] . ' ' . $car['model'] . ' ' . $car['year']; ?></h5>
                                <div class="car-specs">
                                    <div class="car-spec">
                                        <i class="fas fa-users"></i>
                                        <span><?php echo $car['seats']; ?> مقاعد</span>
                                    </div>
                                    <div class="car-spec">
                                        <i class="fas fa-cog"></i>
                                        <span><?php echo TRANSMISSION_TYPES[$car['transmission']]; ?></span>
                                    </div>
                                    <div class="car-spec">
                                        <i class="fas fa-gas-pump"></i>
                                        <span><?php echo FUEL_TYPES[$car['fuel_type']]; ?></span>
                                    </div>
                                </div>
                                <div class="car-footer">
                                    <div class="car-price">
                                        <?php echo formatCurrency($car['daily_rate']); ?>
                                        <small>/يوم</small>
                                    </div>
                                    <a href="<?php echo ADMIN_URL; ?>/login.php" class="btn btn-primary btn-sm">
                                        احجز الآن
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <p class="text-muted fs-5">لا توجد سيارات متاحة حالياً</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Contact -->
    <div class="contact-section" id="contact">
        <div class="container">
            <div class="section-title">
                <h2 style="color: white;">اتصل بنا</h2>
                <p style="color: rgba(255,255,255,0.8);">للحجز أو الاستفسار، لا تتردد بالتواصل معنا</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <div class="contact-box">
                        <i class="fas fa-phone"></i>
                        <h5><?php echo COMPANY_PHONE; ?></h5>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="contact-box">
                        <i class="fas fa-envelope"></i>
                        <h5><?php echo COMPANY_EMAIL; ?></h5>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="contact-box">
                        <i class="fas fa-map-marker-alt"></i>
                        <h5>فلسطين 🇵🇸</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="container">
            <p class="mb-2">&copy; 2024 <?php echo SITE_NAME; ?> - جميع الحقوق محفوظة</p>
            <p class="mb-0">🇵🇸 صُنع بكل حب في فلسطين</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>