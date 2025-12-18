<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'auto_backup' => isset($_POST['auto_backup']) ? 1 : 0,
        'backup_frequency' => $_POST['backup_frequency'] ?? 'daily',
        'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
        'sms_notifications' => isset($_POST['sms_notifications']) ? 1 : 0,
        'reminder_days' => (int)$_POST['reminder_days'] ?? 1,
        'overdue_penalty' => (float)$_POST['overdue_penalty'] ?? 50,
        'loyalty_points_rate' => (float)$_POST['loyalty_points_rate'] ?? 1,
        'maintenance_alerts' => isset($_POST['maintenance_alerts']) ? 1 : 0,
        'low_fuel_alert' => isset($_POST['low_fuel_alert']) ? 1 : 0
    ];
    
    foreach ($settings as $key => $value) {
        $stmt = $db->prepare("
            INSERT INTO system_settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?
        ");
        $stmt->execute([$key, $value, $value]);
    }
    
    $_SESSION['success'] = 'تم حفظ الإعدادات بنجاح';
    redirect(ADMIN_URL . '/system_settings.php');
}

// Get current settings
$stmt = $db->query("SELECT * FROM system_settings");
$settingsData = $stmt->fetchAll();
$settings = [];
foreach ($settingsData as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$page_title = 'إعدادات النظام - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-cog me-2"></i>إعدادات النظام المتقدمة</h5>
            <p>التحكم الكامل في إعدادات النظام</p>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <form method="POST">
        <div class="row g-4">
            <!-- Backup Settings -->
            <div class="col-md-6">
                <div class="table-container">
                    <h5 class="mb-3">
                        <i class="fas fa-database text-primary"></i>
                        إعدادات النسخ الاحتياطي
                    </h5>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="auto_backup" 
                                   id="autoBackup" <?php echo ($settings['auto_backup'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="autoBackup">
                                تفعيل النسخ التلقائي
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تكرار النسخ</label>
                        <select name="backup_frequency" class="form-control">
                            <option value="daily" <?php echo ($settings['backup_frequency'] ?? '') == 'daily' ? 'selected' : ''; ?>>يوميًا</option>
                            <option value="weekly" <?php echo ($settings['backup_frequency'] ?? '') == 'weekly' ? 'selected' : ''; ?>>أسبوعيًا</option>
                            <option value="monthly" <?php echo ($settings['backup_frequency'] ?? '') == 'monthly' ? 'selected' : ''; ?>>شهريًا</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Notification Settings -->
            <div class="col-md-6">
                <div class="table-container">
                    <h5 class="mb-3">
                        <i class="fas fa-bell text-warning"></i>
                        إعدادات الإشعارات
                    </h5>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="email_notifications" 
                                   id="emailNotif" <?php echo ($settings['email_notifications'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="emailNotif">
                                إشعارات البريد الإلكتروني
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="sms_notifications" 
                                   id="smsNotif" <?php echo ($settings['sms_notifications'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="smsNotif">
                                إشعارات SMS
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">عدد أيام التذكير قبل الانتهاء</label>
                        <input type="number" name="reminder_days" class="form-control" 
                               value="<?php echo $settings['reminder_days'] ?? 1; ?>" min="1" max="7">
                    </div>
                </div>
            </div>

            <!-- Rental Settings -->
            <div class="col-md-6">
                <div class="table-container">
                    <h5 class="mb-3">
                        <i class="fas fa-car text-success"></i>
                        إعدادات الحجوزات
                    </h5>
                    <div class="mb-3">
                        <label class="form-label">غرامة التأخير اليومية (₪)</label>
                        <input type="number" name="overdue_penalty" class="form-control" 
                               value="<?php echo $settings['overdue_penalty'] ?? 50; ?>" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">معدل تحويل نقاط الولاء (% من المبلغ)</label>
                        <input type="number" name="loyalty_points_rate" class="form-control" 
                               value="<?php echo $settings['loyalty_points_rate'] ?? 1; ?>" step="0.1">
                        <small class="text-muted">مثلاً: 1% يعني كل 100₪ = 1 نقطة</small>
                    </div>
                </div>
            </div>

            <!-- Maintenance Settings -->
            <div class="col-md-6">
                <div class="table-container">
                    <h5 class="mb-3">
                        <i class="fas fa-tools text-danger"></i>
                        إعدادات الصيانة
                    </h5>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="maintenance_alerts" 
                                   id="maintAlert" <?php echo ($settings['maintenance_alerts'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="maintAlert">
                                تنبيهات مواعيد الصيانة
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="low_fuel_alert" 
                                   id="fuelAlert" <?php echo ($settings['low_fuel_alert'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="fuelAlert">
                                تنبيه عند انخفاض الوقود
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save me-2"></i>حفظ جميع الإعدادات
            </button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>