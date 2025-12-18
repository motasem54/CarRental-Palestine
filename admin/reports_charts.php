<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();

// Get data for charts
$months = [];
for ($i = 11; $i >= 0; $i--) {
    $months[] = date('Y-m', strtotime("-$i months"));
}

// Revenue by month
$revenueData = [];
foreach ($months as $month) {
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as total
        FROM rentals
        WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
    ");
    $stmt->execute([$month]);
    $revenueData[] = $stmt->fetchColumn();
}

// Rentals by month
$rentalsCount = [];
foreach ($months as $month) {
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM rentals
        WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
    ");
    $stmt->execute([$month]);
    $rentalsCount[] = $stmt->fetchColumn();
}

// Cars utilization
$carsUtilization = [];
$carsStmt = $db->query("SELECT id, brand, model FROM cars LIMIT 10");
$topCars = $carsStmt->fetchAll();
foreach ($topCars as $car) {
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(total_days), 0) as days
        FROM rentals
        WHERE car_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    ");
    $stmt->execute([$car['id']]);
    $carsUtilization[] = [
        'name' => $car['brand'] . ' ' . $car['model'],
        'days' => $stmt->fetchColumn()
    ];
}

// Payment methods distribution
$paymentMethods = [];
foreach (PAYMENT_METHODS as $key => $label) {
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(amount), 0) as total
        FROM payments
        WHERE payment_method = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    ");
    $stmt->execute([$key]);
    $total = $stmt->fetchColumn();
    if ($total > 0) {
        $paymentMethods[] = [
            'method' => $label,
            'total' => $total
        ];
    }
}

$page_title = 'التقارير المرئية - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-chart-pie me-2"></i>التقارير المرئية</h5>
            <p>رسومات بيانية تفاعلية وتحليلات مرئية</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Revenue Trend -->
        <div class="col-md-8">
            <div class="table-container">
                <h5 class="mb-3"><i class="fas fa-chart-line text-success"></i> اتجاه الإيرادات (آخر 12 شهر)</h5>
                <canvas id="revenueTrendChart" height="80"></canvas>
            </div>
        </div>
        
        <!-- Payment Methods -->
        <div class="col-md-4">
            <div class="table-container">
                <h5 class="mb-3"><i class="fas fa-chart-pie text-info"></i> طرق الدفع</h5>
                <canvas id="paymentMethodsChart"></canvas>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Cars Utilization -->
        <div class="col-md-6">
            <div class="table-container">
                <h5 class="mb-3"><i class="fas fa-chart-bar text-warning"></i> استخدام السيارات (أيام)</h5>
                <canvas id="carsUtilizationChart" height="150"></canvas>
            </div>
        </div>
        
        <!-- Rentals Count -->
        <div class="col-md-6">
            <div class="table-container">
                <h5 class="mb-3"><i class="fas fa-chart-area text-primary"></i> عدد الحجوزات الشهرية</h5>
                <canvas id="rentalsCountChart" height="150"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
// Revenue Trend
const revenueTrendCtx = document.getElementById('revenueTrendChart').getContext('2d');
new Chart(revenueTrendCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($months); ?>,
        datasets: [{
            label: 'الإيرادات (₪)',
            data: <?php echo json_encode($revenueData); ?>,
            borderColor: 'rgb(76, 175, 80)',
            backgroundColor: 'rgba(76, 175, 80, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: true } },
        scales: { y: { beginAtZero: true } }
    }
});

// Payment Methods
const paymentMethodsCtx = document.getElementById('paymentMethodsChart').getContext('2d');
new Chart(paymentMethodsCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($paymentMethods, 'method')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($paymentMethods, 'total')); ?>,
            backgroundColor: [
                'rgb(76, 175, 80)',
                'rgb(156, 39, 176)',
                'rgb(33, 150, 243)',
                'rgb(255, 152, 0)'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});

// Cars Utilization
const carsUtilizationCtx = document.getElementById('carsUtilizationChart').getContext('2d');
new Chart(carsUtilizationCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($carsUtilization, 'name')); ?>,
        datasets: [{
            label: 'عدد الأيام',
            data: <?php echo json_encode(array_column($carsUtilization, 'days')); ?>,
            backgroundColor: 'rgba(255, 152, 0, 0.8)'
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        scales: { x: { beginAtZero: true } }
    }
});

// Rentals Count
const rentalsCountCtx = document.getElementById('rentalsCountChart').getContext('2d');
new Chart(rentalsCountCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($months); ?>,
        datasets: [{
            label: 'عدد الحجوزات',
            data: <?php echo json_encode($rentalsCount); ?>,
            backgroundColor: 'rgba(33, 150, 243, 0.8)'
        }]
    },
    options: {
        responsive: true,
        scales: { y: { beginAtZero: true } }
    }
});
</script>

<?php include 'includes/footer.php'; ?>