<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();

// Get all maintenance records
$stmt = $db->query("
    SELECT m.*, c.brand, c.model, c.plate_number, c.year
    FROM maintenance m
    JOIN cars c ON m.car_id = c.id
    ORDER BY m.maintenance_date DESC
");
$maintenances = $stmt->fetchAll();

$maintenanceTypeNames = [
    'oil_change' => 'تغيير زيت',
    'regular_maintenance' => 'صيانة دورية',
    'tire_change' => 'تغيير إطارات',
    'inspection' => 'فحص دوري',
    'brake_repair' => 'إصلاح فرامل',
    'engine_repair' => 'إصلاح محرك',
    'transmission' => 'ناقل الحركة',
    'electrical' => 'كهرباء',
    'ac_repair' => 'إصلاح مكيف',
    'body_work' => 'أعمال صفيح',
    'repair' => 'إصلاح عام',
    'other' => 'أخرى'
];

$statusNames = [
    'pending' => 'معلقة',
    'in_progress' => 'قيد التنفيذ',
    'completed' => 'مكتملة'
];

$totalCost = array_sum(array_column($maintenances, 'cost'));
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير الصيانة - <?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
            background: #fff;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #FF5722;
        }
        .header h1 {
            color: #FF5722;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header .date {
            color: #666;
            font-size: 14px;
        }
        .summary {
            background: #f5f5f5;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
        .summary-item {
            flex: 1;
        }
        .summary-item h3 {
            color: #333;
            font-size: 24px;
            margin-bottom: 5px;
        }
        .summary-item p {
            color: #666;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: right;
            border: 1px solid #ddd;
        }
        th {
            background: #FF5722;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
        }
        .status-pending { background: #FFF3E0; color: #F57C00; }
        .status-in_progress { background: #E3F2FD; color: #1976D2; }
        .status-completed { background: #E8F5E9; color: #388E3C; }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            text-align: center;
            color: #999;
            font-size: 12px;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
            @page { margin: 1cm; }
        }
        .print-btn {
            background: #FF5722;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 20px;
        }
        .print-btn:hover {
            background: #E64A19;
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">
        <i class="fas fa-print"></i> طباعة التقرير
    </button>
    
    <div class="header">
        <h1>🔧 تقرير الصيانة</h1>
        <h2><?php echo SITE_NAME; ?></h2>
        <p class="date">تاريخ الطباعة: <?php echo date('d/m/Y H:i'); ?></p>
    </div>

    <div class="summary">
        <div class="summary-item">
            <h3><?php echo count($maintenances); ?></h3>
            <p>إجمالي عمليات الصيانة</p>
        </div>
        <div class="summary-item">
            <h3><?php echo number_format($totalCost, 2); ?> ₪</h3>
            <p>إجمالي التكلفة</p>
        </div>
        <div class="summary-item">
            <h3><?php echo count(array_filter($maintenances, fn($m) => $m['status'] == 'completed')); ?></h3>
            <p>مكتملة</p>
        </div>
        <div class="summary-item">
            <h3><?php echo count(array_filter($maintenances, fn($m) => $m['status'] != 'completed')); ?></h3>
            <p>قيد التنفيذ</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>التاريخ</th>
                <th>السيارة</th>
                <th>رقم اللوحة</th>
                <th>نوع الصيانة</th>
                <th>الوصف</th>
                <th>التكلفة</th>
                <th>الحالة</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($maintenances as $index => $m): ?>
            <tr>
                <td><?php echo $index + 1; ?></td>
                <td><?php echo date('d/m/Y', strtotime($m['maintenance_date'])); ?></td>
                <td><?php echo $m['brand'] . ' ' . $m['model'] . ' ' . $m['year']; ?></td>
                <td><?php echo $m['plate_number']; ?></td>
                <td><?php echo $maintenanceTypeNames[$m['maintenance_type']] ?? $m['maintenance_type']; ?></td>
                <td><?php echo mb_substr($m['description'], 0, 50) . (mb_strlen($m['description']) > 50 ? '...' : ''); ?></td>
                <td><strong><?php echo number_format($m['cost'], 2); ?> ₪</strong></td>
                <td>
                    <span class="status status-<?php echo $m['status']; ?>">
                        <?php echo $statusNames[$m['status']] ?? $m['status']; ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background: #f5f5f5; font-weight: bold;">
                <td colspan="6" style="text-align: left;">الإجمالي:</td>
                <td colspan="2"><?php echo number_format($totalCost, 2); ?> ₪</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - جميع الحقوق محفوظة</p>
    </div>
</body>
</html>