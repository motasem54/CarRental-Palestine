<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();
$current_page = 'loyalty';

// Get loyalty statistics
try {
    $stats = $db->query("
        SELECT 
            loyalty_level,
            COUNT(*) as count,
            SUM(loyalty_points) as total_points
        FROM customers
        GROUP BY loyalty_level
        ORDER BY loyalty_level
    ")->fetchAll();
} catch (Exception $e) {
    $stats = [];
}

$page_title = 'برنامج الولاء - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-gift me-2"></i>برنامج الولاء</h5>
            <p>إدارة برنامج ولاء العملاء والمكافآت</p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="table-container">
                <h6 class="mb-3">مستويات الولاء:</h6>
                <div class="row">
                    <?php 
                    $colors = ['bronze' => 'secondary', 'silver' => 'info', 'gold' => 'warning', 'platinum' => 'primary'];
                    $icons = ['bronze' => 'medal', 'silver' => 'award', 'gold' => 'trophy', 'platinum' => 'crown'];
                    foreach ($stats as $stat): 
                    ?>
                    <div class="col-md-3 mb-3">
                        <div class="card border-<?php echo $colors[$stat['loyalty_level']]; ?>">
                            <div class="card-body text-center">
                                <i class="fas fa-<?php echo $icons[$stat['loyalty_level']]; ?> fa-3x text-<?php echo $colors[$stat['loyalty_level']]; ?> mb-2"></i>
                                <h5><?php echo LOYALTY_LEVELS[$stat['loyalty_level']]; ?></h5>
                                <p class="mb-1">عدد العملاء: <strong><?php echo $stat['count']; ?></strong></p>
                                <p class="mb-0">إجمالي النقاط: <strong><?php echo number_format($stat['total_points']); ?></strong></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="table-container">
        <h6 class="mb-3">قواعد برنامج الولاء:</h6>
        <table class="table">
            <thead>
                <tr>
                    <th>المستوى</th>
                    <th>الحد الأدنى للنقاط</th>
                    <th>المزايا</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="badge bg-secondary">برونزي</span></td>
                    <td>0 نقطة</td>
                    <td>نقاط أساسية</td>
                </tr>
                <tr>
                    <td><span class="badge bg-info">فضي</span></td>
                    <td>500 نقطة</td>
                    <td>خصم 5%</td>
                </tr>
                <tr>
                    <td><span class="badge bg-warning">ذهبي</span></td>
                    <td>1500 نقطة</td>
                    <td>خصم 10% + أولوية الحجز</td>
                </tr>
                <tr>
                    <td><span class="badge bg-primary">بلاتيني</span></td>
                    <td>5000 نقطة</td>
                    <td>خصم 15% + ترقية مجانية</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>