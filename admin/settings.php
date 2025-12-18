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

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($_POST as $key => $value) {
            if ($key !== 'submit') {
                $stmt = $db->prepare("UPDATE settings SET value = ? WHERE `key` = ?");
                $stmt->execute([sanitizeInput($value), $key]);
            }
        }
        $success = 'تم حفظ الإعدادات بنجاح';
    } catch (Exception $e) {
        $error = 'خطأ في حفظ الإعدادات';
    }
}

// Get all settings
$stmt = $db->query("SELECT * FROM settings ORDER BY category, `key`");
$allSettings = $stmt->fetchAll();

// Group by category
$settings = [];
foreach ($allSettings as $setting) {
    $settings[$setting['category']][] = $setting;
}

$page_title = 'إعدادات النظام - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-cog me-2"></i>إعدادات النظام</h5>
            <p>تخصيص وتحكم في إعدادات المنصة</p>
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

    <form method="POST">
        <?php foreach ($settings as $category => $categorySettings): ?>
        <div class="table-container mb-4">
            <h5 class="mb-3">
                <i class="fas fa-folder text-primary"></i>
                <?php echo ucfirst($category); ?>
            </h5>
            <div class="row g-3">
                <?php foreach ($categorySettings as $setting): ?>
                <div class="col-md-6">
                    <label class="form-label"><?php echo $setting['label']; ?></label>
                    <?php if ($setting['type'] === 'text' || $setting['type'] === 'email'): ?>
                        <input type="<?php echo $setting['type']; ?>" 
                               name="<?php echo $setting['key']; ?>" 
                               class="form-control" 
                               value="<?php echo $setting['value']; ?>">
                    <?php elseif ($setting['type'] === 'textarea'): ?>
                        <textarea name="<?php echo $setting['key']; ?>" class="form-control" rows="3"><?php echo $setting['value']; ?></textarea>
                    <?php elseif ($setting['type'] === 'number'): ?>
                        <input type="number" 
                               name="<?php echo $setting['key']; ?>" 
                               class="form-control" 
                               value="<?php echo $setting['value']; ?>" 
                               step="0.01">
                    <?php elseif ($setting['type'] === 'boolean'): ?>
                        <select name="<?php echo $setting['key']; ?>" class="form-control">
                            <option value="1" <?php echo $setting['value'] == '1' ? 'selected' : ''; ?>>نعم</option>
                            <option value="0" <?php echo $setting['value'] == '0' ? 'selected' : ''; ?>>لا</option>
                        </select>
                    <?php endif; ?>
                    <?php if ($setting['description']): ?>
                        <small class="text-muted"><?php echo $setting['description']; ?></small>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="text-center mt-4">
            <button type="submit" name="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save me-2"></i>حفظ جميع الإعدادات
            </button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>