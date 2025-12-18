<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

if ($_SESSION['user_role'] !== 'admin') {
    die('غير مصرح');
}

$db = Database::getInstance()->getConnection();

// Handle 2FA toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_2fa'])) {
    $userId = $_SESSION['user_id'];
    $enabled = isset($_POST['enable_2fa']) ? 1 : 0;
    
    if ($enabled) {
        // Generate secret
        require_once '../core/TwoFactorAuth.php';
        $tfa = new TwoFactorAuth();
        $secret = $tfa->generateSecret();
        
        $stmt = $db->prepare("UPDATE users SET two_factor_enabled = 1, two_factor_secret = ? WHERE id = ?");
        $stmt->execute([$secret, $userId]);
        
        $_SESSION['success'] = 'تم تفعيل المصادقة الثنائية';
    } else {
        $stmt = $db->prepare("UPDATE users SET two_factor_enabled = 0, two_factor_secret = NULL WHERE id = ?");
        $stmt->execute([$userId]);
        
        $_SESSION['success'] = 'تم إيقاف المصادقة الثنائية';
    }
    
    redirect(ADMIN_URL . '/security.php');
}

// Get security logs
$logsStmt = $db->prepare("
    SELECT * FROM activity_log 
    WHERE action_type IN ('login', 'logout', 'failed_login') 
    ORDER BY created_at DESC 
    LIMIT 50
");
$logsStmt->execute();
$logs = $logsStmt->fetchAll();

// Get current user
$userStmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$_SESSION['user_id']]);
$currentUser = $userStmt->fetch();

$page_title = 'الأمان - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-shield-alt me-2"></i>الأمان والحماية</h5>
            <p>إعدادات الأمان وسجل الدخول</p>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- 2FA Settings -->
    <div class="table-container mb-4">
        <h5 class="mb-3">
            <i class="fas fa-mobile-alt text-primary"></i>
            المصادقة الثنائية (2FA)
        </h5>
        <form method="POST">
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="enable_2fa" 
                       id="enable2fa" <?php echo $currentUser['two_factor_enabled'] ? 'checked' : ''; ?>>
                <label class="form-check-label" for="enable2fa">
                    تفعيل المصادقة الثنائية
                </label>
            </div>
            <?php if ($currentUser['two_factor_enabled']): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                المصادقة الثنائية مفعلة. حسابك محمي بشكل إضافي.
            </div>
            <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                ينصح بتفعيل المصادقة الثنائية لحماية أفضل.
            </div>
            <?php endif; ?>
            <button type="submit" name="toggle_2fa" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>حفظ التغييرات
            </button>
        </form>
    </div>

    <!-- Security Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(76, 175, 80, 0.1); color: #4CAF50;">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
                <div class="stat-value">
                    <?php
                    $successLogins = array_filter($logs, fn($l) => $l['action_type'] === 'login');
                    echo count($successLogins);
                    ?>
                </div>
                <div class="stat-label">تسجيلات ناجحة</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(244, 67, 54, 0.1); color: #F44336;">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-value">
                    <?php
                    $failedLogins = array_filter($logs, fn($l) => $l['action_type'] === 'failed_login');
                    echo count($failedLogins);
                    ?>
                </div>
                <div class="stat-label">محاولات فاشلة</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(33, 150, 243, 0.1); color: #2196F3;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value">
                    <?php
                    $uniqueIPs = array_unique(array_column($logs, 'ip_address'));
                    echo count($uniqueIPs);
                    ?>
                </div>
                <div class="stat-label">عناوين IP مختلفة</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(255, 152, 0, 0.1); color: #FF9800;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value">
                    <?php echo !empty($logs) ? formatDate($logs[0]['created_at'], 'H:i') : '-'; ?>
                </div>
                <div class="stat-label">آخر دخول</div>
            </div>
        </div>
    </div>

    <!-- Security Log -->
    <div class="table-container">
        <h5 class="mb-3">
            <i class="fas fa-history text-info"></i>
            سجل تسجيلات الدخول (آخر 50)
        </h5>
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>المستخدم</th>
                        <th>العملية</th>
                        <th>IP</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo formatDate($log['created_at'], 'd/m/Y H:i:s'); ?></td>
                        <td>
                            <?php
                            $userStmt = $db->prepare("SELECT username FROM users WHERE id = ?");
                            $userStmt->execute([$log['user_id']]);
                            echo $userStmt->fetchColumn();
                            ?>
                        </td>
                        <td>
                            <?php
                            $types = [
                                'login' => ['label' => 'دخول', 'color' => 'success'],
                                'logout' => ['label' => 'خروج', 'color' => 'secondary'],
                                'failed_login' => ['label' => 'فشل', 'color' => 'danger']
                            ];
                            $type = $types[$log['action_type']] ?? ['label' => $log['action_type'], 'color' => 'info'];
                            ?>
                            <span class="badge bg-<?php echo $type['color']; ?>"><?php echo $type['label']; ?></span>
                        </td>
                        <td><code><?php echo $log['ip_address']; ?></code></td>
                        <td>
                            <?php if ($log['action_type'] === 'failed_login'): ?>
                            <i class="fas fa-exclamation-triangle text-danger"></i>
                            <?php else: ?>
                            <i class="fas fa-check-circle text-success"></i>
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