<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();

// Get filters
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$action_type = isset($_GET['action_type']) ? $_GET['action_type'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Build query
$sql = "SELECT al.*, u.username, u.full_name
        FROM activity_log al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE DATE(al.created_at) BETWEEN ? AND ?";

$params = [$start_date, $end_date];

if ($user_id > 0) {
    $sql .= " AND al.user_id = ?";
    $params[] = $user_id;
}

if ($action_type) {
    $sql .= " AND al.action_type = ?";
    $params[] = $action_type;
}

$sql .= " ORDER BY al.created_at DESC LIMIT 500";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get users for filter
$usersStmt = $db->query("SELECT id, username, full_name FROM users ORDER BY username");
$users = $usersStmt->fetchAll();

$page_title = 'سجل النشاطات - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-history me-2"></i>سجل النشاطات</h5>
            <p>تتبع جميع العمليات والأنشطة في النظام</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="table-container mb-4">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">المستخدم</label>
                <select name="user_id" class="form-control">
                    <option value="">الكل</option>
                    <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user['id']; ?>" <?php echo $user_id == $user['id'] ? 'selected' : ''; ?>>
                        <?php echo $user['username']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">نوع العملية</label>
                <select name="action_type" class="form-control">
                    <option value="">الكل</option>
                    <option value="login" <?php echo $action_type == 'login' ? 'selected' : ''; ?>>تسجيل دخول</option>
                    <option value="logout" <?php echo $action_type == 'logout' ? 'selected' : ''; ?>>تسجيل خروج</option>
                    <option value="create" <?php echo $action_type == 'create' ? 'selected' : ''; ?>>إضافة</option>
                    <option value="update" <?php echo $action_type == 'update' ? 'selected' : ''; ?>>تعديل</option>
                    <option value="delete" <?php echo $action_type == 'delete' ? 'selected' : ''; ?>>حذف</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">من تاريخ</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">إلى تاريخ</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-2"></i>تصفية
                </button>
            </div>
        </form>
    </div>

    <!-- Statistics -->
    <div class="row g-3 mb-4">
        <?php
        $stats = ['login' => 0, 'create' => 0, 'update' => 0, 'delete' => 0];
        foreach ($logs as $log) {
            if (isset($stats[$log['action_type']])) {
                $stats[$log['action_type']]++;
            }
        }
        ?>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(33, 150, 243, 0.1); color: #2196F3;">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
                <div class="stat-value"><?php echo $stats['login']; ?></div>
                <div class="stat-label">تسجيلات دخول</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(76, 175, 80, 0.1); color: #4CAF50;">
                    <i class="fas fa-plus"></i>
                </div>
                <div class="stat-value"><?php echo $stats['create']; ?></div>
                <div class="stat-label">إضافات</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(255, 152, 0, 0.1); color: #FF9800;">
                    <i class="fas fa-edit"></i>
                </div>
                <div class="stat-value"><?php echo $stats['update']; ?></div>
                <div class="stat-label">تعديلات</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(244, 67, 54, 0.1); color: #F44336;">
                    <i class="fas fa-trash"></i>
                </div>
                <div class="stat-value"><?php echo $stats['delete']; ?></div>
                <div class="stat-label">حذف</div>
            </div>
        </div>
    </div>

    <!-- Activity Log Table -->
    <div class="table-container">
        <h5 class="mb-3">
            <i class="fas fa-list text-primary"></i>
            السجل (آخر 500 عملية)
        </h5>
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>المستخدم</th>
                        <th>العملية</th>
                        <th>الجدول</th>
                        <th>معرف السجل</th>
                        <th>الوصف</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo formatDate($log['created_at'], 'd/m/Y H:i:s'); ?></td>
                        <td>
                            <?php echo $log['username']; ?><br>
                            <small class="text-muted"><?php echo $log['full_name']; ?></small>
                        </td>
                        <td>
                            <?php
                            $actionColors = [
                                'login' => 'info',
                                'logout' => 'secondary',
                                'create' => 'success',
                                'update' => 'warning',
                                'delete' => 'danger'
                            ];
                            $color = $actionColors[$log['action_type']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $color; ?>">
                                <?php echo $log['action_type']; ?>
                            </span>
                        </td>
                        <td><?php echo $log['table_name']; ?></td>
                        <td><?php echo $log['record_id']; ?></td>
                        <td><?php echo substr($log['description'], 0, 50); ?></td>
                        <td><small class="text-muted"><?php echo $log['ip_address']; ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>