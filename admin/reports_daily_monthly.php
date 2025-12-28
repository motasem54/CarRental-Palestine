<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();

// Get filter parameters
$report_type = $_GET['type'] ?? 'daily';
$date = $_GET['date'] ?? date('Y-m-d');
$month = $_GET['month'] ?? date('Y-m');

if ($report_type == 'daily') {
    $start_date = $date . ' 00:00:00';
    $end_date = $date . ' 23:59:59';
    $title = 'تقرير يومي - ' . formatDate($date);
} else {
    $start_date = $month . '-01 00:00:00';
    $end_date = date('Y-m-t', strtotime($month . '-01')) . ' 23:59:59';
    $title = 'تقرير شهري - ' . date('F Y', strtotime($month . '-01'));
}

// Get statistics
$stats = [];

// Rentals
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_rentals,
        SUM(total_amount) as total_revenue,
        SUM(paid_amount) as total_paid,
        SUM(total_amount - paid_amount) as total_pending,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_rentals,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_rentals,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_rentals
    FROM rentals
    WHERE created_at BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$stats['rentals'] = $stmt->fetch();

// Payments
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_payments,
        SUM(amount) as total_amount,
        COUNT(CASE WHEN payment_method = 'cash' THEN 1 END) as cash_count,
        SUM(CASE WHEN payment_method = 'cash' THEN amount ELSE 0 END) as cash_amount,
        COUNT(CASE WHEN payment_method = 'card' THEN 1 END) as card_count,
        SUM(CASE WHEN payment_method = 'card' THEN amount ELSE 0 END) as card_amount,
        COUNT(CASE WHEN payment_method = 'bank_transfer' THEN 1 END) as transfer_count,
        SUM(CASE WHEN payment_method = 'bank_transfer' THEN amount ELSE 0 END) as transfer_amount
    FROM payments
    WHERE created_at BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$stats['payments'] = $stmt->fetch();

// Expenses
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_expenses,
        SUM(amount) as total_amount,
        SUM(CASE WHEN category = 'fuel' THEN amount ELSE 0 END) as fuel_amount,
        SUM(CASE WHEN category = 'maintenance' THEN amount ELSE 0 END) as maintenance_amount,
        SUM(CASE WHEN category = 'insurance' THEN amount ELSE 0 END) as insurance_amount,
        SUM(CASE WHEN category = 'other' THEN amount ELSE 0 END) as other_amount
    FROM expenses
    WHERE expense_date BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$stats['expenses'] = $stmt->fetch();

// Maintenance
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_maintenance,
        SUM(cost) as total_cost,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending
    FROM maintenance
    WHERE maintenance_date BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$stats['maintenance'] = $stmt->fetch();

// New customers
$stmt = $db->prepare("
    SELECT COUNT(*) as new_customers
    FROM customers
    WHERE created_at BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$stats['customers'] = $stmt->fetch();

// Calculate profit
$total_income = ($stats['rentals']['total_paid'] ?? 0);
$total_expenses = ($stats['expenses']['total_amount'] ?? 0) + ($stats['maintenance']['total_cost'] ?? 0);
$net_profit = $total_income - $total_expenses;

// Get detailed rentals
$stmt = $db->prepare("
    SELECT r.*, 
           c.full_name as customer_name,
           car.brand, car.model, car.plate_number
    FROM rentals r
    JOIN customers c ON r.customer_id = c.id
    JOIN cars car ON r.car_id = car.id
    WHERE r.created_at BETWEEN ? AND ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$start_date, $end_date]);
$rentals = $stmt->fetchAll();

// Get detailed expenses
$stmt = $db->prepare("
    SELECT e.*, car.brand, car.model, car.plate_number
    FROM expenses e
    LEFT JOIN cars car ON e.car_id = car.id
    WHERE e.expense_date BETWEEN ? AND ?
    ORDER BY e.expense_date DESC
");
$stmt->execute([$start_date, $end_date]);
$expenses = $stmt->fetchAll();

$page_title = $title . ' - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-chart-line me-2"></i><?php echo $title; ?></h5>
            <p>تقرير مفصل للعمليات والإيرادات والمصروفات</p>
        </div>
        <div class="top-bar-right">
            <button class="btn btn-success" onclick="exportExcel()">
                <i class="fas fa-file-excel me-2"></i>تصدير Excel
            </button>
            <button class="btn btn-danger" onclick="exportPDF()">
                <i class="fas fa-file-pdf me-2"></i>تصدير PDF
            </button>
        </div>
    </div>

    <!-- Filter -->
    <div class="stat-card mb-4">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">نوع التقرير</label>
                <select name="type" class="form-select" onchange="toggleDateInputs(this.value)">
                    <option value="daily" <?php echo $report_type == 'daily' ? 'selected' : ''; ?>>يومي</option>
                    <option value="monthly" <?php echo $report_type == 'monthly' ? 'selected' : ''; ?>>شهري</option>
                </select>
            </div>
            <div class="col-md-3" id="daily-input" style="<?php echo $report_type == 'monthly' ? 'display:none' : ''; ?>">
                <label class="form-label">التاريخ</label>
                <input type="date" name="date" class="form-control" value="<?php echo $date; ?>">
            </div>
            <div class="col-md-3" id="monthly-input" style="<?php echo $report_type == 'daily' ? 'display:none' : ''; ?>">
                <label class="form-label">الشهر</label>
                <input type="month" name="month" class="form-control" value="<?php echo $month; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary d-block w-100">
                    <i class="fas fa-search me-2"></i>عرض التقرير
                </button>
            </div>
        </form>
    </div>

    <!-- Summary Statistics -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(76, 175, 80, 0.1); color: #4CAF50;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-value"><?php echo formatCurrency($total_income); ?></div>
                <div class="stat-label">إجمالي الإيرادات</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(244, 67, 54, 0.1); color: #F44336;">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div class="stat-value"><?php echo formatCurrency($total_expenses); ?></div>
                <div class="stat-label">إجمالي المصروفات</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(33, 150, 243, 0.1); color: #2196F3;">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-value" style="color: <?php echo $net_profit >= 0 ? '#4CAF50' : '#F44336'; ?>">
                    <?php echo formatCurrency($net_profit); ?>
                </div>
                <div class="stat-label">صافي الربح</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(255, 152, 0, 0.1); color: #FF9800;">
                    <i class="fas fa-car"></i>
                </div>
                <div class="stat-value"><?php echo $stats['rentals']['total_rentals'] ?? 0; ?></div>
                <div class="stat-label">عمليات التأجير</div>
            </div>
        </div>
    </div>

    <!-- Revenue Details -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="stat-card">
                <h6 class="mb-3" style="color: #FF5722;">
                    <i class="fas fa-money-bill-wave me-2"></i>تفاصيل الإيرادات
                </h6>
                <table class="table table-sm">
                    <tr>
                        <td>إجمالي قيمة العقود</td>
                        <td class="text-start"><strong><?php echo formatCurrency($stats['rentals']['total_revenue'] ?? 0); ?></strong></td>
                    </tr>
                    <tr>
                        <td>المبالغ المحصلة</td>
                        <td class="text-start" style="color: #4CAF50;"><strong><?php echo formatCurrency($stats['rentals']['total_paid'] ?? 0); ?></strong></td>
                    </tr>
                    <tr>
                        <td>المبالغ المعلقة</td>
                        <td class="text-start" style="color: #f44336;"><strong><?php echo formatCurrency($stats['rentals']['total_pending'] ?? 0); ?></strong></td>
                    </tr>
                    <tr>
                        <td>عدد الدفعات</td>
                        <td class="text-start"><strong><?php echo $stats['payments']['total_payments'] ?? 0; ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="stat-card">
                <h6 class="mb-3" style="color: #FF5722;">
                    <i class="fas fa-receipt me-2"></i>تفاصيل المصروفات
                </h6>
                <table class="table table-sm">
                    <tr>
                        <td>🔧 صيانة</td>
                        <td class="text-start"><strong><?php echo formatCurrency($stats['expenses']['maintenance_amount'] ?? 0); ?></strong></td>
                    </tr>
                    <tr>
                        <td>⛽ بنزين</td>
                        <td class="text-start"><strong><?php echo formatCurrency($stats['expenses']['fuel_amount'] ?? 0); ?></strong></td>
                    </tr>
                    <tr>
                        <td>🛡️ تأمين</td>
                        <td class="text-start"><strong><?php echo formatCurrency($stats['expenses']['insurance_amount'] ?? 0); ?></strong></td>
                    </tr>
                    <tr>
                        <td>📝 أخرى</td>
                        <td class="text-start"><strong><?php echo formatCurrency($stats['expenses']['other_amount'] ?? 0); ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Payment Methods -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card text-center">
                <div style="font-size: 2.5rem; color: #4CAF50; margin-bottom: 10px;">💵</div>
                <h6>نقداً</h6>
                <div style="font-size: 1.5rem; font-weight: 700; color: #333;">
                    <?php echo formatCurrency($stats['payments']['cash_amount'] ?? 0); ?>
                </div>
                <small class="text-muted"><?php echo $stats['payments']['cash_count'] ?? 0; ?> عملية</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card text-center">
                <div style="font-size: 2.5rem; color: #2196F3; margin-bottom: 10px;">💳</div>
                <h6>بطاقة</h6>
                <div style="font-size: 1.5rem; font-weight: 700; color: #333;">
                    <?php echo formatCurrency($stats['payments']['card_amount'] ?? 0); ?>
                </div>
                <small class="text-muted"><?php echo $stats['payments']['card_count'] ?? 0; ?> عملية</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card text-center">
                <div style="font-size: 2.5rem; color: #FF5722; margin-bottom: 10px;">🏦</div>
                <h6>تحويل بنكي</h6>
                <div style="font-size: 1.5rem; font-weight: 700; color: #333;">
                    <?php echo formatCurrency($stats['payments']['transfer_amount'] ?? 0); ?>
                </div>
                <small class="text-muted"><?php echo $stats['payments']['transfer_count'] ?? 0; ?> عملية</small>
            </div>
        </div>
    </div>

    <!-- Detailed Rentals -->
    <?php if (count($rentals) > 0): ?>
    <div class="table-container mb-4">
        <h5 class="mb-3">
            <i class="fas fa-list text-primary"></i>
            تفاصيل عمليات التأجير (<?php echo count($rentals); ?>)
        </h5>
        <div class="table-responsive">
            <table class="table table-hover" id="rentals-table">
                <thead>
                    <tr>
                        <th>رقم العقد</th>
                        <th>العميل</th>
                        <th>السيارة</th>
                        <th>الفترة</th>
                        <th>الأيام</th>
                        <th>المبلغ</th>
                        <th>المدفوع</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rentals as $rental): ?>
                    <tr>
                        <td><strong><?php echo $rental['rental_number']; ?></strong></td>
                        <td><?php echo $rental['customer_name']; ?></td>
                        <td><?php echo $rental['brand'] . ' ' . $rental['model']; ?><br>
                            <small class="text-muted"><?php echo $rental['plate_number']; ?></small>
                        </td>
                        <td>
                            <small>
                                <?php echo formatDate($rental['start_date'], 'd/m'); ?> - 
                                <?php echo formatDate($rental['end_date'], 'd/m'); ?>
                            </small>
                        </td>
                        <td><?php echo $rental['total_days']; ?></td>
                        <td><?php echo formatCurrency($rental['total_amount']); ?></td>
                        <td><?php echo formatCurrency($rental['paid_amount']); ?></td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $rental['status'] == 'active' ? 'success' : 
                                    ($rental['status'] == 'completed' ? 'secondary' : 'warning'); 
                            ?>">
                                <?php echo RENTAL_STATUS[$rental['status']]; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Detailed Expenses -->
    <?php if (count($expenses) > 0): ?>
    <div class="table-container">
        <h5 class="mb-3">
            <i class="fas fa-receipt text-danger"></i>
            تفاصيل المصروفات (<?php echo count($expenses); ?>)
        </h5>
        <div class="table-responsive">
            <table class="table table-hover" id="expenses-table">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>النوع</th>
                        <th>السيارة</th>
                        <th>الوصف</th>
                        <th>المبلغ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expenses as $expense): ?>
                    <tr>
                        <td><?php echo formatDate($expense['expense_date']); ?></td>
                        <td>
                            <?php 
                            $icons = [
                                'fuel' => '⛽',
                                'maintenance' => '🔧',
                                'insurance' => '🛡️',
                                'other' => '📝'
                            ];
                            echo $icons[$expense['category']] ?? '📝';
                            ?>
                            <?php echo ucfirst($expense['category']); ?>
                        </td>
                        <td>
                            <?php if ($expense['car_id']): ?>
                                <?php echo $expense['brand'] . ' ' . $expense['model']; ?><br>
                                <small class="text-muted"><?php echo $expense['plate_number']; ?></small>
                            <?php else: ?>
                                <span class="text-muted">عام</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $expense['description']; ?></td>
                        <td><strong style="color: #f44336;"><?php echo formatCurrency($expense['amount']); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
function toggleDateInputs(type) {
    if (type === 'daily') {
        document.getElementById('daily-input').style.display = 'block';
        document.getElementById('monthly-input').style.display = 'none';
    } else {
        document.getElementById('daily-input').style.display = 'none';
        document.getElementById('monthly-input').style.display = 'block';
    }
}

function exportExcel() {
    // Create workbook
    const wb = XLSX.utils.book_new();
    
    // Export rentals
    <?php if (count($rentals) > 0): ?>
    const rentalsTable = document.getElementById('rentals-table');
    const rentalsWS = XLSX.utils.table_to_sheet(rentalsTable);
    XLSX.utils.book_append_sheet(wb, rentalsWS, 'التأجيرات');
    <?php endif; ?>
    
    // Export expenses
    <?php if (count($expenses) > 0): ?>
    const expensesTable = document.getElementById('expenses-table');
    const expensesWS = XLSX.utils.table_to_sheet(expensesTable);
    XLSX.utils.book_append_sheet(wb, expensesWS, 'المصروفات');
    <?php endif; ?>
    
    // Save file
    XLSX.writeFile(wb, 'report_<?php echo $report_type; ?>_<?php echo $report_type == "daily" ? $date : $month; ?>.xlsx');
}

function exportPDF() {
    window.print();
}
</script>

<script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>

<?php include 'includes/footer.php'; ?>