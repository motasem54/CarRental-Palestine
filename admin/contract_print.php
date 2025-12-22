<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();
$rental_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($rental_id === 0) {
    die('رقم الحجز غير صحيح');
}

// Get rental details
$stmt = $db->prepare("
    SELECT r.*, 
           c.full_name as customer_name, c.national_id, c.phone, c.email, c.address,
           c.driver_license, c.license_expiry,
           car.brand, car.model, car.year, car.plate_number, car.color, car.type,
           car.transmission, car.fuel_type, car.seats,
           u.full_name as created_by_name
    FROM rentals r
    JOIN customers c ON r.customer_id = c.id
    JOIN cars car ON r.car_id = car.id
    LEFT JOIN users u ON r.created_by = u.id
    WHERE r.id = ?
");
$stmt->execute([$rental_id]);
$rental = $stmt->fetch();

if (!$rental) {
    die('لم يتم العثور على الحجز');
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عقد إيجار - <?php echo $rental['rental_number']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Cairo', sans-serif; }
        body { background: white; }
        .contract-header {
            text-align: center;
            border-bottom: 3px solid #FF5722;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .contract-number {
            background: #FF5722;
            color: white;
            padding: 10px 20px;
            display: inline-block;
            border-radius: 5px;
            font-weight: bold;
        }
        .section-title {
            background: #f5f5f5;
            padding: 10px 15px;
            margin: 20px 0 10px;
            border-right: 4px solid #FF5722;
            font-weight: bold;
        }
        .info-row {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .info-label {
            font-weight: 600;
            color: #666;
        }
        .signature-box {
            border: 2px dashed #ccc;
            height: 80px;
            margin-top: 10px;
            text-align: center;
            line-height: 80px;
            color: #999;
        }
        .terms {
            font-size: 0.9rem;
            line-height: 1.8;
        }
        .terms li { margin-bottom: 8px; }
        @media print {
            .no-print { display: none !important; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Print Button -->
        <div class="no-print mb-3 text-end">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> طباعة العقد
            </button>
            <button onclick="window.close()" class="btn btn-secondary">
                <i class="fas fa-times"></i> إغلاق
            </button>
        </div>

        <!-- Header -->
        <div class="contract-header">
            <h1 style="color: #FF5722;"><i class="fas fa-file-contract"></i> عقد إيجار سيارة</h1>
            <h3><?php echo SITE_NAME; ?></h3>
            <p class="mb-2">🇵🇸 <?php echo COMPANY_ADDRESS; ?></p>
            <p class="mb-2">هاتف: <?php echo COMPANY_PHONE; ?> | بريد: <?php echo COMPANY_EMAIL; ?></p>
            <div class="contract-number">رقم العقد: <?php echo $rental['rental_number']; ?></div>
            <p class="mt-2"><strong>تاريخ العقد:</strong> <?php echo formatDate($rental['created_at'], 'd/m/Y'); ?></p>
        </div>

        <!-- Customer Info -->
        <div class="section-title">الطرف الأول (المستأجر)</div>
        <div class="row">
            <div class="col-md-6">
                <div class="info-row">
                    <span class="info-label">الاسم الكامل:</span>
                    <strong><?php echo $rental['customer_name']; ?></strong>
                </div>
                <div class="info-row">
                    <span class="info-label">رقم الهوية:</span>
                    <strong><?php echo $rental['national_id']; ?></strong>
                </div>
                <div class="info-row">
                    <span class="info-label">رقم الهاتف:</span>
                    <strong><?php echo $rental['phone']; ?></strong>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-row">
                    <span class="info-label">البريد الإلكتروني:</span>
                    <strong><?php echo $rental['email'] ?: '-'; ?></strong>
                </div>
                <div class="info-row">
                    <span class="info-label">رقم رخصة القيادة:</span>
                    <strong><?php echo $rental['driver_license']; ?></strong>
                </div>
                <div class="info-row">
                    <span class="info-label">تاريخ انتهاء الرخصة:</span>
                    <strong><?php echo formatDate($rental['license_expiry'], 'd/m/Y'); ?></strong>
                </div>
            </div>
        </div>

        <!-- Car Info -->
        <div class="section-title">الطرف الثاني (السيارة)</div>
        <div class="row">
            <div class="col-md-6">
                <div class="info-row">
                    <span class="info-label">نوع السيارة:</span>
                    <strong><?php echo $rental['brand'] . ' ' . $rental['model'] . ' ' . $rental['year']; ?></strong>
                </div>
                <div class="info-row">
                    <span class="info-label">رقم اللوحة:</span>
                    <strong style="color: #FF5722;"><?php echo $rental['plate_number']; ?></strong>
                </div>
                <div class="info-row">
                    <span class="info-label">اللون:</span>
                    <strong><?php echo $rental['color']; ?></strong>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-row">
                    <span class="info-label">نوع النقل:</span>
                    <strong><?php echo TRANSMISSION_TYPES[$rental['transmission']]; ?></strong>
                </div>
                <div class="info-row">
                    <span class="info-label">نوع الوقود:</span>
                    <strong><?php echo FUEL_TYPES[$rental['fuel_type']]; ?></strong>
                </div>
                <div class="info-row">
                    <span class="info-label">عدد المقاعد:</span>
                    <strong><?php echo $rental['seats']; ?> مقعد</strong>
                </div>
            </div>
        </div>

        <!-- Rental Details -->
        <div class="section-title">تفاصيل عقد الإيجار</div>
        <div class="row">
            <div class="col-md-6">
                <div class="info-row">
                    <span class="info-label">تاريخ البدء:</span>
                    <strong><?php echo formatDate($rental['start_date'], 'd/m/Y'); ?></strong>
                </div>
                <div class="info-row">
                    <span class="info-label">مكان الاستلام:</span>
                    <strong><?php echo $rental['pickup_location'] ?: COMPANY_ADDRESS; ?></strong>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-row">
                    <span class="info-label">تاريخ الإرجاع:</span>
                    <strong><?php echo formatDate($rental['end_date'], 'd/m/Y'); ?></strong>
                </div>
                <div class="info-row">
                    <span class="info-label">مكان الإرجاع:</span>
                    <strong><?php echo $rental['return_location'] ?: COMPANY_ADDRESS; ?></strong>
                </div>
            </div>
        </div>

        <!-- Financial Details -->
        <div class="section-title">التفاصيل المالية</div>
        <table class="table table-bordered">
            <tr>
                <td class="info-label">عدد الأيام</td>
                <td><strong><?php echo $rental['total_days']; ?> يوم</strong></td>
            </tr>
            <tr>
                <td class="info-label">الأجرة اليومية</td>
                <td><strong><?php echo formatCurrency($rental['daily_rate']); ?></strong></td>
            </tr>
            <tr>
                <td class="info-label">إجمالي الأجرة</td>
                <td><strong><?php echo formatCurrency($rental['subtotal']); ?></strong></td>
            </tr>
            <?php if ($rental['discount_amount'] > 0): ?>
            <tr>
                <td class="info-label">الخصم</td>
                <td><strong class="text-danger">- <?php echo formatCurrency($rental['discount_amount']); ?></strong></td>
            </tr>
            <?php endif; ?>
            <?php if ($rental['tax_amount'] > 0): ?>
            <tr>
                <td class="info-label">الضريبة</td>
                <td><strong>+ <?php echo formatCurrency($rental['tax_amount']); ?></strong></td>
            </tr>
            <?php endif; ?>
            <tr class="table-primary">
                <td class="info-label"><strong>إجمالي المبلغ المستحق</strong></td>
                <td><strong style="color: #FF5722; font-size: 1.2rem;"><?php echo formatCurrency($rental['total_amount']); ?></strong></td>
            </tr>
            <tr>
                <td class="info-label">التأمين</td>
                <td><strong><?php echo formatCurrency($rental['deposit_amount']); ?></strong></td>
            </tr>
        </table>

        <!-- Terms and Conditions -->
        <div class="section-title">الشروط والأحكام</div>
        <div class="terms">
            <ol>
                <li>يلتزم المستأجر بإرجاع السيارة في التاريخ والمكان المحددين في هذا العقد.</li>
                <li>يتعهد المستأجر بالمحافظة على السيارة وعدم استخدامها في أنشطة غير قانونية.</li>
                <li>في حالة التأخير عن موعد الإرجاع، يتم فرض غرامة تأخير بقيمة الأجرة اليومية.</li>
                <li>المستأجر مسؤول عن أي أضرار تلحق بالسيارة خلال فترة الإيجار.</li>
                <li>يتم استرجاع مبلغ التأمين عند إرجاع السيارة بحالة جيدة.</li>
                <li>يحظر استخدام السيارة خارج حدود فلسطين إلا بموافقة خطية مسبقة.</li>
                <li>المستأجر مسؤول عن جميع مخالفات السير والغرامات المرورية.</li>
                <li>يجب على المستأجر إبلاغ الشركة فوراً في حالة وقوع أي حادث.</li>
            </ol>
        </div>

        <!-- Signatures -->
        <div class="row mt-5">
            <div class="col-md-6">
                <h6>توقيع المستأجر</h6>
                <div class="signature-box">التوقيع</div>
                <p class="mt-2"><strong>الاسم:</strong> <?php echo $rental['customer_name']; ?></p>
                <p><strong>التاريخ:</strong> ________________</p>
            </div>
            <div class="col-md-6">
                <h6>توقيع الشركة</h6>
                <div class="signature-box">التوقيع والختم</div>
                <p class="mt-2"><strong>باسم:</strong> <?php echo SITE_NAME; ?></p>
                <p><strong>التاريخ:</strong> <?php echo formatDate('now', 'd/m/Y'); ?></p>
            </div>
        </div>

        <div class="text-center mt-5 text-muted">
            <small>هذا العقد مطبوع إلكترونياً من نظام <?php echo SITE_NAME; ?> 🇵🇸</small>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>