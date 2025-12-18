<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

// Check if admin
if ($_SESSION['user_role'] !== 'admin') {
    die('غير مصرح لك بالوصول');
}

$db = Database::getInstance()->getConnection();

// Handle actions
if (isset($_GET['action'])) {
    require_once '../core/BackupManager.php';
    $backup = new BackupManager();
    
    switch ($_GET['action']) {
        case 'create':
            $result = $backup->createBackup();
            if ($result['success']) {
                $_SESSION['success'] = 'تم إنشاء النسخة الاحتياطية بنجاح';
            } else {
                $_SESSION['error'] = $result['message'];
            }
            redirect(ADMIN_URL . '/backup.php');
            break;
            
        case 'download':
            $filename = $_GET['file'] ?? '';
            $backup->downloadBackup($filename);
            exit;
            break;
            
        case 'delete':
            $filename = $_GET['file'] ?? '';
            if ($backup->deleteBackup($filename)) {
                $_SESSION['success'] = 'تم حذف النسخة بنجاح';
            } else {
                $_SESSION['error'] = 'فشل الحذف';
            }
            redirect(ADMIN_URL . '/backup.php');
            break;
            
        case 'restore':
            $filename = $_GET['file'] ?? '';
            $result = $backup->restoreBackup($filename);
            if ($result['success']) {
                $_SESSION['success'] = 'تم استعادة النسخة بنجاح';
            } else {
                $_SESSION['error'] = $result['message'];
            }
            redirect(ADMIN_URL . '/backup.php');
            break;
    }
}

// Get backup directory
$backupDir = '../backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Get all backups
$backups = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir, SCANDIR_SORT_DESCENDING);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $backups[] = [
                'name' => $file,
                'size' => filesize($backupDir . $file),
                'date' => filemtime($backupDir . $file)
            ];
        }
    }
}

$page_title = 'النسخ الاحتياطي - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-database me-2"></i>النسخ الاحتياطي</h5>
            <p>إنشاء واستعادة نسخ قاعدة البيانات</p>
        </div>
        <div class="top-bar-right">
            <a href="?action=create" class="btn btn-success" onclick="return confirm('هل أنت متأكد من إنشاء نسخة جديدة؟')">
                <i class="fas fa-plus me-2"></i>إنشاء نسخة جديدة
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Info Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="table-container text-center p-4">
                <i class="fas fa-database fa-3x text-primary mb-3"></i>
                <h6>عدد النسخ</h6>
                <h3 class="text-primary"><?php echo count($backups); ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="table-container text-center p-4">
                <i class="fas fa-hdd fa-3x text-success mb-3"></i>
                <h6>إجمالي الحجم</h6>
                <h3 class="text-success"><?php echo formatFileSize(array_sum(array_column($backups, 'size'))); ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="table-container text-center p-4">
                <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                <h6>آخر نسخة</h6>
                <h3 class="text-warning">
                    <?php echo !empty($backups) ? formatDate($backups[0]['date'], 'd/m/Y H:i') : '-'; ?>
                </h3>
            </div>
        </div>
    </div>

    <!-- Backups Table -->
    <div class="table-container">
        <h5 class="mb-3">
            <i class="fas fa-list text-primary"></i>
            النسخ المتوفرة
        </h5>
        
        <?php if (empty($backups)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            لا توجد نسخ احتياطية. اضعط على "إنشاء نسخة جديدة" للبدء.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>اسم الملف</th>
                        <th>الحجم</th>
                        <th>التاريخ</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $backup): ?>
                    <tr>
                        <td>
                            <i class="fas fa-file-archive text-primary me-2"></i>
                            <?php echo $backup['name']; ?>
                        </td>
                        <td><?php echo formatFileSize($backup['size']); ?></td>
                        <td><?php echo formatDate($backup['date'], 'd/m/Y H:i:s'); ?></td>
                        <td>
                            <a href="?action=download&file=<?php echo urlencode($backup['name']); ?>" 
                               class="btn btn-sm btn-primary" title="تحميل">
                                <i class="fas fa-download"></i>
                            </a>
                            <a href="?action=restore&file=<?php echo urlencode($backup['name']); ?>" 
                               class="btn btn-sm btn-warning" 
                               onclick="return confirm('هل أنت متأكد من استعادة هذه النسخة؟ سيتم استبدال البيانات الحالية!')" 
                               title="استعادة">
                                <i class="fas fa-undo"></i>
                            </a>
                            <a href="?action=delete&file=<?php echo urlencode($backup['name']); ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('هل أنت متأكد من حذف هذه النسخة؟')" 
                               title="حذف">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Instructions -->
    <div class="table-container mt-4">
        <h5><i class="fas fa-info-circle text-info"></i> إرشادات هامة</h5>
        <ul class="list-unstyled">
            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> يتم حفظ النسخ في مجلد <code>/backups</code></li>
            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> ينصح بإنشاء نسخة يومية</li>
            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> احفظ نسخة خارج السيرفر للأما
</li>
            <li class="mb-2"><i class="fas fa-exclamation-triangle text-warning me-2"></i> الاستعادة تستبدل جميع البيانات الحالية</li>
        </ul>
    </div>
</div>

<?php include 'includes/footer.php'; ?>