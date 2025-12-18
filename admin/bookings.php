<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();

// Handle status update
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'approve') {
        $stmt = $db->prepare("UPDATE online_bookings SET status = 'approved' WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = 'تم قبول طلب الحجز';
    } elseif ($action === 'reject') {
        $stmt = $db->prepare("UPDATE online_bookings SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$id]);
        // Update car status back to available
        $booking = $db->prepare("SELECT car_id FROM online_bookings WHERE id = ?");
        $booking->execute([$id]);
        $carId = $booking->fetchColumn();
        $db->prepare("UPDATE cars SET status = 'available' WHERE id = ?")->execute([$carId]);
        $_SESSION['success'] = 'تم رفض طلب الحجز';
    }
    redirect('bookings.php');
}

// Get all online bookings
$stmt = $db->query("
    SELECT ob.*, c.brand, c.model, c.plate_number
    FROM online_bookings ob
    JOIN cars c ON ob.car_id = c.id
    ORDER BY ob.created_at DESC
");
$bookings = $stmt->fetchAll();

$page_title = 'طلبات الحجز الأونلاين - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-globe me-2"></i>طلبات الحجز الأونلاين</h5>
            <p>إدارة طلبات الحجز من الموقع</p>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="row g-3 mb-4">
        <?php
        $stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'converted' => 0];
        foreach ($bookings as $booking) {
            $stats[$booking['status']]++;
        }
        ?>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(255, 152, 0, 0.1); color: #FF9800;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">قيد الانتظار</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(76, 175, 80, 0.1); color: #4CAF50;">
                    <i class="fas fa-check"></i>
                </div>
                <div class="stat-value"><?php echo $stats['approved']; ?></div>
                <div class="stat-label">مقبولة</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(244, 67, 54, 0.1); color: #F44336;">
                    <i class="fas fa-times"></i>
                </div>
                <div class="stat-value"><?php echo $stats['rejected']; ?></div>
                <div class="stat-label">مرفوضة</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(33, 150, 243, 0.1); color: #2196F3;">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="stat-value"><?php echo $stats['converted']; ?></div>
                <div class="stat-label">تم تحويلها</div>
            </div>
        </div>
    </div>

    <!-- Bookings Table -->
    <div class="table-container">
        <h5 class="mb-3">
            <i class="fas fa-list text-primary"></i>
            قائمة الطلبات
        </h5>
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>رقم الحجز</th>
                        <th>العميل</th>
                        <th>الهاتف</th>
                        <th>السيارة</th>
                        <th>التاريخ</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $index => $booking): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><strong><?php echo $booking['booking_number']; ?></strong></td>
                        <td>
                            <?php echo $booking['customer_name']; ?><br>
                            <small class="text-muted">ID: <?php echo $booking['id_number']; ?></small>
                        </td>
                        <td><?php echo $booking['customer_phone']; ?></td>
                        <td><?php echo $booking['brand'] . ' ' . $booking['model']; ?><br>
                            <small class="text-muted"><?php echo $booking['plate_number']; ?></small>
                        </td>
                        <td>
                            <?php echo formatDate($booking['start_date'], 'd/m'); ?> - 
                            <?php echo formatDate($booking['end_date'], 'd/m/Y'); ?>
                        </td>
                        <td>
                            <?php
                            $statusColors = [
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'converted' => 'info'
                            ];
                            $color = $statusColors[$booking['status']];
                            ?>
                            <span class="badge bg-<?php echo $color; ?>">
                                <?php echo BOOKING_STATUS[$booking['status']]; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($booking['status'] === 'pending'): ?>
                            <div class="btn-group" role="group">
                                <a href="?action=approve&id=<?php echo $booking['id']; ?>" 
                                   class="btn btn-sm btn-success" 
                                   onclick="return confirm('قبول هذا الطلب؟')">
                                    <i class="fas fa-check"></i> قبول
                                </a>
                                <a href="?action=reject&id=<?php echo $booking['id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('رفض هذا الطلب؟')">
                                    <i class="fas fa-times"></i> رفض
                                </a>
                            </div>
                            <?php else: ?>
                            <button class="btn btn-sm btn-info" title="عرض">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>