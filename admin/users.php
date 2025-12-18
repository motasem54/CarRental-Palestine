<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();

// Get all users
$stmt = $db->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

$page_title = 'المستخدمين - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-users-cog me-2"></i>إدارة المستخدمين</h5>
            <p>إدارة مستخدمي النظام والصلاحيات</p>
        </div>
        <div class="top-bar-right">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-user-plus me-2"></i>إضافة مستخدم
            </button>
        </div>
    </div>

    <div class="table-container">
        <h5 class="mb-3">
            <i class="fas fa-list text-primary"></i>
            قائمة المستخدمين
        </h5>
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>اسم المستخدم</th>
                        <th>الاسم الكامل</th>
                        <th>البريد</th>
                        <th>الدور</th>
                        <th>الحالة</th>
                        <th>آخر دخول</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $index => $user): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><strong><?php echo $user['username']; ?></strong></td>
                        <td><?php echo $user['full_name']; ?></td>
                        <td><?php echo $user['email']; ?></td>
                        <td>
                            <?php
                            $roleColors = [
                                'admin' => 'danger',
                                'manager' => 'warning',
                                'employee' => 'info'
                            ];
                            $color = $roleColors[$user['role']];
                            ?>
                            <span class="badge bg-<?php echo $color; ?>">
                                <?php echo USER_ROLES[$user['role']]; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($user['is_active']): ?>
                                <span class="badge bg-success">نشط</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">معطل</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $user['last_login'] ? formatDate($user['last_login'], 'd/m/Y H:i') : 'لم يسجل دخول'; ?></td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-primary" title="تعديل">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <button class="btn btn-sm btn-danger" title="حذف">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>