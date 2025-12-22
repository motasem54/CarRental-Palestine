<?php
require_once '../config/settings.php';
$page_title = 'اتصل بنا - ' . SITE_NAME;
include 'includes/header.php';
?>

<div class="page-header">
    <div class="container text-center">
        <h1>📞 اتصل بنا</h1>
        <p class="lead" style="color: rgba(255,255,255,0.9);">نحن هنا لخدمتكم على مدار الساعة</p>
    </div>
</div>

<div class="container my-5">
    <div class="row g-5">
        <div class="col-lg-5">
            <h3 class="mb-4">معلومات الاتصال</h3>
            
            <div class="p-4 bg-white rounded shadow mb-3">
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-phone fa-2x text-primary me-3"></i>
                    <div>
                        <h5 class="mb-0">الهاتف</h5>
                        <p class="mb-0"><?php echo COMPANY_PHONE; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="p-4 bg-white rounded shadow mb-3">
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-envelope fa-2x text-primary me-3"></i>
                    <div>
                        <h5 class="mb-0">البريد الإلكتروني</h5>
                        <p class="mb-0"><?php echo COMPANY_EMAIL; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="p-4 bg-white rounded shadow mb-3">
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-map-marker-alt fa-2x text-primary me-3"></i>
                    <div>
                        <h5 class="mb-0">العنوان</h5>
                        <p class="mb-0">فلسطين 🇵🇸</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-7">
            <div class="p-5 bg-white rounded shadow">
                <h3 class="mb-4">أرسل رسالة</h3>
                <form>
                    <div class="mb-3">
                        <label class="form-label">الاسم</label>
                        <input type="text" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">رقم الهاتف</label>
                        <input type="tel" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الرسالة</label>
                        <textarea class="form-control" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-paper-plane me-2"></i>إرسال
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>