<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();

// Filters
$reportType = isset($_GET['report_type']) ? $_GET['report_type'] : 'revenue';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$carId = isset($_GET['car_id']) ? (int)$_GET['car_id'] : 0;
$customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
$exportType = isset($_GET['export']) ? $_GET['export'] : '';

// Get cars for filter
$carsStmt = $db->query("SELECT id, brand, model, plate_number FROM cars ORDER BY brand");
$cars = $carsStmt->fetchAll();

// Get customers for filter
$customersStmt = $db->query("SELECT id, full_name, phone FROM customers ORDER BY full_name");
$customers = $customersStmt->fetchAll();

// Build report data based on type
$reportData = [];
$reportTitle = '';
$reportColumns = [];

switch ($reportType) {
    case 'revenue':
        $reportTitle = 'تقرير الإيرادات التفصيلي';
        $reportColumns = ['التاريخ', 'رقم الحجز', 'العميل', 'السيارة', 'المبلغ', 'المدفوع', 'المتبقي', 'الحالة'];
        
        $sql = "SELECT r.created_at, r.rental_number, c.full_name, 
                       ca.brand, ca.model, r.total_amount, r.paid_amount, 
                       r.remaining_amount, r.status
                FROM rentals r
                JOIN customers c ON r.customer_id = c.id
                JOIN cars ca ON r.car_id = ca.id
                WHERE DATE(r.created_at) BETWEEN ? AND ?";
        $params = [$startDate, $endDate];
        
        if ($carId > 0) {
            $sql .= " AND r.car_id = ?";
            $params[] = $carId;
        }
        if ($customerId > 0) {
            $sql .= " AND r.customer_id = ?";
            $params[] = $customerId;
        }
        
        $sql .= " ORDER BY r.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        foreach ($results as $row) {
            $reportData[] = [
                formatDate($row['created_at'], 'd/m/Y'),
                $row['rental_number'],
                $row['full_name'],
                $row['brand'] . ' ' . $row['model'],
                $row['total_amount'],
                $row['paid_amount'],
                $row['remaining_amount'],
                RENTAL_STATUS[$row['status']]
            ];
        }
        break;
        
    case 'cars_performance':
        $reportTitle = 'تقرير أداء السيارات';
        $reportColumns = ['السيارة', 'رقم اللوحة', 'عدد الحجوزات', 'إجمالي الأيام', 'الإيراد', 'متوسط الأجرة', 'معدل الإشغال'];
        
        $sql = "SELECT ca.brand, ca.model, ca.plate_number,
                       COUNT(r.id) as total_rentals,
                       SUM(r.total_days) as total_days,
                       SUM(r.total_amount) as total_revenue,
                       AVG(r.daily_rate) as avg_rate,
                       ROUND((SUM(r.total_days) / DATEDIFF(?, ?)) * 100, 2) as occupancy_rate
                FROM cars ca
                LEFT JOIN rentals r ON ca.id = r.car_id 
                    AND DATE(r.created_at) BETWEEN ? AND ?
                GROUP BY ca.id
                ORDER BY total_revenue DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$endDate, $startDate, $startDate, $endDate]);
        $results = $stmt->fetchAll();
        
        foreach ($results as $row) {
            $reportData[] = [
                $row['brand'] . ' ' . $row['model'],
                $row['plate_number'],
                $row['total_rentals'] ?: 0,
                $row['total_days'] ?: 0,
                $row['total_revenue'] ?: 0,
                $row['avg_rate'] ? round($row['avg_rate'], 2) : 0,
                $row['occupancy_rate'] . '%'
            ];
        }
        break;
        
    case 'customers_analysis':
        $reportTitle = 'تحليل العملاء';
        $reportColumns = ['العميل', 'الهاتف', 'المدينة', 'عدد الحجوزات', 'إجمالي الإنفاق', 'متوسط الحجز', 'نقاط الولاء', 'المستوى'];
        
        $sql = "SELECT c.full_name, c.phone, c.city,
                       COUNT(r.id) as total_rentals,
                       SUM(r.total_amount) as total_spent,
                       AVG(r.total_amount) as avg_rental,
                       c.loyalty_points, c.loyalty_level
                FROM customers c
                LEFT JOIN rentals r ON c.id = r.customer_id 
                    AND DATE(r.created_at) BETWEEN ? AND ?
                GROUP BY c.id
                ORDER BY total_spent DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        $results = $stmt->fetchAll();
        
        foreach ($results as $row) {
            $reportData[] = [
                $row['full_name'],
                $row['phone'],
                $row['city'],
                $row['total_rentals'] ?: 0,
                $row['total_spent'] ?: 0,
                $row['avg_rental'] ? round($row['avg_rental'], 2) : 0,
                $row['loyalty_points'],
                LOYALTY_LEVELS[$row['loyalty_level']]
            ];
        }
        break;
        
    case 'payments':
        $reportTitle = 'تقرير المدفوعات';
        $reportColumns = ['التاريخ', 'رقم الحجز', 'العميل', 'المبلغ', 'الطريقة', 'استلمه', 'ملاحظات'];
        
        $sql = "SELECT p.created_at, r.rental_number, c.full_name,
                       p.amount, p.payment_method, u.username, p.notes
                FROM payments p
                JOIN rentals r ON p.rental_id = r.id
                JOIN customers c ON r.customer_id = c.id
                LEFT JOIN users u ON p.received_by = u.id
                WHERE DATE(p.created_at) BETWEEN ? AND ?
                ORDER BY p.created_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        $results = $stmt->fetchAll();
        
        foreach ($results as $row) {
            $reportData[] = [
                formatDate($row['created_at'], 'd/m/Y H:i'),
                $row['rental_number'],
                $row['full_name'],
                $row['amount'],
                PAYMENT_METHODS[$row['payment_method']],
                $row['username'],
                $row['notes']
            ];
        }
        break;
        
    case 'maintenance':
        $reportTitle = 'تقرير الصيانة والمصروفات';
        $reportColumns = ['التاريخ', 'السيارة', 'النوع', 'الوصف', 'التكلفة', 'الحالة'];
        
        $sql = "SELECT m.maintenance_date, ca.brand, ca.model, 
                       m.type, m.description, m.cost, m.status
                FROM maintenance m
                JOIN cars ca ON m.car_id = ca.id
                WHERE DATE(m.maintenance_date) BETWEEN ? AND ?
                ORDER BY m.maintenance_date DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        $results = $stmt->fetchAll();
        
        foreach ($results as $row) {
            $reportData[] = [
                formatDate($row['maintenance_date'], 'd/m/Y'),
                $row['brand'] . ' ' . $row['model'],
                MAINTENANCE_TYPES[$row['type']],
                substr($row['description'], 0, 50),
                $row['cost'],
                MAINTENANCE_STATUS[$row['status']]
            ];
        }
        break;
        
    case 'profit_loss':
        $reportTitle = 'تقرير الربح والخسارة (P&L)';
        $reportColumns = ['البند', 'المبلغ'];
        
        // Revenue
        $revenueStmt = $db->prepare("
            SELECT SUM(total_amount) as total FROM rentals 
            WHERE DATE(created_at) BETWEEN ? AND ?
        ");
        $revenueStmt->execute([$startDate, $endDate]);
        $revenue = $revenueStmt->fetchColumn() ?: 0;
        
        // Expenses (Maintenance)
        $expensesStmt = $db->prepare("
            SELECT SUM(cost) as total FROM maintenance 
            WHERE DATE(maintenance_date) BETWEEN ? AND ?
        ");
        $expensesStmt->execute([$startDate, $endDate]);
        $expenses = $expensesStmt->fetchColumn() ?: 0;
        
        // Expenses (Other)
        $otherExpensesStmt = $db->prepare("
            SELECT SUM(amount) as total FROM expenses 
            WHERE DATE(expense_date) BETWEEN ? AND ?
        ");
        $otherExpensesStmt->execute([$startDate, $endDate]);
        $otherExpenses = $otherExpensesStmt->fetchColumn() ?: 0;
        
        $totalExpenses = $expenses + $otherExpenses;
        $netProfit = $revenue - $totalExpenses;
        $profitMargin = $revenue > 0 ? round(($netProfit / $revenue) * 100, 2) : 0;
        
        $reportData = [
            ['الإيرادات', $revenue],
            ['مصروفات الصيانة', $expenses],
            ['مصروفات أخرى', $otherExpenses],
            ['إجمالي المصروفات', $totalExpenses],
            ['صافي الربح', $netProfit],
            ['هامش الربح', $profitMargin . '%']
        ];
        break;
}

// Handle Export
if ($exportType === 'excel') {
    require_once '../core/ExcelExport.php';
    $excel = new ExcelExport();
    $excel->export($reportTitle, $reportColumns, $reportData, $reportType . '_' . date('Y-m-d'));
    exit;
} elseif ($exportType === 'pdf') {
    require_once '../core/PDFReport.php';
    $pdfReport = new PDFReport();
    $pdfReport->generate($reportTitle, $reportColumns, $reportData, $startDate, $endDate);
    exit;
}

$page_title = 'التقارير المتقدمة - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-chart-bar me-2"></i>التقارير المتقدمة</h5>
            <p>تقارير شاملة وتحليلات تفصيلية</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="table-container mb-4">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">نوع التقرير</label>
                <select name="report_type" class="form-control" required>
                    <option value="revenue" <?php echo $reportType == 'revenue' ? 'selected' : ''; ?>>الإيرادات</option>
                    <option value="cars_performance" <?php echo $reportType == 'cars_performance' ? 'selected' : ''; ?>>أداء السيارات</option>
                    <option value="customers_analysis" <?php echo $reportType == 'customers_analysis' ? 'selected' : ''; ?>>تحليل العملاء</option>
                    <option value="payments" <?php echo $reportType == 'payments' ? 'selected' : ''; ?>>المدفوعات</option>
                    <option value="maintenance" <?php echo $reportType == 'maintenance' ? 'selected' : ''; ?>>الصيانة</option>
                    <option value="profit_loss" <?php echo $reportType == 'profit_loss' ? 'selected' : ''; ?>>الربح والخسارة</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">من تاريخ</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">إلى تاريخ</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">السيارة</label>
                <select name="car_id" class="form-control">
                    <option value="0">الكل</option>
                    <?php foreach ($cars as $car): ?>
                    <option value="<?php echo $car['id']; ?>" <?php echo $carId == $car['id'] ? 'selected' : ''; ?>>
                        <?php echo $car['brand'] . ' ' . $car['model']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">العميل</label>
                <select name="customer_id" class="form-control">
                    <option value="0">الكل</option>
                    <?php foreach ($customers as $customer): ?>
                    <option value="<?php echo $customer['id']; ?>" <?php echo $customerId == $customer['id'] ? 'selected' : ''; ?>>
                        <?php echo $customer['full_name']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- Export Buttons -->
    <div class="mb-3">
        <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'excel'])); ?>" class="btn btn-success">
            <i class="fas fa-file-excel me-2"></i>تصدير Excel
        </a>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'pdf'])); ?>" class="btn btn-danger">
            <i class="fas fa-file-pdf me-2"></i>تصدير PDF
        </a>
        <button onclick="window.print()" class="btn btn-secondary">
            <i class="fas fa-print me-2"></i>طباعة
        </button>
    </div>

    <!-- Report Table -->
    <div class="table-container">
        <h5 class="mb-3">
            <i class="fas fa-file-alt text-primary"></i>
            <?php echo $reportTitle; ?>
            <small class="text-muted">(
                <?php echo formatDate($startDate, 'd/m/Y'); ?> - 
                <?php echo formatDate($endDate, 'd/m/Y'); ?>)
            </small>
        </h5>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <?php foreach ($reportColumns as $col): ?>
                        <th><?php echo $col; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reportData)): ?>
                    <tr>
                        <td colspan="<?php echo count($reportColumns); ?>" class="text-center">
                            لا توجد بيانات للفترة المحددة
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php 
                        $totals = [];
                        foreach ($reportData as $row): 
                        ?>
                        <tr>
                            <?php foreach ($row as $i => $cell): ?>
                            <td>
                                <?php 
                                if (is_numeric($cell) && $i > 0 && in_array($reportType, ['revenue', 'cars_performance', 'customers_analysis', 'payments', 'maintenance'])) {
                                    echo formatCurrency($cell);
                                    if (!isset($totals[$i])) $totals[$i] = 0;
                                    $totals[$i] += $cell;
                                } else {
                                    echo $cell;
                                }
                                ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (!empty($totals) && $reportType !== 'profit_loss'): ?>
                        <tr class="table-success fw-bold">
                            <td colspan="<?php echo min(array_keys($totals)); ?>">الإجمالي</td>
                            <?php foreach ($totals as $total): ?>
                            <td><?php echo formatCurrency($total); ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endif; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="mt-3 text-muted">
            <small>
                <i class="fas fa-info-circle"></i>
                تم إنشاء التقرير في: <?php echo date('d/m/Y H:i:s'); ?> |
                عدد السجلات: <?php echo count($reportData); ?>
            </small>
        </div>
    </div>
</div>

<style>
@media print {
    .sidebar, .top-bar-right, .btn, .form-control { display: none !important; }
    .main-content { margin: 0 !important; }
}
</style>

<?php include 'includes/footer.php'; ?>