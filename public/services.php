<?php
require_once '../config/settings.php';
$page_title = 'خدماتنا - ' . SITE_NAME;
include 'includes/header.php';
?>

<div class="page-header">
    <div class="container text-center">
        <h1>✨ خدماتنا</h1>
        <p class="lead" style="color: rgba(255,255,255,0.9);">نقدم لكم مجموعة متكاملة من الخدمات</p>
    </div>
</div>

<div class="container my-5">
    <div class="row g-4">
        <div class="col-lg-4 col-md-6">
            <div class="p-5 bg-white rounded shadow text-center h-100">
                <i class="fas fa-car fa-4x text-primary mb-4"></i>
                <h4 class="mb-3">تأجير يومي</h4>
                <p>استأجر سيارتك ليوم واحد أو أكثر بأسعار منافسة</p>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
            <div class="p-5 bg-white rounded shadow text-center h-100">
                <i class="fas fa-calendar-week fa-4x text-primary mb-4"></i>
                <h4 class="mb-3">تأجير أسبوعي</h4>
                <p>خصومات خاصة للتأجير لأسبوع أو أكثر</p>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
            <div class="p-5 bg-white rounded shadow text-center h-100">
                <i class="fas fa-calendar-alt fa-4x text-primary mb-4"></i>
                <h4 class="mb-3">تأجير شهري</h4>
                <p>أفضل عرض للتأجير لفترات طويلة</p>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
            <div class="p-5 bg-white rounded shadow text-center h-100">
                <i class="fas fa-shield-alt fa-4x text-primary mb-4"></i>
                <h4 class="mb-3">تأمين شامل</h4>
                <p>جميع سياراتنا مؤمّنة بشكل كامل</p>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
            <div class="p-5 bg-white rounded shadow text-center h-100">
                <i class="fas fa-headset fa-4x text-primary mb-4"></i>
                <h4 class="mb-3">دعم 24/7</h4>
                <p>خدمة عملاء على مدار الساعة</p>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
            <div class="p-5 bg-white rounded shadow text-center h-100">
                <i class="fas fa-wrench fa-4x text-primary mb-4"></i>
                <h4 class="mb-3">صيانة مجانية</h4>
                <p>صيانة دورية لضمان أفضل أداء</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>