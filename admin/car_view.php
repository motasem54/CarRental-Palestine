<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'معرف السيارة غير موجود';
    redirect('cars.php');
}

$car_id = (int)$_GET['id'];

// Fetch car details
try {
    $stmt = $db->prepare("SELECT * FROM cars WHERE id = ?");
    $stmt->execute([$car_id]);
    $car = $stmt->fetch();
    
    if (!$car) {
        $_SESSION['error'] = 'السيارة غير موجودة';
        redirect('cars.php');
    }
    
    // Get rental history
    $rentals_stmt = $db->prepare("
        SELECT r.*, c.full_name as customer_name 
        FROM rentals r
        JOIN customers c ON r.customer_id = c.id
        WHERE r.car_id = ?
        ORDER BY r.start_date DESC
        LIMIT 10
    ");
    $rentals_stmt->execute([$car_id]);
    $rentals = $rentals_stmt->fetchAll();
    
    // Get maintenance history
    $maintenance_stmt = $db->prepare("
        SELECT * FROM maintenance 
        WHERE car_id = ?
        ORDER BY maintenance_date DESC
        LIMIT 10
    ");
    $maintenance_stmt->execute([$car_id]);
    $maintenance = $maintenance_stmt->fetchAll();
    
} catch (Exception $e) {
    $_SESSION['error'] = 'خطأ في جلب بيانات السيارة';
    redirect('cars.php');
}

$page_title = 'تفاصيل السيارة - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-car me-2"></i><?php echo $car['brand'] . ' ' . $car['model']; ?></h5>
        </div>
        <div class="top-bar-right">
            <a href="car_edit.php?id=<?php echo $car['id']; ?>" class="btn btn-primary">
                <i class="fas fa-edit me-2"></i>تعديل
            </a>
            <a href="cars.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right me-2"></i>عودة
            </a>
        </div>
    </div>

    <!-- Car Details Card -->
    <div class="table-container">
        <div class="row">
            <div class="col-md-4">
                <?php if ($car['image']): ?>
                    <img src="<?php echo UPLOADS_URL . '/cars/' . $car['image']; ?>" 
                         class="img-fluid rounded mb-3" alt="<?php echo $car['brand']; ?>">
                <?php else: ?>
                    <div class="text-center p-5 bg-light rounded mb-3">
                        <i class="fas fa-car fa-5x text-muted"></i>
                    </div>
                <?php endif; ?>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">معلومات أساسية</h5>
                        <table class="table table-sm">
                            <tr>
                                <th>رقم اللوحة:</th>
                                <td><strong><?php echo $car['plate_number']; ?></strong></td>
                            </tr>
                            <tr>
                                <th>الحالة:</th>
                                <td><?php echo getCarStatusBadge($car['status']); ?></td>
                            </tr>
                            <tr>
                                <th>السنة:</th>
                                <td><?php echo $car['year']; ?></td>
                            </tr>
                            <tr>
                                <th>اللون:</th>
                                <td><?php echo $car['color']; ?></td>
                            </tr>
                            <tr>
                                <th>عدد المقاعد:</th>
                                <td><?php echo $car['seats']; ?></td>
                            </tr>
                            <tr>
                                <th>قراءة العداد:</th>
                                <td><?php echo number_format($car['mileage']); ?> كم</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">المواصفات التقنية</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <th>النوع:</th>
                                        <td><?php echo CAR_TYPES[$car['type']]; ?></td>
                                    </tr>
                                    <tr>
                                        <th>ناقل الحركة:</th>
                                        <td><?php echo TRANSMISSION_TYPES[$car['transmission']]; ?></td>
                                    </tr>
                                    <tr>
                                        <th>نوع الوقود:</th>
                                        <td><?php echo FUEL_TYPES[$car['fuel_type']]; ?></td>
                                    </tr>
                                    <tr>
                                        <th>حالة السيارة:</th>
                                        <td><?php echo $car['condition']; ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <th>الأجرة اليومية:</th>
                                        <td><strong><?php echo formatCurrency($car['daily_rate']); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <th>الأجرة الأسبوعية:</th>
                                        <td><?php echo $car['weekly_rate'] ? formatCurrency($car['weekly_rate']) : '-'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>الأجرة الشهرية:</th>
                                        <td><?php echo $car['monthly_rate'] ? formatCurrency($car['monthly_rate']) : '-'; ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($car['features']): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">المميزات</h5>
                        <p><?php echo nl2br(htmlspecialchars($car['features'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($car['notes']): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">ملاحظات</h5>
                        <p><?php echo nl2br(htmlspecialchars($car['notes'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Rental History -->
    <?php if (count($rentals) > 0): ?>
    <div class="table-container mt-4">
        <h5 class="mb-3"><i class="fas fa-history me-2"></i>سجل الحجوزات</h5>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>رقم الحجز</th>
                    <th>العميل</th>
                    <th>تاريخ البدء</th>
                    <th>تاريخ الانتهاء</th>
                    <th>الحالة</th>
                    <th>المبلغ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rentals as $rental): ?>
                <tr>
                    <td><?php echo $rental['rental_number']; ?></td>
                    <td><?php echo $rental['customer_name']; ?></td>
                    <td><?php echo formatDate($rental['start_date']); ?></td>
                    <td><?php echo formatDate($rental['end_date']); ?></td>
                    <td><?php echo getRentalStatusBadge($rental['status']); ?></td>
                    <td><?php echo formatCurrency($rental['total_amount']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Maintenance History -->
    <?php if (count($maintenance) > 0): ?>
    <div class="table-container mt-4">
        <h5 class="mb-3"><i class="fas fa-wrench me-2"></i>سجل الصيانة</h5>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>النوع</th>
                    <th>الوصف</th>
                    <th>التكلفة</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($maintenance as $m): ?>
                <tr>
                    <td><?php echo formatDate($m['maintenance_date']); ?></td>
                    <td><?php echo MAINTENANCE_TYPES[$m['maintenance_type']]; ?></td>
                    <td><?php echo htmlspecialchars($m['description']); ?></td>
                    <td><?php echo formatCurrency($m['cost']); ?></td>
                    <td>
                        <span class="badge bg-<?php echo $m['status'] == 'completed' ? 'success' : 'warning'; ?>">
                            <?php echo $m['status']; ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>