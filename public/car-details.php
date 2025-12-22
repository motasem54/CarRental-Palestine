<?php
require_once '../config/settings.php';
$db = Database::getInstance()->getConnection();

if (!isset($_GET['id'])) {
    header('Location: cars.php');
    exit;
}

$car_id = (int)$_GET['id'];

try {
    $stmt = $db->prepare("SELECT * FROM cars WHERE id = ?");
    $stmt->execute([$car_id]);
    $car = $stmt->fetch();
    
    if (!$car) {
        header('Location: cars.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: cars.php');
    exit;
}

$page_title = $car['brand'] . ' ' . $car['model'] . ' ' . $car['year'] . ' - ' . SITE_NAME;
include 'includes/header.php';
?>

<style>
.car-details-section {
    padding: 50px 0;
}

.car-main-image {
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    height: 500px;
}

.car-main-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.car-details-box {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.price-box {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    padding: 30px;
    border-radius: 15px;
    text-align: center;
    margin-bottom: 30px;
}

.price-box h2 {
    font-size: 3rem;
    font-weight: 900;
    margin: 0;
}

.specs-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin: 30px 0;
}

.spec-item {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.spec-item i {
    font-size: 2rem;
    color: var(--primary);
}

.spec-item .spec-content h6 {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.spec-item .spec-content p {
    margin: 5px 0 0 0;
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--dark);
}

.features-list {
    list-style: none;
    padding: 0;
}

.features-list li {
    padding: 12px 0;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
    gap: 10px;
}

.features-list li i {
    color: var(--primary);
}

.status-badge {
    display: inline-block;
    padding: 10px 25px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 1.1rem;
}

.status-available {
    background: #4CAF50;
    color: white;
}

.status-rented {
    background: #FFC107;
    color: white;
}

.status-maintenance {
    background: #F44336;
    color: white;
}
</style>

<!-- Page Header -->
<div class="page-header">
    <div class="container text-center">
        <h1><?php echo $car['brand'] . ' ' . $car['model'] . ' ' . $car['year']; ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center">
                <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                <li class="breadcrumb-item"><a href="cars.php">السيارات</a></li>
                <li class="breadcrumb-item active"><?php echo $car['brand'] . ' ' . $car['model']; ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="container car-details-section">
    <div class="row g-4">
        <!-- Car Image -->
        <div class="col-lg-7">
            <div class="car-main-image">
                <img src="<?php echo UPLOADS_URL . '/cars/' . $car['image']; ?>" 
                     alt="<?php echo $car['brand'] . ' ' . $car['model']; ?>"
                     onerror="this.src='<?php echo UPLOADS_URL; ?>/cars/no_image.jpg'">
            </div>
        </div>
        
        <!-- Car Details -->
        <div class="col-lg-5">
            <div class="car-details-box">
                <!-- Price -->
                <div class="price-box">
                    <h2><?php echo formatCurrency($car['daily_rate']); ?></h2>
                    <p class="mb-0">لليوم الواحد</p>
                </div>
                
                <!-- Status -->
                <div class="text-center mb-4">
                    <?php
                    $status_class = '';
                    $status_text = '';
                    switch($car['status']) {
                        case 'available':
                            $status_class = 'status-available';
                            $status_text = 'متاحة للحجز';
                            break;
                        case 'rented':
                            $status_class = 'status-rented';
                            $status_text = 'مؤجّرة حالياً';
                            break;
                        case 'maintenance':
                            $status_class = 'status-maintenance';
                            $status_text = 'قيد الصيانة';
                            break;
                    }
                    ?>
                    <span class="status-badge <?php echo $status_class; ?>">
                        <i class="fas fa-circle me-2"></i><?php echo $status_text; ?>
                    </span>
                </div>
                
                <!-- Specs Grid -->
                <div class="specs-grid">
                    <div class="spec-item">
                        <i class="fas fa-car"></i>
                        <div class="spec-content">
                            <h6>نوع السيارة</h6>
                            <p><?php echo CAR_TYPES[$car['type']]; ?></p>
                        </div>
                    </div>
                    
                    <div class="spec-item">
                        <i class="fas fa-users"></i>
                        <div class="spec-content">
                            <h6>عدد المقاعد</h6>
                            <p><?php echo $car['seats']; ?> مقاعد</p>
                        </div>
                    </div>
                    
                    <div class="spec-item">
                        <i class="fas fa-cog"></i>
                        <div class="spec-content">
                            <h6>ناقل الحركة</h6>
                            <p><?php echo TRANSMISSION_TYPES[$car['transmission']]; ?></p>
                        </div>
                    </div>
                    
                    <div class="spec-item">
                        <i class="fas fa-gas-pump"></i>
                        <div class="spec-content">
                            <h6>نوع الوقود</h6>
                            <p><?php echo FUEL_TYPES[$car['fuel_type']]; ?></p>
                        </div>
                    </div>
                    
                    <div class="spec-item">
                        <i class="fas fa-palette"></i>
                        <div class="spec-content">
                            <h6>اللون</h6>
                            <p><?php echo $car['color']; ?></p>
                        </div>
                    </div>
                    
                    <div class="spec-item">
                        <i class="fas fa-id-card"></i>
                        <div class="spec-content">
                            <h6>رقم اللوحة</h6>
                            <p><?php echo $car['plate_number']; ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Buttons -->
                <div class="d-grid gap-2">
                    <?php if ($car['status'] === 'available'): ?>
                    <a href="<?php echo ADMIN_URL; ?>/login.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-calendar-check me-2"></i>احجز الآن
                    </a>
                    <?php endif; ?>
                    <a href="contact.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-phone me-2"></i>اتصل بنا
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Additional Info -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="car-details-box">
                <h3 class="mb-4">معلومات إضافية</h3>
                
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-3">المواصفات</h5>
                        <ul class="features-list">
                            <li><i class="fas fa-check-circle"></i> تكييف هواء قوي</li>
                            <li><i class="fas fa-check-circle"></i> نظام صوتي متطور</li>
                            <li><i class="fas fa-check-circle"></i> نظام سلامة متقدم</li>
                            <li><i class="fas fa-check-circle"></i> فتحة سقف</li>
                            <li><i class="fas fa-check-circle"></i> Bluetooth & USB</li>
                        </ul>
                    </div>
                    
                    <div class="col-md-6">
                        <h5 class="mb-3">شروط التأجير</h5>
                        <ul class="features-list">
                            <li><i class="fas fa-info-circle"></i> رخصة قيادة سارية</li>
                            <li><i class="fas fa-info-circle"></i> بطاقة هوية أو جواز سفر</li>
                            <li><i class="fas fa-info-circle"></i> تأمين شامل</li>
                            <li><i class="fas fa-info-circle"></i> صيانة مجانية</li>
                            <li><i class="fas fa-info-circle"></i> دعم 24/7</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>