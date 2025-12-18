<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();

// Get statistics
$stats = [];

// Total cars
$stmt = $db->query("SELECT COUNT(*) as total FROM cars");
$stats['total_cars'] = $stmt->fetch()['total'];

// Available cars
$stmt = $db->query("SELECT COUNT(*) as total FROM cars WHERE status = 'available'");
$stats['available_cars'] = $stmt->fetch()['total'];

// Active rentals
$stmt = $db->query("SELECT COUNT(*) as total FROM rentals WHERE status = 'active'");
$stats['active_rentals'] = $stmt->fetch()['total'];

// Total customers
$stmt = $db->query("SELECT COUNT(*) as total FROM customers WHERE status = 'active'");
$stats['total_customers'] = $stmt->fetch()['total'];

// Today's revenue
$stmt = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE DATE(payment_date) = CURDATE()");
$stats['today_revenue'] = $stmt->fetch()['total'];

// This month revenue
$stmt = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())");
$stats['month_revenue'] = $stmt->fetch()['total'];

// Pending payments
$stmt = $db->query("SELECT COALESCE(SUM(remaining_amount), 0) as total FROM rentals WHERE payment_status != 'paid'");
$stats['pending_payments'] = $stmt->fetch()['total'];

// Recent rentals
$stmt = $db->query("
    SELECT r.*, c.full_name as customer_name, ca.plate_number, ca.brand, ca.model
    FROM rentals r
    JOIN customers c ON r.customer_id = c.id
    JOIN cars ca ON r.car_id = ca.id
    ORDER BY r.created_at DESC
    LIMIT 5
");
$recent_rentals = $stmt->fetchAll();

// Cars needing maintenance
$stmt = $db->query("
    SELECT * FROM cars 
    WHERE status = 'maintenance' 
    OR next_maintenance <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    LIMIT 5
");
$maintenance_cars = $stmt->fetchAll();

$page_title = 'لوحة التحكم - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="welcome-text">
            <h5>مرحباً، <?php echo $_SESSION['full_name']; ?> 👋</h5>
            <p>اليوم <?php echo date('l, d F Y'); ?></p>
        </div>
        <div class="top-bar-right">
            <div class="notification-icon">
                <i class="fas fa-bell"></i>
                <span class="notification-badge">3</span>
            </div>
            <div class="user-profile">
                <div class="user-avatar"><?php echo mb_substr($_SESSION['full_name'], 0, 1); ?></div>
                <span class="user-name"><?php echo $_SESSION['full_name']; ?></span>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(33, 150, 243, 0.1); color: #2196F3;">
                    <i class="fas fa-car"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_cars']; ?></div>
                <div class="stat-label">إجمالي السيارات</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(76, 175, 80, 0.1); color: #4CAF50;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo $stats['available_cars']; ?></div>
                <div class="stat-label">سيارات متاحة</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(255, 152, 0, 0.1); color: #FF9800;">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-value"><?php echo $stats['active_rentals']; ?></div>
                <div class="stat-label">حجوزات نشطة</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(156, 39, 176, 0.1); color: #9C27B0;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_customers']; ?></div>
                <div class="stat-label">عملاء نشطين</div>
            </div>
        </div>
    </div>

    <!-- Revenue Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(255, 87, 34, 0.1); color: var(--primary);">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="stat-value"><?php echo formatCurrency($stats['today_revenue']); ?></div>
                <div class="stat-label">إيرادات اليوم</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(76, 175, 80, 0.1); color: #4CAF50;">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-value"><?php echo formatCurrency($stats['month_revenue']); ?></div>
                <div class="stat-label">إيرادات الشهر</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(244, 67, 54, 0.1); color: #F44336;">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="stat-value"><?php echo formatCurrency($stats['pending_payments']); ?></div>
                <div class="stat-label">مدفوعات معلقة</div>
            </div>
        </div>
    </div>

    <!-- Recent Rentals & Maintenance -->
    <div class="row g-3">
        <div class="col-md-8">
            <div class="table-container">
                <h5 class="mb-3">
                    <i class="fas fa-calendar-alt text-primary"></i>
                    أحدث الحجوزات
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>رقم الحجز</th>
                                <th>العميل</th>
                                <th>السيارة</th>
                                <th>الحالة</th>
                                <th>المبلغ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_rentals as $rental): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $rental['rental_number']; ?></strong><br>
                                    <small class="text-muted"><?php echo formatDate($rental['start_date'], 'd/m/Y'); ?></small>
                                </td>
                                <td><?php echo $rental['customer_name']; ?></td>
                                <td><?php echo $rental['brand'] . ' ' . $rental['model']; ?></td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'confirmed' => 'info',
                                        'active' => 'success',
                                        'completed' => 'secondary',
                                        'cancelled' => 'danger'
                                    ];
                                    $color = $statusColors[$rental['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $color; ?>">
                                        <?php echo RENTAL_STATUS[$rental['status']]; ?>
                                    </span>
                                </td>
                                <td><strong><?php echo formatCurrency($rental['total_amount']); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <a href="rentals.php" class="btn btn-primary">
                        <i class="fas fa-eye me-2"></i>عرض جميع الحجوزات
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="table-container">
                <h5 class="mb-3">
                    <i class="fas fa-tools text-danger"></i>
                    صيانة مطلوبة
                </h5>
                <?php if (count($maintenance_cars) > 0): ?>
                    <div class="list-group">
                        <?php foreach ($maintenance_cars as $car): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo $car['brand'] . ' ' . $car['model']; ?></strong><br>
                                    <small class="text-muted"><?php echo $car['plate_number']; ?></small>
                                </div>
                                <span class="badge bg-danger">
                                    <i class="fas fa-wrench"></i>
                                </span>
                            </div>
                            <?php if ($car['next_maintenance']): ?>
                            <small class="text-muted d-block mt-1">
                                الصيانة: <?php echo formatDate($car['next_maintenance'], 'd/m/Y'); ?>
                            </small>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i><br>
                        لا توجد سيارات تحتاج صيانة
                    </div>
                <?php endif; ?>
                <div class="text-center mt-3">
                    <a href="maintenance.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-tools me-2"></i>إدارة الصيانة
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>