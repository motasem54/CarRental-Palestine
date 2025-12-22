<?php
require_once '../config/settings.php';
$db = Database::getInstance()->getConnection();

// Get available cars
try {
    $cars_stmt = $db->query("
        SELECT * FROM cars 
        WHERE status = 'available' 
        ORDER BY created_at DESC 
        LIMIT 6
    ");
    $featured_cars = $cars_stmt->fetchAll();
} catch (Exception $e) {
    $featured_cars = [];
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
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #FF5722;
            --primary-dark: #E64A19;
            --dark: #1a1a2e;
        }
        
        * { font-family: 'Cairo', sans-serif; }
        
        body { background: #f8f9fa; }
        
        .hero {
            background: linear-gradient(135deg, var(--dark) 0%, #16213e 100%);
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        
        .hero h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 20px;
        }
        
        .hero p { font-size: 1.2rem; margin-bottom: 30px; }
        
        .btn-primary {
            background: var(--primary);
            border: none;
            padding: 12px 30px;
            font-weight: 700;
            border-radius: 50px;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .car-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: 0.3s;
            height: 100%;
        }
        
        .car-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .car-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }
        
        .car-card .card-body {
            padding: 20px;
        }
        
        .car-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .car-price {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
        }
        
        .features {
            background: white;
            padding: 80px 0;
        }
        
        .feature-box {
            text-align: center;
            padding: 30px;
        }
        
        .feature-box i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 20px;
        }
        
        .footer {
            background: var(--dark);
            color: white;
            padding: 40px 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: var(--dark);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-car me-2"></i>
                <?php echo SITE_NAME_EN; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
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
    <div class="hero">
        <div class="container">
            <h1>🇵🇸 أهلاً بكم في نظام تأجير السيارات</h1>
            <p>أفضل أسعار تأجير السيارات في فلسطين - سيارات حديثة وخدمة مميزة</p>
            <a href="#cars" class="btn btn-primary btn-lg">
                <i class="fas fa-search me-2"></i>ابحث عن سيارتك الآن
            </a>
        </div>
    </div>

    <!-- Features -->
    <div class="features" id="features">
        <div class="container">
            <h2 class="text-center fw-bold mb-5">لماذا تختارنا؟</h2>
            <div class="row">
                <div class="col-md-3">
                    <div class="feature-box">
                        <i class="fas fa-dollar-sign"></i>
                        <h5 class="fw-bold">أسعار تنافسية</h5>
                        <p>أفضل الأسعار في السوق</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-box">
                        <i class="fas fa-car"></i>
                        <h5 class="fw-bold">سيارات حديثة</h5>
                        <p>أحدث موديلات 2021-2023</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-box">
                        <i class="fas fa-clock"></i>
                        <h5 class="fw-bold">خدمة 24/7</h5>
                        <p>دعم على مدار الساعة</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-box">
                        <i class="fas fa-shield-alt"></i>
                        <h5 class="fw-bold">تأمين شامل</h5>
                        <p>جميع سياراتنا مؤمّنة</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cars -->
    <div class="py-5" id="cars">
        <div class="container">
            <h2 class="text-center fw-bold mb-5">السيارات المتاحة</h2>
            <div class="row g-4">
                <?php if (count($featured_cars) > 0): ?>
                    <?php foreach ($featured_cars as $car): ?>
                    <div class="col-md-4">
                        <div class="card car-card">
                            <img src="<?php echo UPLOADS_URL . '/cars/' . $car['image']; ?>" 
                                 alt="<?php echo $car['brand'] . ' ' . $car['model']; ?>"
                                 onerror="this.src='https://via.placeholder.com/800x600/FF5722/FFFFFF?text=لا+توجد+صورة'">
                            <div class="card-body">
                                <h5 class="car-title"><?php echo $car['brand'] . ' ' . $car['model'] . ' ' . $car['year']; ?></h5>
                                <p class="mb-2">
                                    <span class="badge bg-secondary"><?php echo CAR_TYPES[$car['type']]; ?></span>
                                    <span class="badge bg-info"><?php echo TRANSMISSION_TYPES[$car['transmission']]; ?></span>
                                </p>
                                <p class="text-muted"><i class="fas fa-users me-1"></i><?php echo $car['seats']; ?> مقاعد</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="car-price"><?php echo formatCurrency($car['daily_rate']); ?>/يوم</div>
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
                        <p class="text-muted">لا توجد سيارات متاحة حالياً</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Contact -->
    <div class="py-5 bg-light" id="contact">
        <div class="container text-center">
            <h2 class="fw-bold mb-4">اتصل بنا</h2>
            <p class="mb-4">للحجز أو الاستفسار، لا تتردد بالتواصل معنا</p>
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <i class="fas fa-phone fa-2x text-primary mb-3"></i>
                    <h5><?php echo COMPANY_PHONE; ?></h5>
                </div>
                <div class="col-md-4">
                    <i class="fas fa-envelope fa-2x text-primary mb-3"></i>
                    <h5><?php echo COMPANY_EMAIL; ?></h5>
                </div>
                <div class="col-md-4">
                    <i class="fas fa-map-marker-alt fa-2x text-primary mb-3"></i>
                    <h5><?php echo COMPANY_ADDRESS; ?></h5>
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
</body>
</html>