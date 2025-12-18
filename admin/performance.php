<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();

// Database stats
$dbSize = $db->query("
    SELECT 
        SUM(data_length + index_length) / 1024 / 1024 AS size_mb
    FROM information_schema.TABLES 
    WHERE table_schema = DATABASE()
")->fetchColumn();

// Table stats
$tables = $db->query("
    SELECT 
        table_name,
        table_rows,
        ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb
    FROM information_schema.TABLES 
    WHERE table_schema = DATABASE()
    ORDER BY (data_length + index_length) DESC
")->fetchAll();

// System info
$serverInfo = [
    'PHP Version' => phpversion(),
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'Max Upload' => ini_get('upload_max_filesize'),
    'Max Execution Time' => ini_get('max_execution_time') . 's',
    'Memory Limit' => ini_get('memory_limit'),
    'Timezone' => date_default_timezone_get()
];

// Recent activity
$activityStmt = $db->query("
    SELECT COUNT(*) as total, DATE(created_at) as date
    FROM activity_log
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
");
$recentActivity = $activityStmt->fetchAll();

$page_title = 'مراقبة الأداء - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-tachometer-alt me-2"></i>مراقبة الأداء</h5>
            <p>معلومات النظام وقاعدة البيانات</p>
        </div>
    </div>

    <!-- Database Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(33, 150, 243, 0.1); color: #2196F3;">
                    <i class="fas fa-database"></i>
                </div>
                <div class="stat-value"><?php echo round($dbSize, 2); ?> MB</div>
                <div class="stat-label">حجم قاعدة البيانات</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(76, 175, 80, 0.1); color: #4CAF50;">
                    <i class="fas fa-table"></i>
                </div>
                <div class="stat-value"><?php echo count($tables); ?></div>
                <div class="stat-label">عدد الجداول</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(255, 152, 0, 0.1); color: #FF9800;">
                    <i class="fas fa-history"></i>
                </div>
                <div class="stat-value"><?php echo array_sum(array_column($recentActivity, 'total')); ?></div>
                <div class="stat-label">نشاط آخر 7 أيام</div>
            </div>
        </div>
    </div>

    <!-- Tables Info -->
    <div class="table-container mb-4">
        <h5 class="mb-3">
            <i class="fas fa-list text-primary"></i>
            تفاصيل الجداول
        </h5>
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>اسم الجدول</th>
                        <th>عدد السجلات</th>
                        <th>الحجم (MB)</th>
                        <th>النسبة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tables as $table): ?>
                    <tr>
                        <td><code><?php echo $table['table_name']; ?></code></td>
                        <td><?php echo number_format($table['table_rows']); ?></td>
                        <td><?php echo $table['size_mb']; ?> MB</td>
                        <td>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: <?php echo ($table['size_mb'] / $dbSize * 100); ?>%">
                                    <?php echo round($table['size_mb'] / $dbSize * 100, 1); ?>%
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-4">
        <!-- System Info -->
        <div class="col-md-6">
            <div class="table-container">
                <h5 class="mb-3">
                    <i class="fas fa-server text-success"></i>
                    معلومات السيرفر
                </h5>
                <table class="table table-bordered">
                    <?php foreach ($serverInfo as $key => $value): ?>
                    <tr>
                        <th style="width: 50%;"><?php echo $key; ?></th>
                        <td><code><?php echo $value; ?></code></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <!-- Activity Chart -->
        <div class="col-md-6">
            <div class="table-container">
                <h5 class="mb-3">
                    <i class="fas fa-chart-line text-info"></i>
                    النشاط اليومي (آخر 7 أيام)
                </h5>
                <canvas id="activityChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('activityChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($recentActivity, 'date')); ?>,
        datasets: [{
            label: 'عدد العمليات',
            data: <?php echo json_encode(array_column($recentActivity, 'total')); ?>,
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