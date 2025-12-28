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

// Load current settings
$stmt = $db->query("SELECT * FROM system_settings WHERE setting_key IN ('site_primary_color', 'site_secondary_color', 'site_logo_text', 'site_logo_icon')");
$settings_data = $stmt->fetchAll();
$settings = [];
foreach ($settings_data as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Default values
$primary_color = $settings['site_primary_color'] ?? '#FF5722';
$secondary_color = $settings['site_secondary_color'] ?? '#E64A19';
$logo_text = $settings['site_logo_text'] ?? SITE_NAME;
$logo_icon = $settings['site_logo_icon'] ?? 'fa-car';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_primary = $_POST['primary_color'] ?? '#FF5722';
    $new_secondary = $_POST['secondary_color'] ?? '#E64A19';
    $new_logo_text = $_POST['logo_text'] ?? SITE_NAME;
    $new_logo_icon = $_POST['logo_icon'] ?? 'fa-car';
    
    try {
        $db->beginTransaction();
        
        // Update or insert settings
        $settings_to_update = [
            'site_primary_color' => $new_primary,
            'site_secondary_color' => $new_secondary,
            'site_logo_text' => $new_logo_text,
            'site_logo_icon' => $new_logo_icon
        ];
        
        foreach ($settings_to_update as $key => $value) {
            $stmt = $db->prepare("
                INSERT INTO system_settings (setting_key, setting_value, updated_at) 
                VALUES (?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
            ");
            $stmt->execute([$key, $value, $value]);
        }
        
        $db->commit();
        $success = 'تم حفظ الإعدادات بنجاح!';
        
        // Reload values
        $primary_color = $new_primary;
        $secondary_color = $new_secondary;
        $logo_text = $new_logo_text;
        $logo_icon = $new_logo_icon;
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = 'حدث خطأ: ' . $e->getMessage();
    }
}

$page_title = 'مظهر النظام والألوان';
include 'includes/header.php';
?>

<style>
:root {
    --primary: <?php echo $primary_color; ?>;
    --primary-dark: <?php echo $secondary_color; ?>;
}

.color-preview {
    width: 100%;
    height: 60px;
    border-radius: 10px;
    border: 3px solid #dee2e6;
    margin-top: 10px;
}

.icon-selector {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 10px;
    max-height: 300px;
    overflow-y: auto;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
}

.icon-option {
    padding: 20px;
    text-align: center;
    background: white;
    border: 2px solid #dee2e6;
    border-radius: 10px;
    cursor: pointer;
    transition: 0.3s;
}

.icon-option:hover,
.icon-option.active {
    border-color: var(--primary);
    background: var(--primary);
    color: white;
    transform: scale(1.1);
}

.icon-option i {
    font-size: 2rem;
}

.preview-card {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.preview-logo {
    font-size: 2rem;
    font-weight: 900;
    margin-bottom: 20px;
}

.preview-button {
    background: var(--primary);
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 50px;
    font-weight: 700;
}
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-palette me-2"></i>مظهر النظام والألوان
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-times-circle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row">
                            <!-- Left Side - Settings -->
                            <div class="col-lg-6">
                                <h5 class="mb-4">إعدادات الألوان</h5>
                                
                                <!-- Primary Color -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">اللون الأساسي</label>
                                    <input type="color" class="form-control" name="primary_color" 
                                           id="primaryColor" value="<?php echo $primary_color; ?>">
                                    <div class="color-preview" id="primaryPreview" 
                                         style="background: <?php echo $primary_color; ?>;"></div>
                                    <small class="text-muted">يستخدم للأزرار والعناوين والروابط</small>
                                </div>
                                
                                <!-- Secondary Color -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">اللون الثانوي</label>
                                    <input type="color" class="form-control" name="secondary_color" 
                                           id="secondaryColor" value="<?php echo $secondary_color; ?>">
                                    <div class="color-preview" id="secondaryPreview" 
                                         style="background: <?php echo $secondary_color; ?>;"></div>
                                    <small class="text-muted">يستخدم للتدرجات والتأثيرات</small>
                                </div>
                                
                                <hr class="my-4">
                                
                                <h5 class="mb-4">إعدادات الشعار</h5>
                                
                                <!-- Logo Text -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">نص الشعار</label>
                                    <input type="text" class="form-control" name="logo_text" 
                                           id="logoText" value="<?php echo htmlspecialchars($logo_text); ?>">
                                </div>
                                
                                <!-- Logo Icon -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">أيقونة الشعار</label>
                                    <input type="hidden" name="logo_icon" id="logoIconInput" value="<?php echo $logo_icon; ?>">
                                    <div class="icon-selector">
                                        <?php
                                        $icons = [
                                            'fa-car', 'fa-car-side', 'fa-taxi', 'fa-shuttle-van',
                                            'fa-truck', 'fa-bus', 'fa-steering-wheel', 'fa-road',
                                            'fa-key', 'fa-car-alt', 'fa-车', 'fa-shield-alt'
                                        ];
                                        foreach ($icons as $icon):
                                        ?>
                                        <div class="icon-option <?php echo $icon === $logo_icon ? 'active' : ''; ?>" 
                                             data-icon="<?php echo $icon; ?>">
                                            <i class="fas <?php echo $icon; ?>"></i>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <!-- Preset Colors -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">ألوان جاهزة</label>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="button" class="btn btn-sm" style="background: #FF5722; color: white;" 
                                                onclick="setColors('#FF5722', '#E64A19')">برتقالي</button>
                                        <button type="button" class="btn btn-sm" style="background: #2196F3; color: white;" 
                                                onclick="setColors('#2196F3', '#1976D2')">أزرق</button>
                                        <button type="button" class="btn btn-sm" style="background: #4CAF50; color: white;" 
                                                onclick="setColors('#4CAF50', '#388E3C')">أخضر</button>
                                        <button type="button" class="btn btn-sm" style="background: #9C27B0; color: white;" 
                                                onclick="setColors('#9C27B0', '#7B1FA2')">بنفسجي</button>
                                        <button type="button" class="btn btn-sm" style="background: #F44336; color: white;" 
                                                onclick="setColors('#F44336', '#D32F2F')">أحمر</button>
                                        <button type="button" class="btn btn-sm" style="background: #FF9800; color: white;" 
                                                onclick="setColors('#FF9800', '#F57C00')">ذهبي</button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right Side - Preview -->
                            <div class="col-lg-6">
                                <h5 class="mb-4">معاينة مباشرة</h5>
                                <div class="preview-card">
                                    <div class="preview-logo" id="previewLogo">
                                        <i class="fas <?php echo $logo_icon; ?>" id="previewIcon"></i>
                                        <span id="previewText"><?php echo htmlspecialchars($logo_text); ?></span>
                                    </div>
                                    
                                    <p class="mb-3">هذا مثال على شكل الأزرار والعناصر بالألوان الجديدة</p>
                                    
                                    <div class="d-flex gap-2 flex-wrap mb-3">
                                        <button type="button" class="preview-button">زر عادي</button>
                                        <button type="button" class="btn" style="background: var(--primary); color: white; border-radius: 5px; padding: 10px 20px;">زر مربع</button>
                                    </div>
                                    
                                    <div class="alert" style="background: var(--primary); color: white; border: none;">
                                        <i class="fas fa-info-circle me-2"></i>مثال على تنبيه بالألوان الجديدة
                                    </div>
                                    
                                    <div class="progress mb-3" style="height: 25px;">
                                        <div class="progress-bar" style="width: 75%; background: var(--primary);" role="progressbar">75%</div>
                                    </div>
                                    
                                    <a href="#" style="color: var(--primary); text-decoration: none; font-weight: bold;">
                                        <i class="fas fa-link me-2"></i>مثال على رابط
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>حفظ الإعدادات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Update color previews
document.getElementById('primaryColor').addEventListener('input', function(e) {
    document.getElementById('primaryPreview').style.background = e.target.value;
    document.documentElement.style.setProperty('--primary', e.target.value);
});

document.getElementById('secondaryColor').addEventListener('input', function(e) {
    document.getElementById('secondaryPreview').style.background = e.target.value;
    document.documentElement.style.setProperty('--primary-dark', e.target.value);
});

// Update logo text preview
document.getElementById('logoText').addEventListener('input', function(e) {
    document.getElementById('previewText').textContent = e.target.value;
});

// Icon selection
document.querySelectorAll('.icon-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.icon-option').forEach(o => o.classList.remove('active'));
        this.classList.add('active');
        const icon = this.dataset.icon;
        document.getElementById('logoIconInput').value = icon;
        document.getElementById('previewIcon').className = 'fas ' + icon;
    });
});

// Preset colors
function setColors(primary, secondary) {
    document.getElementById('primaryColor').value = primary;
    document.getElementById('secondaryColor').value = secondary;
    document.getElementById('primaryPreview').style.background = primary;
    document.getElementById('secondaryPreview').style.background = secondary;
    document.documentElement.style.setProperty('--primary', primary);
    document.documentElement.style.setProperty('--primary-dark', secondary);
}
</script>

<?php include 'includes/footer.php'; ?>