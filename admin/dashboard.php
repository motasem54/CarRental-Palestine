<?php
/**
 * Admin Dashboard
 * 📊 لوحة التحكم
 */

require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
checkAuth();

$db = Database::getInstance()->getConnection();

// Get statistics
try {
    // Total cars
    $stmt = $db->query("SELECT COUNT(*) as total FROM cars");
    $totalCars = $stmt->fetch()['total'];

    // Available cars
    $stmt = $db->query("SELECT COUNT(*) as total FROM cars WHERE status = 'available'");
    $availableCars = $stmt->fetch()['total'];

    // Rented cars
    $stmt = $db->query("SELECT COUNT(*) as total FROM cars WHERE status = 'rented'");
    $rentedCars = $stmt->fetch()['total'];

    // Total customers
    $stmt = $db->query("SELECT COUNT(*) as total FROM customers WHERE status = 'active'");
    $totalCustomers = $stmt->fetch()['total'];

    // Active rentals
    $stmt = $db->query("SELECT COUNT(*) as total FROM rentals WHERE status IN ('active', 'confirmed')");
    $activeRentals = $stmt->fetch()['total'];

    // Today's revenue
    $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE DATE(payment_date) = CURDATE()");
    $todayRevenue = $stmt->fetch()['total'];

    // This month revenue
    $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())");
    $monthRevenue = $stmt->fetch()['total'];

    // Pending payments
    $stmt = $db->query("SELECT COALESCE(SUM(remaining_amount), 0) as total FROM rentals WHERE payment_status IN ('pending', 'partial')");
    $pendingPayments = $stmt->fetch()['total'];

    // Recent rentals
    $stmt = $db->query("
        SELECT r.*, c.brand, c.model, c.plate_number, cu.full_name as customer_name
        FROM rentals r
        JOIN cars c ON r.car_id = c.id
        JOIN customers cu ON r.customer_id = cu.id
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $recentRentals = $stmt->fetchAll();

    // Cars needing maintenance
    $stmt = $db->query("
        SELECT c.*, DATEDIFF(next_maintenance, CURDATE()) as days_until
        FROM cars c
        WHERE next_maintenance IS NOT NULL 
        AND next_maintenance <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ORDER BY next_maintenance ASC
        LIMIT 5
    ");
    $maintenanceDue = $stmt->fetchAll();

} catch (Exception $e) {
    error_log('Dashboard stats error: ' . $e->getMessage());
}

include 'includes/header.php';
?>

<!-- Dashboard Content -->
<div class="container-fluid py-4">
    
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="glass-card p-4">
                <h2 class="mb-1">مرحباً 👋 <?php echo $_SESSION['full_name']; ?></h2>
                <p class="text-muted mb-0">هذا ملخص سريع لنظام التأجير لهذا اليوم</p>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <!-- Total Cars -->
        <div class="col-xl-3 col-md-6">
            <div class="stat-card stat-primary">
                <div class="stat-icon">
                    <i class="fas fa-car"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $totalCars; ?></h3>
                    <p>إجمالي السيارات</p>
                    <small class="text-success">
                        <i class="fas fa-check-circle"></i> <?php echo $availableCars; ?> متاحة
                    </small>
                </div>
            </div>
        </div>

        <!-- Active Rentals -->
        <div class="col-xl-3 col-md-6">
            <div class="stat-card stat-warning">
                <div class="stat-icon">
                    <i class="fas fa-key"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $activeRentals; ?></h3>
                    <p>حجوزات نشطة</p>
                    <small class="text-warning">
                        <i class="fas fa-car-side"></i> <?php echo $rentedCars; ?> سيارة مؤجرة
                    </small>
                </div>
            </div>
        </div>

        <!-- Total Customers -->
        <div class="col-xl-3 col-md-6">
            <div class="stat-card stat-info">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $totalCustomers; ?></h3>
                    <p>إجمالي العملاء</p>
                    <small class="text-info">
                        <i class="fas fa-user-check"></i> عملاء نشطين
                    </small>
                </div>
            </div>
        </div>

        <!-- Month Revenue -->
        <div class="col-xl-3 col-md-6">
            <div class="stat-card stat-success">
                <div class="stat-icon">
                    <i class="fas fa-shekel-sign"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($monthRevenue, 0); ?>₪</h3>
                    <p>إيرادات الشهر</p>
                    <small class="text-success">
                        <i class="fas fa-arrow-up"></i> اليوم: <?php echo number_format($todayRevenue, 0); ?>₪
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-3 mb-4">
        <!-- Revenue Chart -->
        <div class="col-lg-8">
            <div class="glass-card p-4">
                <h5 class="mb-3">
                    <i class="fas fa-chart-line text-primary me-2"></i>
                    إيرادات آخر 7 أيام
                </h5>
                <canvas id="revenueChart" height="80"></canvas>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="col-lg-4">
            <div class="glass-card p-4 h-100">
                <h5 class="mb-3">
                    <i class="fas fa-tachometer-alt text-warning me-2"></i>
                    إحصائيات سريعة
                </h5>
                
                <div class="quick-stat-item">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>مدفوعات معلقة</span>
                        <strong class="text-danger"><?php echo number_format($pendingPayments, 0); ?>₪</strong>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-danger" style="width: 60%"></div>
                    </div>
                </div>

                <hr class="my-3">

                <div class="quick-stat-item">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span><i class="fas fa-car text-success"></i> متاحة</span>
                        <strong><?php echo $availableCars; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span><i class="fas fa-key text-warning"></i> مؤجرة</span>
                        <strong><?php echo $rentedCars; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-wrench text-info"></i> صيانة</span>
                        <strong><?php echo $totalCars - $availableCars - $rentedCars; ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tables Row -->
    <div class="row g-3">
        <!-- Recent Rentals -->
        <div class="col-lg-7">
            <div class="glass-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="fas fa-clock text-primary me-2"></i>
                        آخر الحجوزات
                    </h5>
                    <a href="rentals.php" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                </div>

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
                            <?php foreach ($recentRentals as $rental): ?>
                            <tr>
                                <td><small><?php echo $rental['rental_number']; ?></small></td>
                                <td><?php echo $rental['customer_name']; ?></td>
                                <td><small><?php echo $rental['brand'] . ' ' . $rental['model']; ?></small></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $rental['status'] === 'active' ? 'success' : 
                                            ($rental['status'] === 'confirmed' ? 'warning' : 'secondary'); 
                                    ?>">
                                        <?php echo RENTAL_STATUS[$rental['status']]; ?>
                                    </span>
                                </td>
                                <td><strong><?php echo number_format($rental['total_amount'], 0); ?>₪</strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Maintenance Alerts -->
        <div class="col-lg-5">
            <div class="glass-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="fas fa-tools text-warning me-2"></i>
                        صيانة قريبة
                    </h5>
                    <a href="maintenance.php" class="btn btn-sm btn-outline-warning">عرض الكل</a>
                </div>

                <?php if (empty($maintenanceDue)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                        <p>لا توجد صيانة قريبة</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($maintenanceDue as $car): ?>
                        <div class="list-group-item bg-transparent border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?php echo $car['brand'] . ' ' . $car['model']; ?></h6>
                                    <small class="text-muted"><?php echo $car['plate_number']; ?></small>
                                </div>
                                <span class="badge bg-<?php echo $car['days_until'] <= 0 ? 'danger' : 'warning'; ?>">
                                    <?php 
                                    if ($car['days_until'] <= 0) {
                                        echo 'متأخرة';
                                    } else {
                                        echo $car['days_until'] . ' يوم';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Revenue Chart
const ctx = document.getElementById('revenueChart');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['السبت', 'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'اليوم'],
        datasets: [{
            label: 'الإيرادات (₪)',
            data: [1200, 1900, 3000, 5000, 2300, 3200, <?php echo $todayRevenue; ?>],
            borderColor: '#FF5722',
            backgroundColor: 'rgba(255, 87, 34, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(255, 255, 255, 0.1)' },
                ticks: { color: '#999' }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#999' }
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>