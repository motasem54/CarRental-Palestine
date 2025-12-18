<?php
$page_title = 'لوحة التحكم';
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
checkAuth();

$db = Database::getInstance()->getConnection();

// Get statistics
$stats = [];

// Total cars
$stmt = $db->query("SELECT COUNT(*) as total, status FROM cars GROUP BY status");
$carStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$stats['total_cars'] = array_sum($carStats);
$stats['available_cars'] = $carStats['available'] ?? 0;
$stats['rented_cars'] = $carStats['rented'] ?? 0;

// Total customers
$stmt = $db->query("SELECT COUNT(*) as total FROM customers WHERE status = 'active'");
$stats['total_customers'] = $stmt->fetch()['total'];

// Active rentals
$stmt = $db->query("SELECT COUNT(*) as total FROM rentals WHERE status IN ('confirmed', 'active')");
$stats['active_rentals'] = $stmt->fetch()['total'];

// Pending bookings
$stmt = $db->query("SELECT COUNT(*) as total FROM online_bookings WHERE status = 'pending'");
$stats['pending_bookings'] = $stmt->fetch()['total'];

// Today's revenue
$stmt = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE DATE(payment_date) = CURDATE()");
$stats['today_revenue'] = $stmt->fetch()['total'];

// Month revenue
$stmt = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())");
$stats['month_revenue'] = $stmt->fetch()['total'];

// Recent rentals
$stmt = $db->prepare("
    SELECT r.*, c.plate_number, c.brand, c.model, cu.full_name as customer_name
    FROM rentals r
    JOIN cars c ON r.car_id = c.id
    JOIN customers cu ON r.customer_id = cu.id
    ORDER BY r.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recent_rentals = $stmt->fetchAll();

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="main-content">
    <!-- Top Bar -->
    <div class="top-bar">
        <h1 class="page-title">🏠 لوحة التحكم الرئيسية</h1>
        <div class="user-info">
            <div class="user-avatar"><?php echo substr($user['full_name'], 0, 2); ?></div>
            <div class="user-details">
                <h6><?php echo $user['full_name']; ?></h6>
                <small><?php echo USER_ROLES[$user['role']]; ?></small>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #4CAF50, #45a049);">
                    <i class="fas fa-car"></i>
                </div>
                <div class="stat-value"><?php echo $stats['available_cars']; ?></div>
                <div class="stat-label">سيارات متاحة</div>
                <small class="text-muted">من أصل <?php echo $stats['total_cars']; ?> سيارة</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #FF5722, #E64A19);">
                    <i class="fas fa-car-side"></i>
                </div>
                <div class="stat-value"><?php echo $stats['rented_cars']; ?></div>
                <div class="stat-label">سيارات مؤجرة</div>
                <small class="text-muted">حالياً</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #2196F3, #1976D2);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_customers']; ?></div>
                <div class="stat-label">عملاء نشطين</div>
                <small class="text-muted">مسجلين</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #9C27B0, #7B1FA2);">
                    <i class="fas fa-file-contract"></i>
                </div>
                <div class="stat-value"><?php echo $stats['active_rentals']; ?></div>
                <div class="stat-label">حجوزات نشطة</div>
                <small class="text-muted">قيد التنفيذ</small>
            </div>
        </div>
    </div>

    <!-- Revenue Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 style="color: #4CAF50;">💰 إيرادات اليوم</h5>
                        <h2 class="stat-value" style="color: #4CAF50;"><?php echo formatCurrency($stats['today_revenue']); ?></h2>
                    </div>
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4CAF50, #45a049);">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 style="color: #FF9800;">📊 إيرادات الشهر</h5>
                        <h2 class="stat-value" style="color: #FF9800;"><?php echo formatCurrency($stats['month_revenue']); ?></h2>
                    </div>
                    <div class="stat-icon" style="background: linear-gradient(135deg, #FF9800, #F57C00);">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($stats['pending_bookings'] > 0): ?>
    <div class="alert alert-warning" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        لديك <strong><?php echo $stats['pending_bookings']; ?></strong> حجز جديد بانتظار المراجعة!
        <a href="bookings.php" class="alert-link">اذهب للحجوزات</a>
    </div>
    <?php endif; ?>

    <!-- Recent Rentals -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-clock me-2"></i> آخر الحجوزات</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>رقم الحجز</th>
                            <th>العميل</th>
                            <th>السيارة</th>
                            <th>من - إلى</th>
                            <th>المبلغ</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_rentals as $rental): ?>
                        <tr>
                            <td><strong><?php echo $rental['rental_number']; ?></strong></td>
                            <td><?php echo $rental['customer_name']; ?></td>
                            <td><?php echo $rental['brand'] . ' ' . $rental['model']; ?></td>
                            <td>
                                <small><?php echo formatDate($rental['start_date']); ?></small><br>
                                <small><?php echo formatDate($rental['end_date']); ?></small>
                            </td>
                            <td><?php echo formatCurrency($rental['total_amount']); ?></td>
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
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>