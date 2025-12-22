<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();
$current_page = 'reports';

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$report_type = $_GET['type'] ?? 'revenue';

// Revenue Report
if ($report_type === 'revenue') {
    $stmt = $db->prepare("
        SELECT 
            DATE(p.payment_date) as date,
            SUM(p.amount) as total,
            COUNT(DISTINCT p.rental_id) as rentals_count
        FROM payments p
        WHERE p.payment_date BETWEEN ? AND ?
        GROUP BY DATE(p.payment_date)
        ORDER BY date
    ");
    $stmt->execute([$start_date, $end_date]);
    $revenue_data = $stmt->fetchAll();
}

// Cars Performance
$stmt = $db->prepare("
    SELECT 
        c.id,
        c.brand,
        c.model,
        c.plate_number,
        COUNT(r.id) as total_rentals,
        SUM(r.total_amount) as total_revenue,
        AVG(r.total_days) as avg_days
    FROM cars c
    LEFT JOIN rentals r ON c.id = r.car_id AND r.start_date BETWEEN ? AND ?
    GROUP BY c.id
    ORDER BY total_revenue DESC
    LIMIT 10
");
$stmt->execute([$start_date, $end_date]);
$cars_performance = $stmt->fetchAll();

// Top Customers
$stmt = $db->prepare("
    SELECT 
        cu.id,
        cu.full_name,
        cu.phone,
        COUNT(r.id) as total_rentals,
        SUM(r.total_amount) as total_spent,
        cu.loyalty_level
    FROM customers cu
    LEFT JOIN rentals r ON cu.id = r.customer_id AND r.start_date BETWEEN ? AND ?
    GROUP BY cu.id
    HAVING total_rentals > 0
    ORDER BY total_spent DESC
    LIMIT 10
");
$stmt->execute([$start_date, $end_date]);
$top_customers = $stmt->fetchAll();

// Summary Stats
$stmt = $db->prepare("
    SELECT 
        COUNT(DISTINCT r.id) as total_rentals,
        SUM(p.amount) as total_revenue,
        SUM(e.amount) as total_expenses,
        (SUM(p.amount) - COALESCE(SUM(e.amount), 0)) as net_profit
    FROM rentals r
    LEFT JOIN payments p ON r.id = p.rental_id AND p.payment_date BETWEEN ? AND ?
    LEFT JOIN expenses e ON e.expense_date BETWEEN ? AND ?
    WHERE r.start_date BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);
$summary = $stmt->fetch();

$page_title = 'التقارير المتقدمة - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-chart-bar me-2"></i>التقارير المتقدمة</h5>
            <p>تحليل شامل للأداء والإيرادات</p>
        </div>
        <div class="top-bar-right">
            <button onclick="printReport()" class="btn btn-primary">
                <i class="fas fa-print me-2"></i>طباعة
            </button>
            <button onclick="exportPDF()" class="btn btn-danger">
                <i class="fas fa-file-pdf me-2"></i>PDF
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">من تاريخ</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">إلى تاريخ</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">نوع التقرير</label>
                    <select name="type" class="form-control">
                        <option value="revenue" <?php echo $report_type === 'revenue' ? 'selected' : ''; ?>>الإيرادات</option>
                        <option value="cars" <?php echo $report_type === 'cars' ? 'selected' : ''; ?>>أداء السيارات</option>
                        <option value="customers" <?php echo $report_type === 'customers' ? 'selected' : ''; ?>>العملاء</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>تطبيق
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-content">
                    <h6>إجمالي الحجوزات</h6>
                    <h3><?php echo number_format($summary['total_rentals']); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-success">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="stat-content">
                    <h6>إجمالي الإيرادات</h6>
                    <h3><?php echo formatCurrency($summary['total_revenue']); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-danger">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-content">
                    <h6>إجمالي المصروفات</h6>
                    <h3><?php echo formatCurrency($summary['total_expenses']); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-info">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <h6>صافي الربح</h6>
                    <h3><?php echo formatCurrency($summary['net_profit']); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Chart -->
    <?php if ($report_type === 'revenue' && !empty($revenue_data)): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h6><i class="fas fa-chart-area me-2"></i>الإيرادات اليومية</h6>
        </div>
        <div class="card-body">
            <canvas id="revenueChart" height="80"></canvas>
        </div>
    </div>
    <?php endif; ?>

    <!-- Cars Performance Table -->
    <div class="card mb-4">
        <div class="card-header">
            <h6><i class="fas fa-car me-2"></i>أداء السيارات (أعلى 10)</h6>
        </div>
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>السيارة</th>
                        <th>اللوحة</th>
                        <th>عدد الحجوزات</th>
                        <th>الإيرادات</th>
                        <th>متوسط الأيام</th>
                        <th>الأداء</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cars_performance as $i => $car): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo $car['brand'] . ' ' . $car['model']; ?></td>
                        <td><?php echo $car['plate_number']; ?></td>
                        <td><?php echo $car['total_rentals'] ?: 0; ?></td>
                        <td><strong><?php echo formatCurrency($car['total_revenue']); ?></strong></td>
                        <td><?php echo $car['avg_days'] ? round($car['avg_days'], 1) : 0; ?> يوم</td>
                        <td>
                            <div class="progress" style="height: 20px;">
                                <?php $percentage = $cars_performance[0]['total_revenue'] > 0 ? ($car['total_revenue'] / $cars_performance[0]['total_revenue']) * 100 : 0; ?>
                                <div class="progress-bar bg-success" style="width: <?php echo $percentage; ?>%">
                                    <?php echo round($percentage); ?>%
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Customers Table -->
    <div class="card">
        <div class="card-header">
            <h6><i class="fas fa-users me-2"></i>أفضل العملاء (أعلى 10)</h6>
        </div>
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>العميل</th>
                        <th>الهاتف</th>
                        <th>عدد الحجوزات</th>
                        <th>إجمالي المبلغ</th>
                        <th>مستوى الولاء</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_customers as $i => $customer): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo htmlspecialchars($customer['full_name']); ?></td>
                        <td><?php echo $customer['phone']; ?></td>
                        <td><?php echo $customer['total_rentals']; ?></td>
                        <td><strong><?php echo formatCurrency($customer['total_spent']); ?></strong></td>
                        <td><?php echo getLoyaltyBadge($customer['loyalty_level']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($report_type === 'revenue' && !empty($revenue_data)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Revenue Chart
const revenueData = <?php echo json_encode($revenue_data); ?>;
const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: revenueData.map(d => d.date),
        datasets: [{
            label: 'الإيرادات اليومية (₪)',
            data: revenueData.map(d => d.total),
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.3,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: true },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ₪' + context.parsed.y.toFixed(2);
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₪' + value;
                    }
                }
            }
        }
    }
});
</script>
<?php endif; ?>

<script>
function printReport() {
    window.print();
}

function exportPDF() {
    alert('ميزة تصدير PDF قيد التطوير');
}
</script>

<?php include 'includes/footer.php'; ?>