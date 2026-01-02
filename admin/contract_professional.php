<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();

if (!isset($_GET['id'])) {
    redirect('rentals.php');
}

$rental_id = (int)$_GET['id'];

// Get rental details - بدون image_url
$stmt = $db->prepare("
    SELECT r.*, 
           c.full_name as customer_name, c.phone as customer_phone, 
           c.address as customer_address, c.id_number, c.driver_license,
           car.brand, car.model, car.year, car.color, car.plate_number,
           car.type as car_type, car.seats, car.transmission,
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
    redirect('rentals.php');
}

// Get contract type
$stmt = $db->prepare("SELECT contract_type, has_promissory_note FROM rental_contracts WHERE rental_id = ? LIMIT 1");
$stmt->execute([$rental_id]);
$contractRecord = $stmt->fetch();
$with_promissory = ($contractRecord['has_promissory_note'] ?? 0) == 1;

// Safe values
$rental['base_amount'] = $rental['base_amount'] ?? ($rental['total_amount'] - ($rental['insurance_amount'] ?? 0));
$rental['insurance_amount'] = $rental['insurance_amount'] ?? 0;
$rental['discount_amount'] = $rental['discount_amount'] ?? 0;
$rental['paid_amount'] = $rental['paid_amount'] ?? 0;
$rental['tax_amount'] = 0; // No Tax

$remaining_amount = $rental['total_amount'] - $rental['paid_amount'];
$page_title = 'عقد إيجار رقم ' . $rental['rental_number'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Cairo', sans-serif;
        }
        
        body {
            background: #f0f2f5;
            padding: 15px;
        }
        
        @media print {
            body { background: white; padding: 0; }
            .no-print { display: none !important; }
            .page-break { page-break-after: always; }
        }
        
        .contract {
            background: white;
            width: 210mm;
            margin: 0 auto;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            position: relative;
        }
        
        /* ===== HEADER / TROUHEHA ===== */
        .header {
            background: linear-gradient(135deg, #1a237e 0%, #0d47a1 100%);
            color: white;
            padding: 20px;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 20px;
            align-items: center;
            border-bottom: 5px solid #ff6f00;
        }
        
        .logo-box {
            text-align: center;
        }
        
        .logo-circle {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #ff6f00, #ff9100);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 900;
            box-shadow: 0 4px 15px rgba(255, 111, 0, 0.4);
        }
        
        .header-info {
            text-align: center;
        }
        
        .header-info h1 {
            font-size: 24px;
            font-weight: 900;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }
        
        .header-info .company {
            font-size: 14px;
            color: #ffeb3b;
            font-weight: 700;
        }
        
        .header-info .details {
            font-size: 11px;
            margin-top: 8px;
            line-height: 1.6;
            color: #eceff1;
        }
        
        .header-stamp {
            text-align: center;
        }
        
        .stamp {
            border: 3px solid #ff6f00;
            border-radius: 50%;
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 900;
            color: #ff6f00;
            transform: rotate(-15deg);
            background: rgba(255, 255, 255, 0.1);
        }
        
        /* ===== CONTRACT NUMBER BAR ===== */
        .contract-bar {
            background: linear-gradient(90deg, #ff6f00, #ffb74d);
            color: white;
            padding: 12px 20px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 30px;
            font-weight: 700;
            font-size: 13px;
        }
        
        .contract-bar strong {
            color: #fff;
        }
        
        /* ===== SECTION HEADER ===== */
        .section-header {
            background: linear-gradient(90deg, #1a237e, #283593);
            color: white;
            padding: 10px 15px;
            font-weight: 900;
            font-size: 13px;
            border-right: 4px solid #ff6f00;
            margin: 15px 0 10px 0;
        }
        
        /* ===== MAIN TABLE ===== */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 12px;
        }
        
        .info-table td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        
        .info-table td:first-child {
            background: #f5f5f5;
            font-weight: 700;
            color: #1a237e;
            width: 25%;
        }
        
        .info-table tr:nth-child(even) td:first-child {
            background: #ede7f6;
        }
        
        /* ===== FINANCIAL TABLE ===== */
        .financial-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 12px;
        }
        
        .financial-table td {
            padding: 12px;
            border: 2px solid #1a237e;
        }
        
        .financial-table td:first-child {
            background: #ede7f6;
            font-weight: 700;
            width: 50%;
            text-align: right;
        }
        
        .financial-table td:last-child {
            background: #f5f5f5;
            text-align: center;
            font-weight: 700;
            font-size: 13px;
        }
        
        .financial-table tr:last-child td {
            background: linear-gradient(90deg, #1a237e, #283593);
            color: white;
            font-size: 14px;
            font-weight: 900;
        }
        
        /* ===== CAR IMAGES INSPECTION ===== */
        .inspection-header {
            background: #1a237e;
            color: white;
            padding: 12px 15px;
            font-weight: 900;
            font-size: 13px;
            margin-top: 20px;
            border-right: 4px solid #ff6f00;
            text-align: center;
        }
        
        .car-images {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            padding: 15px;
            background: #f9f9f9;
            margin-top: 10px;
        }
        
        .car-image-item {
            background: white;
            border: 3px solid #1a237e;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }
        
        .car-position {
            background: linear-gradient(90deg, #1a237e, #283593);
            color: white;
            padding: 8px;
            font-weight: 700;
            font-size: 12px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        
        .car-image {
            width: 100%;
            height: 140px;
            background: white;
            border: 2px solid #1a237e;
            border-radius: 4px;
            margin-bottom: 10px;
            object-fit: contain;
            padding: 5px;
        }
        
        .drawing-canvas {
            width: 100%;
            height: 140px;
            border: 2px dashed #1a237e;
            background: white;
            border-radius: 4px;
            margin-top: 10px;
            cursor: crosshair;
        }
        
        .drawing-notes {
            font-size: 11px;
            color: #666;
            font-style: italic;
            background: #fff3e0;
            padding: 8px;
            border-radius: 4px;
            margin-top: 8px;
        }
        
        /* ===== SIGNATURE SECTION ===== */
        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
            padding: 0 15px;
        }
        
        .signature-box {
            text-align: center;
        }
        
        .signature-canvas {
            border: 2px dashed #1a237e;
            background: white;
            border-radius: 4px;
            cursor: crosshair;
            width: 100%;
            height: 120px;
            margin-bottom: 15px;
        }
        
        .signature-line {
            border-top: 2px solid #1a237e;
            padding-top: 10px;
            font-size: 12px;
        }
        
        .signature-line strong {
            color: #ff6f00;
            display: block;
            margin-bottom: 3px;
        }
        
        /* ===== TERMS ===== */
        .terms-section {
            padding: 15px;
            background: #ede7f6;
            margin: 15px 0;
            border-right: 4px solid #ff6f00;
            font-size: 11px;
            line-height: 1.8;
        }
        
        .terms-section h4 {
            color: #1a237e;
            margin-bottom: 10px;
            font-size: 12px;
        }
        
        /* ===== FOOTER ===== */
        .footer {
            text-align: center;
            padding: 15px;
            border-top: 3px solid #1a237e;
            font-size: 11px;
            color: #666;
            background: #f5f5f5;
        }
        
        /* ===== BUTTONS ===== */
        .actions {
            position: fixed;
            bottom: 20px;
            left: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            z-index: 1000;
        }
        
        .btn {
            background: linear-gradient(135deg, #1a237e, #0d47a1);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 25px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(26, 35, 126, 0.3);
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 35, 126, 0.4);
        }
        
        .btn-orange {
            background: linear-gradient(135deg, #ff6f00, #ff9100);
        }
        
        .btn-green {
            background: linear-gradient(135deg, #00695c, #004d40);
        }
        
        .btn-back {
            background: linear-gradient(135deg, #455a64, #37474f);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        
        @media (max-width: 768px) {
            .contract {
                width: 100%;
            }
            
            .car-images {
                grid-template-columns: 1fr;
            }
            
            .signature-section {
                grid-template-columns: 1fr;
            }
            
            .actions {
                position: static;
                justify-content: center;
                margin: 20px 0;
            }
        }
    </style>
</head>
<body>
    <!-- BUTTONS -->
    <div class="actions no-print">
        <button class="btn btn-orange" onclick="window.print()">🖨️ طباعة</button>
        <button class="btn btn-green" onclick="exportPDF()">📥 تحميل PDF</button>
        <button class="btn" onclick="clearDrawings()">🔄 مسح</button>
        <a href="rentals.php" class="btn btn-back">← عودة</a>
    </div>
    
    <div class="contract" id="contract">
        <!-- ===== HEADER ===== -->
        <div class="header">
            <div class="logo-box">
                <div class="logo-circle">🚗</div>
            </div>
            <div class="header-info">
                <h1>عقد إيجار سيارة</h1>
                <div class="company"><?php echo COMPANY_NAME; ?></div>
                <div class="details">
                    📞 <?php echo COMPANY_PHONE; ?><br>
                    📧 <?php echo COMPANY_EMAIL; ?><br>
                    📍 <?php echo COMPANY_ADDRESS; ?> | 🇵🇸
                </div>
            </div>
            <div class="header-stamp">
                <div class="stamp">عقد<br>رسمي</div>
            </div>
        </div>
        
        <!-- CONTRACT NUMBER BAR -->
        <div class="contract-bar">
            <div>رقم العقد: <strong><?php echo $rental['rental_number']; ?></strong></div>
            <div>التاريخ: <strong><?php echo formatDate($rental['created_at']); ?></strong></div>
            <div>النوع: <strong><?php echo $with_promissory ? '✅ مع كمبيالة' : '📋 بسيط'; ?></strong></div>
        </div>
        
        <!-- ===== CUSTOMER DATA ===== -->
        <div class="section-header">👤 بيانات المستأجر</div>
        <table class="info-table">
            <tr>
                <td>الاسم الكامل</td>
                <td><strong><?php echo htmlspecialchars($rental['customer_name']); ?></strong></td>
                <td>رقم الهوية</td>
                <td><strong><?php echo $rental['id_number']; ?></strong></td>
            </tr>
            <tr>
                <td>الجنسية</td>
                <td>فلسطيني</td>
                <td>الهاتف</td>
                <td><strong><?php echo $rental['customer_phone']; ?></strong></td>
            </tr>
            <tr>
                <td>مكان الإقامة</td>
                <td colspan="3"><?php echo htmlspecialchars($rental['customer_address']); ?></td>
            </tr>
        </table>
        
        <!-- ===== CAR DATA ===== -->
        <div class="section-header">🚙 بيانات السيارة</div>
        <table class="info-table">
            <tr>
                <td>نوع السيارة</td>
                <td><strong><?php echo $rental['brand'] . ' ' . $rental['model'] . ' (' . $rental['year'] . ')'; ?></strong></td>
                <td>اللون</td>
                <td><?php echo $rental['color']; ?></td>
            </tr>
            <tr>
                <td>رقم اللوحة</td>
                <td><strong style="color:#ff6f00; font-size:13px;"><?php echo $rental['plate_number']; ?></strong></td>
                <td>النوع</td>
                <td><?php echo $rental['car_type']; ?></td>
            </tr>
            <tr>
                <td>عدد المقاعد</td>
                <td><?php echo $rental['seats']; ?></td>
                <td>ناقل الحركة</td>
                <td><?php echo $rental['transmission'] ?? 'أوتوماتيك'; ?></td>
            </tr>
            <tr>
                <td>عداد الكيلومتر عند الاستلام</td>
                <td colspan="3"><strong><?php echo $rental['mileage_start'] ?? 0; ?> كم</strong></td>
            </tr>
        </table>
        
        <!-- ===== RENTAL PERIOD ===== -->
        <div class="section-header">📅 فترة الإيجار</div>
        <table class="info-table">
            <tr>
                <td>تاريخ الاستلام</td>
                <td><?php echo formatDate($rental['start_date']); ?></td>
                <td>الوقت</td>
                <td><?php echo date('H:i', strtotime($rental['start_date'])); ?></td>
            </tr>
            <tr>
                <td>تاريخ التسليم</td>
                <td><?php echo formatDate($rental['end_date']); ?></td>
                <td>الوقت</td>
                <td><?php echo date('H:i', strtotime($rental['end_date'])); ?></td>
            </tr>
            <tr>
                <td>عدد الأيام</td>
                <td colspan="3"><strong><?php echo $rental['total_days']; ?> يوم</strong></td>
            </tr>
        </table>
        
        <!-- ===== FINANCIAL - NO TAX ===== -->
        <div class="section-header">💰 التفاصيل المالية</div>
        <table class="financial-table">
            <tr>
                <td>السعر الأساسي لليوم الواحد</td>
                <td><?php echo formatCurrency($rental['base_amount'] / $rental['total_days']); ?></td>
            </tr>
            <tr>
                <td>المبلغ الأساسي (<?php echo $rental['total_days']; ?> يوم)</td>
                <td><?php echo formatCurrency($rental['base_amount']); ?></td>
            </tr>
            <?php if ($rental['discount_amount'] > 0): ?>
            <tr>
                <td>الخصم</td>
                <td style="color: #4caf50;">-<?php echo formatCurrency($rental['discount_amount']); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>رسوم التأمين</td>
                <td><?php echo formatCurrency($rental['insurance_amount']); ?></td>
            </tr>
            <tr>
                <td>المبلغ الإجمالي</td>
                <td><?php echo formatCurrency($rental['total_amount']); ?></td>
            </tr>
        </table>
        
        <!-- ===== INSPECTION FORM WITH CAR IMAGES ===== -->
        <div class="page-break"></div>
        
        <div class="inspection-header">🔍 نموذج فحص حالة السيارة - اخترق على الرسومات التالية</div>
        
        <div class="car-images">
            <!-- FRONT -->
            <div class="car-image-item">
                <div class="car-position">🔴 الجهة الأمامية</div>
                <svg class="car-image" viewBox="0 0 200 150" xmlns="http://www.w3.org/2000/svg">
                    <rect x="30" y="40" width="140" height="80" fill="#ddd" stroke="#333" stroke-width="2"/>
                    <circle cx="60" cy="110" r="15" fill="#333"/>
                    <circle cx="140" cy="110" r="15" fill="#333"/>
                    <rect x="50" y="50" width="30" height="20" fill="#87ceeb" stroke="#333" stroke-width="1"/>
                    <rect x="120" y="50" width="30" height="20" fill="#87ceeb" stroke="#333" stroke-width="1"/>
                    <polygon points="100,40 130,40 100,20" fill="#666"/>
                    <rect x="70" y="25" width="60" height="12" fill="#f4f4f4" stroke="#333" stroke-width="1"/>
                </svg>
                <canvas id="carFront" class="drawing-canvas"></canvas>
                <div class="drawing-notes">📋 اخترق على أي خدوش أو تجنيشات في الأمام</div>
            </div>
            
            <!-- BACK -->
            <div class="car-image-item">
                <div class="car-position">🟡 الجهة الخلفية</div>
                <svg class="car-image" viewBox="0 0 200 150" xmlns="http://www.w3.org/2000/svg">
                    <rect x="30" y="40" width="140" height="80" fill="#ddd" stroke="#333" stroke-width="2"/>
                    <circle cx="60" cy="110" r="15" fill="#333"/>
                    <circle cx="140" cy="110" r="15" fill="#333"/>
                    <rect x="50" y="50" width="30" height="20" fill="#ff6b6b" stroke="#333" stroke-width="1"/>
                    <rect x="120" y="50" width="30" height="20" fill="#ff6b6b" stroke="#333" stroke-width="1"/>
                    <rect x="70" y="115" width="60" height="8" fill="#555" stroke="#333" stroke-width="1"/>
                </svg>
                <canvas id="carBack" class="drawing-canvas"></canvas>
                <div class="drawing-notes">📋 اخترق على أي أضرار في الخلف</div>
            </div>
            
            <!-- LEFT -->
            <div class="car-image-item">
                <div class="car-position">🟢 الجانب الأيسر</div>
                <svg class="car-image" viewBox="0 0 200 150" xmlns="http://www.w3.org/2000/svg">
                    <ellipse cx="100" cy="100" rx="70" ry="50" fill="#ddd" stroke="#333" stroke-width="2"/>
                    <circle cx="50" cy="105" r="15" fill="#333"/>
                    <circle cx="150" cy="105" r="15" fill="#333"/>
                    <rect x="70" y="60" width="35" height="20" fill="#87ceeb" stroke="#333" stroke-width="1"/>
                    <rect x="95" y="60" width="35" height="20" fill="#87ceeb" stroke="#333" stroke-width="1"/>
                    <line x1="20" y1="100" x2="180" y2="100" stroke="#333" stroke-width="2"/>
                    <polygon points="160,85 175,95 160,105" fill="#ff9800"/>
                </svg>
                <canvas id="carLeft" class="drawing-canvas"></canvas>
                <div class="drawing-notes">📋 اخترق على الجنب الأيسر</div>
            </div>
            
            <!-- RIGHT -->
            <div class="car-image-item">
                <div class="car-position">🔵 الجانب الأيمن</div>
                <svg class="car-image" viewBox="0 0 200 150" xmlns="http://www.w3.org/2000/svg">
                    <ellipse cx="100" cy="100" rx="70" ry="50" fill="#ddd" stroke="#333" stroke-width="2"/>
                    <circle cx="50" cy="105" r="15" fill="#333"/>
                    <circle cx="150" cy="105" r="15" fill="#333"/>
                    <rect x="70" y="60" width="35" height="20" fill="#87ceeb" stroke="#333" stroke-width="1"/>
                    <rect x="95" y="60" width="35" height="20" fill="#87ceeb" stroke="#333" stroke-width="1"/>
                    <line x1="20" y1="100" x2="180" y2="100" stroke="#333" stroke-width="2"/>
                    <polygon points="25,85 40,95 25,105" fill="#ff9800"/>
                </svg>
                <canvas id="carRight" class="drawing-canvas"></canvas>
                <div class="drawing-notes">📋 اخترق على الجانب الأيمن</div>
            </div>
        </div>
        
        <!-- ===== TERMS ===== -->
        <div class="section-header">📋 الشروط والأحكام العامة</div>
        <div class="terms-section">
            <h4>🔹 الشروط الأساسية:</h4>
            1. المستأجر مسؤول عن السيارة طوال فترة الإيجار من لحظة الاستلام.<br>
            2. يتعين إعادة السيارة بنفس الحالة التي تم استلامها فيها.<br>
            3. المستأجر مسؤول عن جميع المخالفات المرورية والغرامات.<br>
            4. غرامة التأخير عند الإعادة: <?php echo formatCurrency(LATE_RETURN_FEE); ?> لكل ساعة.<br>
            5. التأمين ينطبق على الحوادث غير المقصودة فقط.<br>
            6. أي أضرار متعمدة لا تغطيها بوليشة التأمين.<br>
        </div>
        
        <!-- ===== SIGNATURES ===== -->
        <div class="section-header">✍️ التوقيعات والاتفاق</div>
        <div class="signature-section">
            <div class="signature-box">
                <canvas id="customerSig" class="signature-canvas"></canvas>
                <div class="signature-line">
                    <strong>توقيع المستأجر</strong>
                    <?php echo htmlspecialchars($rental['customer_name']); ?>
                </div>
            </div>
            <div class="signature-box">
                <canvas id="companySig" class="signature-canvas"></canvas>
                <div class="signature-line">
                    <strong>توقيع الشركة</strong>
                    <?php echo COMPANY_NAME; ?>
                </div>
            </div>
        </div>
        
        <!-- PAGE BREAK FOR PROMISSORY -->
        <?php if ($with_promissory && $remaining_amount > 0): ?>
        <div class="page-break"></div>
        
        <!-- ===== PROMISSORY NOTE ===== -->
        <div class="header">
            <div style="grid-column: 1 / -1; text-align: center;">
                <h1 style="margin: 0; font-size: 28px;">🧾 كمبيالة / سند إذني</h1>
            </div>
        </div>
        
        <div style="padding: 30px; text-align: center; line-height: 2.5; font-size: 13px;">
            <p>أتعهد أنا المضموم توقيعه أدناه</p>
            <p><strong><?php echo htmlspecialchars($rental['customer_name']); ?></strong></p>
            <p>الجنسية: فلسطيني | رقم الهوية: <strong><?php echo $rental['id_number']; ?></strong></p>
            
            <div style="margin: 30px 0; border: 3px solid #ff6f00; padding: 20px; border-radius: 10px; background: #fff3e0;">
                <p style="font-size: 11px; margin-bottom: 10px;">بدفع مبلغ وقدره</p>
                <p style="font-size: 24px; font-weight: 900; color: #ff6f00; margin: 0;"><?php echo formatCurrency($remaining_amount); ?></p>
                <p style="font-size: 12px; color: #666; margin-top: 10px;">(<?php echo numberToArabicWords($remaining_amount); ?> شيكل فقط لا غير)</p>
            </div>
            
            <p>لصالح: <strong><?php echo COMPANY_NAME; ?></strong></p>
            <p>بتاريخ: <strong><?php echo formatDate($rental['end_date']); ?></strong></p>
            <p>المرجع: عقد إيجار رقم <strong><?php echo $rental['rental_number']; ?></strong></p>
        </div>
        
        <div style="padding: 0 30px; margin-top: 40px;">
            <div class="signature-section" style="margin: 0;">
                <div></div>
                <div class="signature-box">
                    <canvas id="promissorySig" class="signature-canvas"></canvas>
                    <div class="signature-line">
                        <strong>توقيع المدين</strong>
                        <?php echo htmlspecialchars($rental['customer_name']); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- ===== FOOTER ===== -->
        <div class="footer">
            <p>🇵🇸 نظام تأجير السيارات | Made with ❤️ in Palestine</p>
            <p>هذا العقد صادر إلكترونياً وله نفس قيمة العقد الورقي</p>
            <p style="margin-top: 10px; color: #999;">© <?php echo date('Y'); ?> <?php echo COMPANY_NAME; ?> - جميع الحقوق محفوظة</p>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        // Initialize all canvas drawing
        function setupCanvas(canvasId) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            let isDrawing = false;
            let lastX, lastY;
            
            function getCoords(e) {
                const rect = canvas.getBoundingClientRect();
                return {
                    x: e.clientX - rect.left || (e.touches && e.touches[0].clientX - rect.left) || 0,
                    y: e.clientY - rect.top || (e.touches && e.touches[0].clientY - rect.top) || 0
                };
            }
            
            canvas.addEventListener('mousedown', (e) => {
                isDrawing = true;
                const { x, y } = getCoords(e);
                lastX = x;
                lastY = y;
            });
            
            canvas.addEventListener('mousemove', (e) => {
                if (!isDrawing) return;
                const { x, y } = getCoords(e);
                
                ctx.strokeStyle = '#1a237e';
                ctx.lineWidth = 3;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';
                
                ctx.beginPath();
                ctx.moveTo(lastX, lastY);
                ctx.lineTo(x, y);
                ctx.stroke();
                
                lastX = x;
                lastY = y;
            });
            
            canvas.addEventListener('mouseup', () => { isDrawing = false; });
            canvas.addEventListener('mouseout', () => { isDrawing = false; });
            
            // Touch events
            canvas.addEventListener('touchstart', (e) => {
                e.preventDefault();
                isDrawing = true;
                const { x, y } = getCoords(e);
                lastX = x;
                lastY = y;
            });
            
            canvas.addEventListener('touchmove', (e) => {
                e.preventDefault();
                if (!isDrawing) return;
                const { x, y } = getCoords(e);
                
                ctx.strokeStyle = '#1a237e';
                ctx.lineWidth = 3;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';
                
                ctx.beginPath();
                ctx.moveTo(lastX, lastY);
                ctx.lineTo(x, y);
                ctx.stroke();
                
                lastX = x;
                lastY = y;
            });
            
            canvas.addEventListener('touchend', (e) => { 
                e.preventDefault();
                isDrawing = false; 
            });
        }
        
        // Initialize all canvases
        setupCanvas('carFront');
        setupCanvas('carBack');
        setupCanvas('carLeft');
        setupCanvas('carRight');
        setupCanvas('customerSig');
        setupCanvas('companySig');
        setupCanvas('promissorySig');
        
        // Clear all drawings
        function clearDrawings() {
            const canvases = ['carFront', 'carBack', 'carLeft', 'carRight', 'customerSig', 'companySig', 'promissorySig'];
            canvases.forEach(id => {
                const canvas = document.getElementById(id);
                if (canvas) {
                    canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
                }
            });
        }
        
        // Export to PDF
        function exportPDF() {
            const element = document.getElementById('contract');
            const opt = {
                margin: 5,
                filename: 'contract-<?php echo $rental['rental_number']; ?>-<?php echo date('Y-m-d'); ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, allowTaint: true },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak: { mode: 'avoid-all' }
            };
            
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>

<?php
function numberToArabicWords($number) {
    $number = (int)$number;
    $ones = ['', 'واحد', 'اثنان', 'ثلاثة', 'أربعة', 'خمسة', 'ستة', 'سبعة', 'ثمانية', 'تسعة'];
    $tens = ['', 'عشرة', 'عشرون', 'ثلاثون', 'أربعون', 'خمسون', 'ستون', 'سبعون', 'ثمانون', 'تسعون'];
    $hundreds = ['', 'مئة', 'مئتان', 'ثلاثمئة', 'أربعمئة', 'خمسمئة', 'ستمئة', 'سبعمئة', 'ثمانمئة', 'تسعمئة'];
    
    if ($number == 0) return 'صفر';
    if ($number < 10) return $ones[$number];
    if ($number < 100) {
        $ten = floor($number / 10);
        $one = $number % 10;
        return $tens[$ten] . ($one > 0 ? ' و' . $ones[$one] : '');
    }
    if ($number < 1000) {
        $hundred = floor($number / 100);
        $remainder = $number % 100;
        $result = $hundreds[$hundred];
        if ($remainder > 0) $result .= ' و' . numberToArabicWords($remainder);
        return $result;
    }
    return (string)$number;
}
?>
