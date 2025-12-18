<?php
require_once '../../config/settings.php';

if (!isset($_SESSION['customer_id'])) {
    redirect(BASE_URL . '/public/customer/login.php');
}

$db = Database::getInstance()->getConnection();
$customerId = $_SESSION['customer_id'];

// Get customer data
$stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customerId]);
$customer = $stmt->fetch();

// Get rentals
$rentalsStmt = $db->prepare("
    SELECT r.*, c.brand, c.model, c.plate_number
    FROM rentals r
    JOIN cars c ON r.car_id = c.id
    WHERE r.customer_id = ?
    ORDER BY r.created_at DESC
");
$rentalsStmt->execute([$customerId]);
$rentals = $rentalsStmt->fetchAll();

// Get loyalty points
$pointsStmt = $db->prepare("
    SELECT * FROM customer_points 
    WHERE customer_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$pointsStmt->execute([$customerId]);
$points = $pointsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة العميل - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; background: #f8f9fa; }
        .navbar { background: #121212; }
        .card { border-radius: 15px; margin-bottom: 20px; border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .stat-card { text-align: center; padding: 30px; }
        .stat-value { font-size: 2.5rem; font-weight: bold; color: #FF5722; }
        .loyalty-badge { font-size: 3rem; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark">
        <div class="container">
            <span class="navbar-brand">
                <i class="fas fa-user-circle me-2"></i>
                مرحباً <?php echo $customer['full_name']; ?>
            </span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">
                <i class="fas fa-sign-out-alt me-2"></i>خروج
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-value"><?php echo count($rentals); ?></div>
                    <div>إجمالي الحجوزات</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-value"><?php echo number_format($customer['loyalty_points']); ?></div>
                    <div>نقاط الولاء</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="loyalty-badge">
                        <?php
                        $icons = ['bronze' => '🥉', 'silver' => '🥈', 'gold' => '🥇', 'platinum' => '💎'];
                        echo $icons[$customer['loyalty_level']];
                        ?>
                    </div>
                    <div><?php echo LOYALTY_LEVELS[$customer['loyalty_level']]; ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <a href="../booking.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus me-2"></i>حجز جديد
                    </a>
                </div>
            </div>
        </div>

        <!-- Rentals History -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5><i class="fas fa-history me-2"></i>تاريخ الحجوزات</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>رقم الحجز</th>
                                <th>السيارة</th>
                                <th>الفترة</th>
                                <th>المبلغ</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rentals as $rental): ?>
                            <tr>
                                <td><strong><?php echo $rental['rental_number']; ?></strong></td>
                                <td><?php echo $rental['brand'] . ' ' . $rental['model']; ?></td>
                                <td>
                                    <?php echo formatDate($rental['start_date'], 'd/m/Y'); ?> - 
                                    <?php echo formatDate($rental['end_date'], 'd/m/Y'); ?>
                                </td>
                                <td><strong><?php echo formatCurrency($rental['total_amount']); ?></strong></td>
                                <td>
                                    <?php
                                    $colors = ['pending' => 'warning', 'confirmed' => 'info', 
                                              'active' => 'success', 'completed' => 'secondary'];
                                    ?>
                                    <span class="badge bg-<?php echo $colors[$rental['status']]; ?>">
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

        <!-- Loyalty Points History -->
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5><i class="fas fa-star me-2"></i>سجل نقاط الولاء</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>التاريخ</th>
                                <th>العملية</th>
                                <th>النقاط</th>
                                <th>الوصف</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($points as $point): ?>
                            <tr>
                                <td><?php echo formatDate($point['created_at'], 'd/m/Y H:i'); ?></td>
                                <td>
                                    <?php if ($point['points'] > 0): ?>
                                        <span class="badge bg-success">إضافة</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">استخدام</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo $point['points'] > 0 ? '+' : ''; ?><?php echo $point['points']; ?></strong></td>
                                <td><?php echo $point['description']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>