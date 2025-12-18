<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();

// Date filters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Revenue Report
$revenueStmt = $db->prepare("
    SELECT 
        DATE(created_at) as date,
        SUM(amount) as total,
        COUNT(*) as count
    FROM payments
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date DESC
");
$revenueStmt->execute([$startDate, $endDate]);
$revenueData = $revenueStmt->fetchAll();

// Rentals Report
$rentalsStmt = $db->prepare("
    SELECT status, COUNT(*) as count, SUM(total_amount) as total
    FROM rentals
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY status
");
$rentalsStmt->execute([$startDate, $endDate]);
$rentalsStats = $rentalsStmt->fetchAll();

// Top Cars
$topCarsStmt = $db->prepare("
    SELECT c.brand, c.model, c.plate_number, 
           COUNT(r.id) as rental_count,
           SUM(r.total_amount) as total_revenue
    FROM cars c
    LEFT JOIN rentals r ON c.id = r.car_id
    WHERE DATE(r.created_at) BETWEEN ? AND ?
    GROUP BY c.id
    ORDER BY rental_count DESC
    LIMIT 10
");
$topCarsStmt->execute([$startDate, $endDate]);
$topCars = $topCarsStmt->fetchAll();

// Top Customers
$topCustomersStmt = $db->prepare("
    SELECT cu.full_name, cu.phone,
           COUNT(r.id) as rental_count,
           SUM(r.total_amount) as total_spent,
           cu.loyalty_level
    FROM customers cu
    LEFT JOIN rentals r ON cu.id = r.customer_id
    WHERE DATE(r.created_at) BETWEEN ? AND ?
    GROUP BY cu.id
    ORDER BY total_spent DESC
    LIMIT 10
");
$topCustomersStmt->execute([$startDate, $endDate]);
$topCustomers = $topCustomersStmt->fetchAll();

$page_title = 'التقارير - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-chart-line me-2"></i>التقارير والإحصائيات</h5>
            <p>تقارير مفصلة عن الأداء والإيرادات</p>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="table-container mb-4">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">من تاريخ</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">إلى تاريخ</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-2"></i>تصفية
                </button>
            </div>
        </form>
    </div>

    <!-- Rentals Stats -->
    <div class="table-container mb-4">
        <h5 class="mb-3"><i class="fas fa-calendar text-primary"></i> إحصائيات الحجوزات</h5>
        <div class="row g-3">
            <?php 
            $statusColors = [
                'pending' => ['warning', 'clock'],
                'confirmed' => ['info', 'check'],
                'active' => ['success', 'play'],
                'completed' => ['secondary', 'flag-checkered'],
                'cancelled' => ['danger', 'times']
            ];
            foreach ($rentalsStats as $stat): 
                $color = $statusColors[$stat['status']][0];
                $icon = $statusColors[$stat['status']][1];
            ?>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(var(--bs-<?php echo $color; ?>-rgb), 0.1); color: var(--bs-<?php echo $color; ?>);">
                        <i class="fas fa-<?php echo $icon; ?>"></i>
                    </div>
                    <div class="stat-value"><?php echo $stat['count']; ?></div>
                    <div class="stat-label"><?php echo RENTAL_STATUS[$stat['status']]; ?></div>
                    <small class="text-muted"><?php echo formatCurrency($stat['total']); ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Revenue Table -->
    <div class="table-container mb-4">
        <h5 class="mb-3"><i class="fas fa-dollar-sign text-success"></i> تقرير الإيرادات اليومية</h5>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>عدد المدفوعات</th>
                    <th>الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalRevenue = 0;
                foreach ($revenueData as $row): 
                    $totalRevenue += $row['total'];
                ?>
                <tr>
                    <td><?php echo formatDate($row['date'], 'd/m/Y'); ?></td>
                    <td><?php echo $row['count']; ?> دفعة</td>
                    <td><strong class="text-success"><?php echo formatCurrency($row['total']); ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="table-primary">
                    <th>الإجمالي الكلي</th>
                    <th><?php echo count($revenueData); ?> يوم</th>
                    <th><strong class="text-success"><?php echo formatCurrency($totalRevenue); ?></strong></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="row g-4">
        <!-- Top Cars -->
        <div class="col-md-6">
            <div class="table-container">
                <h5 class="mb-3"><i class="fas fa-car text-primary"></i> أكثر السيارات طلباً</h5>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>السيارة</th>
                            <th>الحجوزات</th>
                            <th>الإيراد</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topCars as $index => $car): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo $car['brand'] . ' ' . $car['model']; ?><br>
                                <small class="text-muted"><?php echo $car['plate_number']; ?></small>
                            </td>
                            <td><span class="badge bg-info"><?php echo $car['rental_count']; ?></span></td>
                            <td><strong class="text-success"><?php echo formatCurrency($car['total_revenue']); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Customers -->
        <div class="col-md-6">
            <div class="table-container">
                <h5 class="mb-3"><i class="fas fa-users text-warning"></i> أفضل العملاء</h5>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>العميل</th>
                            <th>الحجوزات</th>
                            <th>الإنفاق</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topCustomers as $index => $customer): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo $customer['full_name']; ?><br>
                                <small class="text-muted"><?php echo $customer['phone']; ?></small>
                            </td>
                            <td><span class="badge bg-info"><?php echo $customer['rental_count']; ?></span></td>
                            <td><strong class="text-success"><?php echo formatCurrency($customer['total_spent']); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>