<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';
require_once '../core/Rental.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();
$rentalId = (int)$_GET['id'] ?? 0;

if (!$rentalId) {
    redirect('rentals.php');
}

// Get rental
$stmt = $db->prepare("SELECT * FROM rentals WHERE id = ?");
$stmt->execute([$rentalId]);
$rental = $stmt->fetch();

if (!$rental) {
    redirect('rentals.php');
}

// Get customer
$stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$rental['customer_id']]);
$customer = $stmt->fetch();

// Get car
$stmt = $db->prepare("SELECT * FROM cars WHERE id = ?");
$stmt->execute([$rental['car_id']]);
$car = $stmt->fetch();

// Get photos
$stmt = $db->prepare("SELECT * FROM rental_photos WHERE rental_id = ? AND is_deleted = FALSE ORDER BY uploaded_at DESC");
$stmt->execute([$rentalId]);
$photos = $stmt->fetchAll();

// Get attachments
$stmt = $db->prepare("SELECT * FROM rental_attachments WHERE rental_id = ? AND status = 'active' ORDER BY uploaded_at DESC");
$stmt->execute([$rentalId]);
$attachments = $stmt->fetchAll();

// Get contract
$stmt = $db->prepare("SELECT * FROM rental_contracts WHERE rental_id = ?");
$stmt->execute([$rentalId]);
$contract = $stmt->fetch();

$page_title = 'عرض الحجز - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<style>
.rental-view-container {
    max-width: 1200px;
    margin: 0 auto;
}

.rental-header {
    background: linear-gradient(135deg, #FF5722, #E64A19);
    color: white;
    padding: 30px;
    border-radius: 10px;
    margin-bottom: 30px;
}

.rental-header h2 {
    margin: 0 0 15px 0;
    font-size: 1.8rem;
}

.rental-status {
    display: inline-block;
    padding: 8px 15px;
    border-radius: 20px;
    background: rgba(255,255,255,0.2);
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 15px;
}

.rental-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.detail-item {
    background: rgba(255,255,255,0.1);
    padding: 15px;
    border-radius: 8px;
}

.detail-label {
    font-size: 0.85rem;
    opacity: 0.9;
    margin-bottom: 5px;
    display: block;
}

.detail-value {
    font-size: 1.1rem;
    font-weight: 700;
}

.tabs-navigation {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    border-bottom: 2px solid #f0f0f0;
    flex-wrap: wrap;
}

.tab-btn {
    padding: 12px 25px;
    border: none;
    background: none;
    cursor: pointer;
    font-weight: 600;
    color: #999;
    border-bottom: 3px solid transparent;
    transition: all 0.3s;
    font-size: 1rem;
}

.tab-btn.active {
    color: #FF5722;
    border-bottom-color: #FF5722;
}

.tab-btn:hover {
    color: #FF5722;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Photos Section */
.photos-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.photo-card {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    background: #f0f0f0;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.photo-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.photo-card img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    display: block;
}

.photo-info {
    padding: 12px;
    background: white;
}

.photo-type {
    display: inline-block;
    padding: 4px 10px;
    background: #FF5722;
    color: white;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 5px;
}

.photo-date {
    font-size: 0.85rem;
    color: #999;
}

.photo-description {
    font-size: 0.9rem;
    color: #333;
    margin-bottom: 5px;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #999;
}

.empty-state-icon {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.5;
}

/* Attachments Section */
.attachments-list {
    background: white;
    border-radius: 10px;
    overflow: hidden;
}

.attachment-item {
    display: flex;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #f0f0f0;
    transition: all 0.3s;
}

.attachment-item:last-child {
    border-bottom: none;
}

.attachment-item:hover {
    background: #f8f9fa;
}

.attachment-icon {
    font-size: 2rem;
    margin-right: 20px;
    color: #FF5722;
}

.attachment-details {
    flex: 1;
}

.attachment-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
}

.attachment-meta {
    font-size: 0.85rem;
    color: #999;
}

.attachment-size {
    background: #f0f0f0;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.85rem;
    color: #666;
    margin-right: 10px;
}

.attachment-actions {
    display: flex;
    gap: 10px;
}

.btn-small {
    padding: 8px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-download {
    background: #4CAF50;
    color: white;
}

.btn-download:hover {
    background: #45a049;
}

.btn-delete {
    background: #f44336;
    color: white;
}

.btn-delete:hover {
    background: #da190b;
}

/* Contract Section */
.contract-info {
    background: white;
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 20px;
}

.contract-status-badge {
    display: inline-block;
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 15px;
}

.status-draft {
    background: #f3e5f5;
    color: #6a1b9a;
}

.status-generated {
    background: #e3f2fd;
    color: #1565c0;
}

.status-printed {
    background: #f1f8e9;
    color: #558b2f;
}

.status-signed {
    background: #e0f2f1;
    color: #004d40;
}

.contract-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.contract-detail-item {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.contract-detail-label {
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 5px;
    display: block;
}

.contract-detail-value {
    font-weight: 700;
    color: #333;
}

.action-buttons {
    display: flex;
    gap: 10px;
    margin-top: 30px;
    flex-wrap: wrap;
}

.btn-primary {
    background: linear-gradient(135deg, #FF5722, #E64A19);
    color: white;
    padding: 12px 25px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 87, 34, 0.3);
}

.btn-secondary {
    background: #f0f0f0;
    color: #333;
    padding: 12px 25px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
}

.btn-secondary:hover {
    background: #e0e0e0;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.7);
    animation: fadeIn 0.3s;
}

.modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background-color: #fefefe;
    padding: 0;
    border-radius: 10px;
    max-width: 90vw;
    max-height: 90vh;
    overflow: auto;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.modal-header {
    background: linear-gradient(135deg, #FF5722, #E64A19);
    color: white;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 10px 10px 0 0;
}

.modal-header h3 {
    margin: 0;
}

.close-modal {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    font-size: 1.5rem;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s;
}

.close-modal:hover {
    background: rgba(255,255,255,0.3);
    transform: rotate(90deg);
}

.modal-body {
    padding: 20px;
}

.modal-body img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
}

@media (max-width: 768px) {
    .rental-details {
        grid-template-columns: 1fr;
    }
    
    .photos-gallery {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
    
    .tabs-navigation {
        overflow-x: auto;
    }
    
    .tab-btn {
        white-space: nowrap;
    }
}
</style>

<div class="main-content">
    <div class="rental-view-container">
        <!-- Header -->
        <div class="rental-header">
            <div class="rental-status">
                <i class="fas fa-check-circle"></i>
                <?php 
                    $statusText = match($rental['status']) {
                        'pending' => 'على الانتظار',
                        'active' => 'ماثلة',
                        'completed' => 'منهية',
                        'cancelled' => 'ملغاه',
                        default => $rental['status']
                    };
                    echo $statusText;
                ?>
            </div>
            <h2><i class="fas fa-car-side"></i> ملخص الحجز</h2>
            <div class="rental-details">
                <div class="detail-item">
                    <span class="detail-label">رقم الحجز</span>
                    <span class="detail-value">#<?php echo $rental['rental_number']; ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">العميل</span>
                    <span class="detail-value"><?php echo $customer['full_name']; ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">السيارة</span>
                    <span class="detail-value"><?php echo $car['brand'] . ' ' . $car['model']; ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">الإجمالي</span>
                    <span class="detail-value" style="color: #FFD54F;"><?php echo $rental['total_amount']; ?>₪</span>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs-navigation">
            <button class="tab-btn active" onclick="showTab('photos')">
                <i class="fas fa-camera"></i> الصور (<?php echo count($photos); ?>)
            </button>
            <button class="tab-btn" onclick="showTab('attachments')">
                <i class="fas fa-paperclip"></i> المرفقات (<?php echo count($attachments); ?>)
            </button>
            <button class="tab-btn" onclick="showTab('contract')">
                <i class="fas fa-file-contract"></i> العقد
            </button>
            <button class="tab-btn" onclick="showTab('details')">
                <i class="fas fa-info-circle"></i> التفاصيل
            </button>
        </div>

        <!-- Photos Tab -->
        <div id="photos" class="tab-content active">
            <?php if (count($photos) > 0): ?>
                <div class="photos-gallery">
                    <?php foreach ($photos as $photo): ?>
                        <div class="photo-card" onclick="openPhotoModal('<?php echo $photo['photo_path']; ?>', '<?php echo $photo['photo_type']; ?>')">
                            <img src="<?php echo $photo['photo_path']; ?>" alt="صورة" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3Crect fill=%22%23ddd%22 width=%22200%22 height=%22200%22/%3E%3C/svg%3E'">
                            <div class="photo-info">
                                <span class="photo-type">
                                    <?php 
                                        $typeText = match($photo['photo_type']) {
                                            'exterior' => 'خارجي',
                                            'interior' => 'داخلي',
                                            'damage' => 'أضرار',
                                            'odometer' => 'عداد',
                                            'fuel' => 'بنزين',
                                            default => 'other'
                                        };
                                        echo $typeText;
                                    ?>
                                </span>
                                <?php if ($photo['description']): ?>
                                    <p class="photo-description"><?php echo $photo['description']; ?></p>
                                <?php endif; ?>
                                <div class="photo-date">
                                    <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($photo['uploaded_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📷</div>
                    <h4>لا توجد صور</h4>
                    <p>لم يتم الجب أي صور لهذا الحجز بعد.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Attachments Tab -->
        <div id="attachments" class="tab-content">
            <?php if (count($attachments) > 0): ?>
                <div class="attachments-list">
                    <?php foreach ($attachments as $attachment): ?>
                        <div class="attachment-item">
                            <div class="attachment-icon">
                                <?php 
                                    if (strpos($attachment['mime_type'], 'image') !== false) {
                                        echo '📸';
                                    } elseif (strpos($attachment['mime_type'], 'pdf') !== false) {
                                        echo '📔';
                                    } else {
                                        echo '📄';
                                    }
                                ?>
                            </div>
                            <div class="attachment-details">
                                <div class="attachment-name"><?php echo $attachment['title'] ?? $attachment['file_name']; ?></div>
                                <div class="attachment-meta">
                                    <span><?php echo date('d/m/Y', strtotime($attachment['uploaded_at'])); ?></span>
                                    <span class="attachment-size">
                                        <?php echo formatFileSize($attachment['file_size']); ?>
                                    </span>
                                </div>
                                <?php if ($attachment['description']): ?>
                                    <small style="color: #999;"><?php echo $attachment['description']; ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="attachment-actions">
                                <a href="<?php echo $attachment['file_path']; ?>" download class="btn-small btn-download">
                                    <i class="fas fa-download"></i> تحميل
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📊</div>
                    <h4>لا توجد مرفقات</h4>
                    <p>لم يتم رفع أي ملفات لهذا الحجز بعد.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Contract Tab -->
        <div id="contract" class="tab-content">
            <?php if ($contract): ?>
                <div class="contract-info">
                    <span class="contract-status-badge status-<?php echo $contract['status']; ?>">
                        <?php 
                            $statusText = match($contract['status']) {
                                'draft' => 'مسودة',
                                'generated' => 'مولدة',
                                'printed' => 'مطبوعة',
                                'signed' => 'موقعة',
                                'completed' => 'منتهية',
                                default => $contract['status']
                            };
                            echo $statusText;
                        ?>
                    </span>
                    
                    <div class="contract-details">
                        <div class="contract-detail-item">
                            <span class="contract-detail-label">نوع العقد</span>
                            <span class="contract-detail-value">
                                <?php echo $contract['contract_type'] === 'simple' ? 'بسيط' : 'مع كمبيالة'; ?>
                            </span>
                        </div>
                        <div class="contract-detail-item">
                            <span class="contract-detail-label">تاريخ الإنشاء</span>
                            <span class="contract-detail-value"><?php echo date('d/m/Y H:i', strtotime($contract['created_at'])); ?></span>
                        </div>
                        <?php if ($contract['pdf_path']): ?>
                            <div class="contract-detail-item">
                                <span class="contract-detail-label">تاريخ الطباعة</span>
                                <span class="contract-detail-value"><?php echo date('d/m/Y H:i', strtotime($contract['generated_at'] ?? 'now')); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="contract_print.php?id=<?php echo $rentalId; ?>" class="btn-primary">
                            <i class="fas fa-file-pdf"></i> عرض العقد
                        </a>
                        <?php if ($contract['pdf_path']): ?>
                            <a href="<?php echo $contract['pdf_path']; ?>" download class="btn-primary">
                                <i class="fas fa-download"></i> تحميل PDF
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <h4>لم يتم إنشاء عقد بعد</h4>
                    <p>يرجى ابدأ عملية التأجير لإنشاء عقد.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Details Tab -->
        <div id="details" class="tab-content">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <!-- Customer Details -->
                <div class="card" style="padding: 20px; border-radius: 10px;">
                    <h5 style="color: #FF5722; margin-top: 0;">👤 بيانات العميل</h5>
                    <div style="line-height: 2;">
                        <p><strong>الاسم:</strong> <?php echo $customer['full_name']; ?></p>
                        <p><strong>هاتف:</strong> <?php echo $customer['phone']; ?></p>
                        <p><strong>رقم الهوية:</strong> <?php echo $customer['id_number']; ?></p>
                        <?php if ($customer['driver_license']): ?>
                            <p><strong>رقم الرخصة:</strong> <?php echo $customer['driver_license']; ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Car Details -->
                <div class="card" style="padding: 20px; border-radius: 10px;">
                    <h5 style="color: #FF5722; margin-top: 0;">🚗 بيانات السيارة</h5>
                    <div style="line-height: 2;">
                        <p><strong>الماركة:</strong> <?php echo $car['brand']; ?></p>
                        <p><strong>الموديل:</strong> <?php echo $car['model']; ?></p>
                        <p><strong>اللوحة:</strong> <?php echo $car['plate_number']; ?></p>
                        <p><strong>اللون:</strong> <?php echo $car['color']; ?></p>
                        <p><strong>عداد الاستلام:</strong> <?php echo $rental['mileage_current'] ?? 0; ?> كم</p>
                    </div>
                </div>

                <!-- Rental Dates -->
                <div class="card" style="padding: 20px; border-radius: 10px;">
                    <h5 style="color: #FF5722; margin-top: 0;">📅 مواعيد التاجير</h5>
                    <div style="line-height: 2;">
                        <p><strong>الاستلام:</strong> <?php echo date('d/m/Y H:i', strtotime($rental['start_date'])); ?></p>
                        <p><strong>التسليم:</strong> <?php echo date('d/m/Y H:i', strtotime($rental['end_date'])); ?></p>
                        <p><strong>عدد الأيام:</strong> <?php echo $rental['total_days']; ?></p>
                        <p><strong>الإجمالي:</strong> <span style="color: #FF5722; font-weight: 700;"><?php echo $rental['total_amount']; ?>₪</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Photo Modal -->
<div id="photoModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3 id="modalPhotoTitle">الصورة</h3>
            <button class="close-modal" onclick="closePhotoModal()">&times;</button>
        </div>
        <div class="modal-body">
            <img id="modalPhotoImage" src="" alt="صورة" style="width: 100%;">
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById(tabName).classList.add('active');
    
    // Add active class to clicked button
    event.target.closest('.tab-btn').classList.add('active');
}

function openPhotoModal(photoPath, photoType) {
    document.getElementById('photoModal').classList.add('active');
    document.getElementById('modalPhotoImage').src = photoPath;
    document.getElementById('modalPhotoTitle').textContent = photoType;
}

function closePhotoModal() {
    document.getElementById('photoModal').classList.remove('active');
}

document.getElementById('photoModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePhotoModal();
    }
});

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}
</script>

<?php include 'includes/footer.php'; ?>

<?php
function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i) * 100) / 100 . ' ' . $sizes[$i];
}
?>