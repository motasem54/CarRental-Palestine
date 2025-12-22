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

// Monthly revenue
$monthlyStmt = $db->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        SUM(amount) as total
    FROM payments
    WHERE YEAR(created_at) = YEAR(CURDATE())
    GROUP BY month
    ORDER BY month
");
$monthlyData = $monthlyStmt->fetchAll();

// Car type distribution
$carTypesStmt = $db->query("
    SELECT type, COUNT(*) as count
    FROM cars
    GROUP BY type
");
$carTypes = $carTypesStmt->fetchAll();

// Rental status distribution
$statusStmt = $db->query("
    SELECT status, COUNT(*) as count, SUM(total_amount) as total
    FROM rentals
    WHERE DATE(created_at) BETWEEN '$startDate' AND '$endDate'
    GROUP BY status
");
$statusData = $statusStmt->fetchAll();

$current_page = 'reports';
$page_title = 'تقارير متقدمة - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-chart-bar me-2"></i>تقارير متقدمة مع رسوم بيانية</h5>
            <p>تحليل مرئي للبيانات والإحصائيات</p>
        </div>
        <div class="top-bar-right">
            <a href="reports.php" class="btn btn-secondary">
                <i class="fas fa-table me-2"></i>عرض جدولي
            </a>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <!-- Monthly Revenue Chart -->
        <div class="col-md-8">
            <div class="table-container">
                <h5 class="mb-3"><i class="fas fa-chart-line text-success"></i> الإيرادات الشهرية</h5>
                <canvas id="revenueChart" height="100"></canvas>
            </div>
        </div>

        <!-- Rental Status Chart -->
        <div class="col-md-4">
            <div class="table-container">
                <h5 class="mb-3"><i class="fas fa-chart-pie text-primary"></i> حالة الحجوزات</h5>
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Car Types Chart -->
        <div class="col-md-6">
            <div class="table-container">
                <h5 class="mb-3"><i class="fas fa-chart-bar text-warning"></i> توزيع أنواع السيارات</h5>
                <canvas id="carTypesChart" height="150"></canvas>
            </div>
        </div>

        <!-- Revenue Stats -->
        <div class="col-md-6">
            <div class="table-container">
                <h5 class="mb-3"><i class="fas fa-dollar-sign text-success"></i> ملخص مالي</h5>
                <?php
                $totalRevenue = array_sum(array_column($monthlyData, 'total'));
                $avgRevenue = count($monthlyData) > 0 ? $totalRevenue / count($monthlyData) : 0;
                ?>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="stat-card">
                            <div class="stat-icon bg-success">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="stat-value"><?php echo formatCurrency($totalRevenue); ?></div>
                            <div class="stat-label">إجمالي الإيرادات</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card">
                            <div class="stat-icon bg-info">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-value"><?php echo formatCurrency($avgRevenue); ?></div>
                            <div class="stat-label">متوسط شهري</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Monthly Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: [<?php foreach($monthlyData as $m) echo "'" . $m['month'] . "',"; ?>],
        datasets: [{
            label: 'الإيرادات (₪)',
            data: [<?php foreach($monthlyData as $m) echo $m['total'] . ','; ?>],
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
            legend: { display: true, position: 'top' }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Rental Status Pie Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: [<?php foreach($statusData as $s) echo "'" . RENTAL_STATUS[$s['status']] . "',"; ?>],
        datasets: [{
            data: [<?php foreach($statusData as $s) echo $s['count'] . ','; ?>],
            backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#6c757d', '#dc3545']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// Car Types Bar Chart
const carTypesCtx = document.getElementById('carTypesChart').getContext('2d');
new Chart(carTypesCtx, {
    type: 'bar',
    data: {
        labels: [<?php foreach($carTypes as $ct) echo "'" . CAR_TYPES[$ct['type']] . "',"; ?>],
        datasets: [{
            label: 'عدد السيارات',
            data: [<?php foreach($carTypes as $ct) echo $ct['count'] . ','; ?>],
            backgroundColor: '#FF5722'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>