<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];

// Mark as read
if (isset($_GET['mark_read'])) {
    $notifId = (int)$_GET['mark_read'];
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notifId, $userId]);
    redirect(ADMIN_URL . '/notifications.php');
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$userId]);
    redirect(ADMIN_URL . '/notifications.php');
}

// Delete notification
if (isset($_GET['delete'])) {
    $notifId = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notifId, $userId]);
    redirect(ADMIN_URL . '/notifications.php');
}

// Get notifications
$stmt = $db->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 50
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

// Count unread
$unreadStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$unreadStmt->execute([$userId]);
$unreadCount = $unreadStmt->fetchColumn();

$page_title = 'الإشعارات - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-bell me-2"></i>الإشعارات</h5>
            <p>جميع التنبيهات والرسائل</p>
        </div>
        <div class="top-bar-right">
            <?php if ($unreadCount > 0): ?>
            <a href="?mark_all_read" class="btn btn-primary">
                <i class="fas fa-check-double me-2"></i>تحديد الكل كمقروء
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(244, 67, 54, 0.1); color: #F44336;">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="stat-value"><?php echo $unreadCount; ?></div>
                <div class="stat-label">غير مقروءة</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(76, 175, 80, 0.1); color: #4CAF50;">
                    <i class="fas fa-check"></i>
                </div>
                <div class="stat-value"><?php echo count($notifications) - $unreadCount; ?></div>
                <div class="stat-label">مقروءة</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(33, 150, 243, 0.1); color: #2196F3;">
                    <i class="fas fa-list"></i>
                </div>
                <div class="stat-value"><?php echo count($notifications); ?></div>
                <div class="stat-label">الإجمالي</div>
            </div>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="table-container">
        <?php if (empty($notifications)): ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle fa-3x mb-3"></i>
            <h5>لا توجد إشعارات</h5>
        </div>
        <?php else: ?>
        <div class="list-group">
            <?php foreach ($notifications as $notif): ?>
            <div class="list-group-item <?php echo $notif['is_read'] ? '' : 'list-group-item-warning'; ?>">
                <div class="d-flex w-100 justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">
                            <?php
                            $icons = [
                                'info' => 'info-circle text-info',
                                'success' => 'check-circle text-success',
                                'warning' => 'exclamation-triangle text-warning',
                                'error' => 'times-circle text-danger'
                            ];
                            $icon = $icons[$notif['type']] ?? 'bell text-secondary';
                            ?>
                            <i class="fas fa-<?php echo $icon; ?> me-2"></i>
                            <?php echo $notif['title']; ?>
                            <?php if (!$notif['is_read']): ?>
                            <span class="badge bg-danger">جديد</span>
                            <?php endif; ?>
                        </h6>
                        <p class="mb-1"><?php echo $notif['message']; ?></p>
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            <?php echo formatDate($notif['created_at'], 'd/m/Y H:i'); ?>
                        </small>
                    </div>
                    <div class="btn-group">
                        <?php if (!$notif['is_read']): ?>
                        <a href="?mark_read=<?php echo $notif['id']; ?>" class="btn btn-sm btn-success" title="تحديد كمقروء">
                            <i class="fas fa-check"></i>
                        </a>
                        <?php endif; ?>
                        <a href="?delete=<?php echo $notif['id']; ?>" class="btn btn-sm btn-danger" title="حذف">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>