<?php
require_once '../config/settings.php';
$page_title = 'من نحن - ' . SITE_NAME;
include 'includes/header.php';
?>

<div class="page-header">
    <div class="container text-center">
        <h1>🏛️ من نحن</h1>
        <p class="lead" style="color: rgba(255,255,255,0.9);">تعرّف على قصتنا ورؤيتنا</p>
    </div>
</div>

<div class="container my-5">
    <div class="row g-5 align-items-center">
        <div class="col-lg-6">
            <h2 class="fw-bold mb-4">شركة رائدة في تأجير السيارات</h2>
            <p class="lead">نحن في <strong><?php echo SITE_NAME; ?></strong> نفتخر بتقديم أفضل خدمات تأجير السيارات في فلسطين.</p>
            <p>منذ سنوات، كنا نعمل بجد لتوفير أحدث السيارات بأفضل الأسعار مع خدمة عملاء متميزة. نؤمن بأن كل عميل يستحق تجربة سلسة ومريحة.</p>
        </div>
        <div class="col-lg-6">
            <img src="https://via.placeholder.com/600x400/FF5722/FFFFFF?text=Car+Rental" class="img-fluid rounded shadow-lg" alt="About Us">
        </div>
    </div>
    
    <div class="row g-4 mt-5">
        <div class="col-md-4">
            <div class="text-center p-4 bg-white rounded shadow">
                <i class="fas fa-bullseye fa-3x text-primary mb-3"></i>
                <h4>رؤيتنا</h4>
                <p>أن نكون الخيار الأول لتأجير السيارات في فلسطين</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="text-center p-4 bg-white rounded shadow">
                <i class="fas fa-heart fa-3x text-primary mb-3"></i>
                <h4>رسالتنا</h4>
                <p>تقديم خدمة متميزة مع أسعار منافسة</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="text-center p-4 bg-white rounded shadow">
                <i class="fas fa-star fa-3x text-primary mb-3"></i>
                <h4>قيمنا</h4>
                <p>الشفافية، الأمانة، والجودة</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>