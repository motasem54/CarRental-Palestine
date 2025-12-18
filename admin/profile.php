<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();
$success = '';
$error = '';

// Get current user
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $fullName = sanitizeInput($_POST['full_name']);
        $email = sanitizeInput($_POST['email']);
        
        $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
        if ($stmt->execute([$fullName, $email, $_SESSION['user_id']])) {
            $success = 'تم تحديث الملف الشخصي بنجاح';
        }
    }
    
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if (password_verify($currentPassword, $user['password'])) {
            if ($newPassword === $confirmPassword) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($stmt->execute([$hashedPassword, $_SESSION['user_id']])) {
                    $success = 'تم تغيير كلمة المرور بنجاح';
                }
            } else {
                $error = 'كلمات المرور غير متطابقة';
            }
        } else {
            $error = 'كلمة المرور الحالية غير صحيحة';
        }
    }
}

$page_title = 'الملف الشخصي - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-user-circle me-2"></i>الملف الشخصي</h5>
            <p>إدارة حسابك الشخصي</p>
        </div>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Profile Info -->
        <div class="col-md-6">
            <div class="table-container">
                <h5 class="mb-3"><i class="fas fa-user text-primary"></i> المعلومات الشخصية</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">اسم المستخدم</label>
                        <input type="text" class="form-control" value="<?php echo $user['username']; ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الاسم الكامل</label>
                        <input type="text" name="full_name" class="form-control" value="<?php echo $user['full_name']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الدور</label>
                        <input type="text" class="form-control" value="<?php echo USER_ROLES[$user['role']]; ?>" disabled>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>حفظ التغييرات
                    </button>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="col-md-6">
            <div class="table-container">
                <h5 class="mb-3"><i class="fas fa-lock text-warning"></i> تغيير كلمة المرور</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">كلمة المرور الحالية</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">كلمة المرور الجديدة</label>
                        <input type="password" name="new_password" class="form-control" minlength="6" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تأكيد كلمة المرور</label>
                        <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-warning">
                        <i class="fas fa-key me-2"></i>تغيير كلمة المرور
                    </button>
                </form>
            </div>
        </div>

        <!-- Activity Info -->
        <div class="col-md-12">
            <div class="table-container">
                <h5 class="mb-3"><i class="fas fa-history text-info"></i> معلومات الحساب</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="alert alert-info">
                            <strong>تاريخ الإنشاء:</strong><br>
                            <?php echo formatDate($user['created_at'], 'd/m/Y H:i'); ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-success">
                            <strong>آخر دخول:</strong><br>
                            <?php echo $user['last_login'] ? formatDate($user['last_login'], 'd/m/Y H:i') : 'لم يسجل دخول'; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-warning">
                            <strong>الحالة:</strong><br>
                            <?php echo $user['is_active'] ? 'نشط ✓' : 'معطل ✗'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>