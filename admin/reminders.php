<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();

$today = date('Y-m-d');
$in_30_days = date('Y-m-d', strtotime('+30 days'));

// Get insurance expiring reminders
$insurance_stmt = $db->prepare("
    SELECT c.*, 
           DATEDIFF(c.insurance_expiry, ?) as days_remaining
    FROM cars c
    WHERE c.insurance_expiry IS NOT NULL 
    AND c.insurance_expiry BETWEEN ? AND ?
    AND c.status = 'available'
    ORDER BY c.insurance_expiry ASC
");
$insurance_stmt->execute([$today, $today, $in_30_days]);
$insurance_reminders = $insurance_stmt->fetchAll();

// Get license expiring reminders
$license_stmt = $db->prepare("
    SELECT c.*,
           DATEDIFF(c.license_expiry, ?) as days_remaining
    FROM cars c
    WHERE c.license_expiry IS NOT NULL
    AND c.license_expiry BETWEEN ? AND ?
    AND c.status != 'sold'
    ORDER BY c.license_expiry ASC
");
$license_stmt->execute([$today, $today, $in_30_days]);
$license_reminders = $license_stmt->fetchAll();

// Get maintenance due reminders
$maintenance_stmt = $db->prepare("
    SELECT c.*,
           c.last_maintenance_km,
           c.current_km,
           (c.current_km - c.last_maintenance_km) as km_since_maintenance
    FROM cars c
    WHERE c.status != 'sold'
    AND (
        (c.current_km - c.last_maintenance_km) >= 5000
        OR c.last_maintenance_date IS NULL
        OR DATEDIFF(?, c.last_maintenance_date) >= 180
    )
    ORDER BY (c.current_km - c.last_maintenance_km) DESC
");
$maintenance_stmt->execute([$today]);
$maintenance_reminders = $maintenance_stmt->fetchAll();

// Get overdue rentals
$overdue_stmt = $db->prepare("
    SELECT r.*,
           c.full_name as customer_name, c.phone as customer_phone,
           car.brand, car.model, car.plate_number,
           DATEDIFF(?, r.end_date) as days_overdue
    FROM rentals r
    JOIN customers c ON r.customer_id = c.id
    JOIN cars car ON r.car_id = car.id
    WHERE r.status = 'active'
    AND r.end_date < ?
    ORDER BY r.end_date ASC
");
$overdue_stmt->execute([$today, $today]);
$overdue_reminders = $overdue_stmt->fetchAll();

// Get cars returning today
$returning_today_stmt = $db->prepare("
    SELECT r.*,
           c.full_name as customer_name, c.phone as customer_phone,
           car.brand, car.model, car.plate_number
    FROM rentals r
    JOIN customers c ON r.customer_id = c.id
    JOIN cars car ON r.car_id = car.id
    WHERE r.status = 'active'
    AND DATE(r.end_date) = ?
    ORDER BY r.end_date ASC
");
$returning_today_stmt->execute([$today]);
$returning_today = $returning_today_stmt->fetchAll();

// Get upcoming payments (rentals with pending amounts)
$pending_payments_stmt = $db->prepare("
    SELECT r.*,
           c.full_name as customer_name, c.phone as customer_phone,
           car.brand, car.model, car.plate_number,
           (r.total_amount - r.paid_amount) as remaining_amount
    FROM rentals r
    JOIN customers c ON r.customer_id = c.id
    JOIN cars car ON r.car_id = car.id
    WHERE r.status IN ('active', 'completed')
    AND (r.total_amount - r.paid_amount) > 0
    ORDER BY r.end_date ASC
    LIMIT 20
");
$pending_payments_stmt->execute();
$pending_payments = $pending_payments_stmt->fetchAll();

$total_reminders = count($insurance_reminders) + count($license_reminders) + 
                   count($maintenance_reminders) + count($overdue_reminders);

$page_title = 'التذكيرات والإشعارات - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-bell me-2"></i>التذكيرات والإشعارات</h5>
            <p>نظام تذكير ذكي لمتابعة التأمينات والصيانة والعقود</p>
        </div>
        <div class="top-bar-right">
            <span class="badge bg-danger" style="font-size: 1.2rem; padding: 10px 20px;">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $total_reminders; ?> تنبيه
            </span>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card" style="border-right: 4px solid #f44336;">
                <div class="stat-icon" style="background: rgba(244, 67, 54, 0.1); color: #F44336;">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="stat-value"><?php echo count($insurance_reminders); ?></div>
                <div class="stat-label">تأمينات تنتهي قريباً</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-right: 4px solid #ff9800;">
                <div class="stat-icon" style="background: rgba(255, 152, 0, 0.1); color: #FF9800;">
                    <i class="fas fa-id-card"></i>
                </div>
                <div class="stat-value"><?php echo count($license_reminders); ?></div>
                <div class="stat-label">رخص تنتهي قريباً</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-right: 4px solid #9c27b0;">
                <div class="stat-icon" style="background: rgba(156, 39, 176, 0.1); color: #9C27B0;">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-value"><?php echo count($maintenance_reminders); ?></div>
                <div class="stat-label">صيانة مستحقة</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-right: 4px solid #e91e63;">
                <div class="stat-icon" style="background: rgba(233, 30, 99, 0.1); color: #E91E63;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?php echo count($overdue_reminders); ?></div>
                <div class="stat-label">سيارات متأخرة</div>
            </div>
        </div>
    </div>

    <!-- Overdue Rentals (URGENT) -->
    <?php if (count($overdue_reminders) > 0): ?>
    <div class="stat-card mb-4" style="border: 3px solid #f44336; background: #ffebee;">
        <h5 class="mb-3" style="color: #f44336;">
            <i class="fas fa-exclamation-circle me-2"></i>
            🚨 سيارات متأخرة - عاجل! (<?php echo count($overdue_reminders); ?>)
        </h5>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead style="background: #f44336; color: white;">
                    <tr>
                        <th>العقد</th>
                        <th>العميل</th>
                        <th>الهاتف</th>
                        <th>السيارة</th>
                        <th>تاريخ الإرجاع</th>
                        <th>التأخير</th>
                        <th>الإجراء</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($overdue_reminders as $rental): ?>
                    <tr>
                        <td><strong><?php echo $rental['rental_number']; ?></strong></td>
                        <td><?php echo $rental['customer_name']; ?></td>
                        <td>
                            <a href="tel:<?php echo $rental['customer_phone']; ?>" class="btn btn-sm btn-success">
                                <i class="fas fa-phone"></i> <?php echo $rental['customer_phone']; ?>
                            </a>
                        </td>
                        <td><?php echo $rental['brand'] . ' ' . $rental['model']; ?><br>
                            <small class="text-muted"><?php echo $rental['plate_number']; ?></small>
                        </td>
                        <td><?php echo formatDate($rental['end_date']); ?></td>
                        <td>
                            <span class="badge bg-danger" style="font-size: 1rem;">
                                <?php echo $rental['days_overdue']; ?> يوم
                            </span>
                        </td>
                        <td>
                            <a href="rental_view.php?id=<?php echo $rental['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Returning Today -->
    <?php if (count($returning_today) > 0): ?>
    <div class="stat-card mb-4" style="border-right: 4px solid #2196F3;">
        <h5 class="mb-3" style="color: #2196F3;">
            <i class="fas fa-calendar-day me-2"></i>
            📅 سيارات تعود اليوم (<?php echo count($returning_today); ?>)
        </h5>
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>العقد</th>
                        <th>العميل</th>
                        <th>السيارة</th>
                        <th>الوقت</th>
                        <th>إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($returning_today as $rental): ?>
                    <tr>
                        <td><?php echo $rental['rental_number']; ?></td>
                        <td><?php echo $rental['customer_name']; ?></td>
                        <td><?php echo $rental['brand'] . ' ' . $rental['model']; ?></td>
                        <td><?php echo date('h:i A', strtotime($rental['end_date'])); ?></td>
                        <td>
                            <a href="rental_view.php?id=<?php echo $rental['id']; ?>" class="btn btn-sm btn-info">
                                عرض
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Insurance Expiring -->
    <?php if (count($insurance_reminders) > 0): ?>
    <div class="stat-card mb-4" style="border-right: 4px solid #f44336;">
        <h5 class="mb-3" style="color: #f44336;">
            <i class="fas fa-shield-alt me-2"></i>
            🛡️ تأمينات تنتهي خلال 30 يوم (<?php echo count($insurance_reminders); ?>)
        </h5>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>السيارة</th>
                        <th>اللوحة</th>
                        <th>تاريخ انتهاء التأمين</th>
                        <th>المتبقي</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($insurance_reminders as $car): ?>
                    <tr>
                        <td><?php echo $car['brand'] . ' ' . $car['model']; ?></td>
                        <td><strong><?php echo $car['plate_number']; ?></strong></td>
                        <td><?php echo formatDate($car['insurance_expiry']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $car['days_remaining'] <= 7 ? 'danger' : 'warning'; ?>">
                                <?php echo $car['days_remaining']; ?> يوم
                            </span>
                        </td>
                        <td>
                            <?php if ($car['days_remaining'] <= 7): ?>
                                <span class="badge bg-danger">عاجل جداً</span>
                            <?php elseif ($car['days_remaining'] <= 15): ?>
                                <span class="badge bg-warning">عاجل</span>
                            <?php else: ?>
                                <span class="badge bg-info">قريباً</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- License Expiring -->
    <?php if (count($license_reminders) > 0): ?>
    <div class="stat-card mb-4" style="border-right: 4px solid #ff9800;">
        <h5 class="mb-3" style="color: #ff9800;">
            <i class="fas fa-id-card me-2"></i>
            📝 رخص سيارات تنتهي خلال 30 يوم (<?php echo count($license_reminders); ?>)
        </h5>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>السيارة</th>
                        <th>اللوحة</th>
                        <th>تاريخ انتهاء الرخصة</th>
                        <th>المتبقي</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($license_reminders as $car): ?>
                    <tr>
                        <td><?php echo $car['brand'] . ' ' . $car['model']; ?></td>
                        <td><strong><?php echo $car['plate_number']; ?></strong></td>
                        <td><?php echo formatDate($car['license_expiry']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $car['days_remaining'] <= 7 ? 'danger' : 'warning'; ?>">
                                <?php echo $car['days_remaining']; ?> يوم
                            </span>
                        </td>
                        <td>
                            <?php if ($car['days_remaining'] <= 7): ?>
                                <span class="badge bg-danger">عاجل جداً</span>
                            <?php elseif ($car['days_remaining'] <= 15): ?>
                                <span class="badge bg-warning">عاجل</span>
                            <?php else: ?>
                                <span class="badge bg-info">قريباً</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Maintenance Due -->
    <?php if (count($maintenance_reminders) > 0): ?>
    <div class="stat-card mb-4" style="border-right: 4px solid #9c27b0;">
        <h5 class="mb-3" style="color: #9c27b0;">
            <i class="fas fa-tools me-2"></i>
            🔧 سيارات تحتاج صيانة (<?php echo count($maintenance_reminders); ?>)
        </h5>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>السيارة</th>
                        <th>اللوحة</th>
                        <th>الكيلومترات الحالية</th>
                        <th>آخر صيانة</th>
                        <th>الكيلومترات منذ الصيانة</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($maintenance_reminders as $car): ?>
                    <tr>
                        <td><?php echo $car['brand'] . ' ' . $car['model']; ?></td>
                        <td><strong><?php echo $car['plate_number']; ?></strong></td>
                        <td><?php echo number_format($car['current_km']); ?> كم</td>
                        <td>
                            <?php if ($car['last_maintenance_date']): ?>
                                <?php echo formatDate($car['last_maintenance_date']); ?>
                            <?php else: ?>
                                <span class="text-danger">لا توجد</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $car['km_since_maintenance'] >= 10000 ? 'danger' : 'warning'; ?>">
                                <?php echo number_format($car['km_since_maintenance']); ?> كم
                            </span>
                        </td>
                        <td>
                            <?php if ($car['km_since_maintenance'] >= 10000 || !$car['last_maintenance_date']): ?>
                                <span class="badge bg-danger">عاجل جداً</span>
                            <?php else: ?>
                                <span class="badge bg-warning">مستحقة</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Pending Payments -->
    <?php if (count($pending_payments) > 0): ?>
    <div class="stat-card" style="border-right: 4px solid #607D8B;">
        <h5 class="mb-3" style="color: #607D8B;">
            <i class="fas fa-money-bill-wave me-2"></i>
            💰 مدفوعات معلقة (<?php echo count($pending_payments); ?>)
        </h5>
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>العقد</th>
                        <th>العميل</th>
                        <th>الهاتف</th>
                        <th>الإجمالي</th>
                        <th>المدفوع</th>
                        <th>المتبقي</th>
                        <th>إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_payments as $rental): ?>
                    <tr>
                        <td><?php echo $rental['rental_number']; ?></td>
                        <td><?php echo $rental['customer_name']; ?></td>
                        <td><?php echo $rental['customer_phone']; ?></td>
                        <td><?php echo formatCurrency($rental['total_amount']); ?></td>
                        <td><?php echo formatCurrency($rental['paid_amount']); ?></td>
                        <td>
                            <strong style="color: #f44336;">
                                <?php echo formatCurrency($rental['remaining_amount']); ?>
                            </strong>
                        </td>
                        <td>
                            <a href="rental_view.php?id=<?php echo $rental['id']; ?>" class="btn btn-sm btn-primary">
                                دفع
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($total_reminders == 0): ?>
    <div class="stat-card text-center" style="padding: 60px;">
        <div style="font-size: 5rem; color: #4CAF50; margin-bottom: 20px;">✅</div>
        <h4 style="color: #4CAF50;">رائع! لا توجد تنبيهات</h4>
        <p style="color: #666; margin-top: 10px;">جميع الأمور تحت السيطرة</p>
    </div>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>