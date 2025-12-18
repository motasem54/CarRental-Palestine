<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();

// Current month stats
$currentMonth = date('Y-m');
$startOfMonth = $currentMonth . '-01';
$endOfMonth = date('Y-m-t');

// Revenue by day (last 30 days)
$revenueStmt = $db->prepare("
    SELECT DATE(created_at) as date, SUM(amount) as total
    FROM payments
    WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$revenueStmt->execute();
$revenueData = $revenueStmt->fetchAll();

// Rentals by status
$rentalsStmt = $db->query("
    SELECT status, COUNT(*) as count
    FROM rentals
    GROUP BY status
");
$rentalsData = $rentalsStmt->fetchAll();

// Cars by status
$carsStmt = $db->query("
    SELECT status, COUNT(*) as count
    FROM cars
    GROUP BY status
");
$carsData = $carsStmt->fetchAll();

// Top 5 cars by revenue
$topCarsStmt = $db->query("
    SELECT c.brand, c.model, c.plate_number, 
           COUNT(r.id) as rentals,
           SUM(r.total_amount) as revenue
    FROM cars c
    LEFT JOIN rentals r ON c.id = r.car_id
    WHERE r.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY c.id
    ORDER BY revenue DESC
    LIMIT 5
");
$topCars = $topCarsStmt->fetchAll();

// Monthly comparison
$monthlyStmt = $db->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as rentals,
        SUM(total_amount) as revenue
    FROM rentals
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY month
    ORDER BY month ASC
");
$monthlyData = $monthlyStmt->fetchAll();

$page_title = 'Dashboard المتقدم - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-chart-line me-2"></i>Dashboard المتقدم</h5>
            <p>رسومات بيانية وإحصائيات تفصيلية</p>
        </div>
        <div class="top-bar-right">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right me-2"></i>Dashboard العادي
            </a>
        </div>
    </div>

    <!-- Revenue Chart -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="table-container">
                <h5 class="mb-3"><i class="fas fa-chart-area text-success"></i> الإيرادات اليومية (آخر 30 يوم)</h5>
                <canvas id="revenueChart" height="80"></canvas>
            </div>
        </div>
        <div class="col-md-4">
            <div class="table-container">
                <h5 class="mb-3"><i class="fas fa-chart-pie text-primary"></i> حالات الحجوزات</h5>
                <canvas id="rentalsChart"></canvas>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="table-container">
                <h5 class="mb-3"><i class="fas fa-chart-bar text-info"></i> حالات السيارات</h5>
                <canvas id="carsChart" height="150"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="table-container">
                <h5 class="mb-3"><i class="fas fa-trophy text-warning"></i> أفضل 5 سيارات</h5>
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
                            <td><span class="badge bg-info"><?php echo $car['rentals']; ?></span></td>
                            <td><strong class="text-success"><?php echo formatCurrency($car['revenue']); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Monthly Comparison -->
    <div class="table-container">
        <h5 class="mb-3"><i class="fas fa-chart-line text-danger"></i> المقارنة الشهرية (آخر 12 شهر)</h5>
        <canvas id="monthlyChart" height="80"></canvas>
    </div>
</div>

<script>
// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($revenueData, 'date')); ?>,
        datasets: [{
            label: 'الإيرادات (₪)',
            data: <?php echo json_encode(array_column($revenueData, 'total')); ?>,
            borderColor: 'rgb(76, 175, 80)',
            backgroundColor: 'rgba(76, 175, 80, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: true }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Rentals Pie Chart
const rentalsCtx = document.getElementById('rentalsChart').getContext('2d');
const rentalsChart = new Chart(rentalsCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_map(fn($r) => RENTAL_STATUS[$r['status']], $rentalsData)); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($rentalsData, 'count')); ?>,
            backgroundColor: [
                'rgb(255, 152, 0)',
                'rgb(33, 150, 243)',
                'rgb(76, 175, 80)',
                'rgb(158, 158, 158)',
                'rgb(244, 67, 54)'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// Cars Bar Chart
const carsCtx = document.getElementById('carsChart').getContext('2d');
const carsChart = new Chart(carsCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_map(fn($c) => CAR_STATUS[$c['status']], $carsData)); ?>,
        datasets: [{
            label: 'عدد السيارات',
            data: <?php echo json_encode(array_column($carsData, 'count')); ?>,
            backgroundColor: [
                'rgba(76, 175, 80, 0.8)',
                'rgba(255, 152, 0, 0.8)',
                'rgba(244, 67, 54, 0.8)',
                'rgba(33, 150, 243, 0.8)'
            ]
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Monthly Chart
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
const monthlyChart = new Chart(monthlyCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($monthlyData, 'month')); ?>,
        datasets: [
            {
                label: 'عدد الحجوزات',
                data: <?php echo json_encode(array_column($monthlyData, 'rentals')); ?>,
                backgroundColor: 'rgba(33, 150, 243, 0.8)',
                yAxisID: 'y'
            },
            {
                label: 'الإيرادات (₪)',
                data: <?php echo json_encode(array_column($monthlyData, 'revenue')); ?>,
                backgroundColor: 'rgba(76, 175, 80, 0.8)',
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                type: 'linear',
                position: 'left',
                beginAtZero: true
            },
            y1: {
                type: 'linear',
                position: 'right',
                beginAtZero: true,
                grid: { drawOnChartArea: false }
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>