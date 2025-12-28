<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();

// Get all cars
$cars_stmt = $db->query("
    SELECT id, brand, model, plate_number, year
    FROM cars
    WHERE status != 'sold'
    ORDER BY brand, model
");
$cars = $cars_stmt->fetchAll();

// Get selected car
$selected_car_id = $_GET['car_id'] ?? ($cars[0]['id'] ?? null);
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

if ($selected_car_id) {
    // Get car details
    $car_stmt = $db->prepare("SELECT * FROM cars WHERE id = ?");
    $car_stmt->execute([$selected_car_id]);
    $car = $car_stmt->fetch();
    
    // Get expenses by category
    $expenses_stmt = $db->prepare("
        SELECT 
            category,
            COUNT(*) as count,
            SUM(amount) as total_amount
        FROM expenses
        WHERE car_id = ? AND expense_date BETWEEN ? AND ?
        GROUP BY category
    ");
    $expenses_stmt->execute([$selected_car_id, $date_from, $date_to]);
    $expenses_by_category = [];
    while ($row = $expenses_stmt->fetch()) {
        $expenses_by_category[$row['category']] = $row;
    }
    
    // Get detailed expenses
    $detailed_expenses_stmt = $db->prepare("
        SELECT *
        FROM expenses
        WHERE car_id = ? AND expense_date BETWEEN ? AND ?
        ORDER BY expense_date DESC
    ");
    $detailed_expenses_stmt->execute([$selected_car_id, $date_from, $date_to]);
    $detailed_expenses = $detailed_expenses_stmt->fetchAll();
    
    // Get maintenance records
    $maintenance_stmt = $db->prepare("
        SELECT *
        FROM maintenance
        WHERE car_id = ? AND maintenance_date BETWEEN ? AND ?
        ORDER BY maintenance_date DESC
    ");
    $maintenance_stmt->execute([$selected_car_id, $date_from, $date_to]);
    $maintenance_records = $maintenance_stmt->fetchAll();
    
    // Get rental income for this car
    $income_stmt = $db->prepare("
        SELECT 
            COUNT(*) as rental_count,
            SUM(total_amount) as total_income,
            SUM(paid_amount) as paid_income,
            SUM(total_days) as total_days
        FROM rentals
        WHERE car_id = ? AND start_date BETWEEN ? AND ?
    ");
    $income_stmt->execute([$selected_car_id, $date_from, $date_to]);
    $income = $income_stmt->fetch();
    
    // Calculate totals
    $total_expenses = array_sum(array_column($expenses_by_category, 'total_amount'));
    $total_maintenance = array_sum(array_column($maintenance_records, 'cost'));
    $total_cost = $total_expenses + $total_maintenance;
    $total_income = $income['paid_income'] ?? 0;
    $net_profit = $total_income - $total_cost;
}

$page_title = 'تقرير مصروفات السيارات - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-car me-2"></i>تقرير مصروفات السيارات</h5>
            <p>عرض تفصيلي لمصروفات كل سيارة (بنزين، صيانة، تأمين)</p>
        </div>
        <div class="top-bar-right">
            <button class="btn btn-success" onclick="exportExcel()">
                <i class="fas fa-file-excel me-2"></i>تصدير Excel
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="stat-card mb-4">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">اختر السيارة</label>
                <select name="car_id" class="form-select" required>
                    <?php foreach ($cars as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo $c['id'] == $selected_car_id ? 'selected' : ''; ?>>
                        <?php echo $c['brand'] . ' ' . $c['model'] . ' (' . $c['plate_number'] . ')'; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">من تاريخ</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">إلى تاريخ</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary d-block w-100">
                    <i class="fas fa-search me-2"></i>عرض
                </button>
            </div>
        </form>
    </div>

    <?php if ($selected_car_id && isset($car)): ?>
    
    <!-- Car Info -->
    <div class="stat-card mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h4 style="color: white; margin-bottom: 10px;">
                    <i class="fas fa-car me-2"></i>
                    <?php echo $car['brand'] . ' ' . $car['model'] . ' ' . $car['year']; ?>
                </h4>
                <p style="margin: 0; opacity: 0.9;">
                    <strong>اللوحة:</strong> <?php echo $car['plate_number']; ?> | 
                    <strong>اللون:</strong> <?php echo $car['color']; ?> |
                    <strong>النوع:</strong> <?php echo $car['type']; ?>
                </p>
            </div>
            <div class="col-md-4 text-end">
                <div style="font-size: 3rem; opacity: 0.3;">🚗</div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(76, 175, 80, 0.1); color: #4CAF50;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-value"><?php echo formatCurrency($total_income); ?></div>
                <div class="stat-label">إجمالي الإيرادات</div>
                <small class="text-muted"><?php echo $income['rental_count'] ?? 0; ?> عملية تأجير</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(244, 67, 54, 0.1); color: #F44336;">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="stat-value"><?php echo formatCurrency($total_cost); ?></div>
                <div class="stat-label">إجمالي المصروفات</div>
                <small class="text-muted">مصروفات + صيانة</small>
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
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-value"><?php echo $income['total_days'] ?? 0; ?></div>
                <div class="stat-label">أيام التأجير</div>
            </div>
        </div>
    </div>

    <!-- Expenses by Category -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div style="font-size: 2.5rem; color: #FF5722; margin-bottom: 10px;">⛽</div>
                <h6>بنزين</h6>
                <div style="font-size: 1.3rem; font-weight: 700; color: #333;">
                    <?php echo formatCurrency($expenses_by_category['fuel']['total_amount'] ?? 0); ?>
                </div>
                <small class="text-muted"><?php echo $expenses_by_category['fuel']['count'] ?? 0; ?> عملية</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div style="font-size: 2.5rem; color: #9C27B0; margin-bottom: 10px;">🔧</div>
                <h6>صيانة</h6>
                <div style="font-size: 1.3rem; font-weight: 700; color: #333;">
                    <?php echo formatCurrency($total_maintenance); ?>
                </div>
                <small class="text-muted"><?php echo count($maintenance_records); ?> عملية</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div style="font-size: 2.5rem; color: #4CAF50; margin-bottom: 10px;">🛡️</div>
                <h6>تأمين</h6>
                <div style="font-size: 1.3rem; font-weight: 700; color: #333;">
                    <?php echo formatCurrency($expenses_by_category['insurance']['total_amount'] ?? 0); ?>
                </div>
                <small class="text-muted"><?php echo $expenses_by_category['insurance']['count'] ?? 0; ?> عملية</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div style="font-size: 2.5rem; color: #607D8B; margin-bottom: 10px;">📝</div>
                <h6>أخرى</h6>
                <div style="font-size: 1.3rem; font-weight: 700; color: #333;">
                    <?php echo formatCurrency($expenses_by_category['other']['total_amount'] ?? 0); ?>
                </div>
                <small class="text-muted"><?php echo $expenses_by_category['other']['count'] ?? 0; ?> عملية</small>
            </div>
        </div>
    </div>

    <!-- Detailed Expenses Table -->
    <?php if (count($detailed_expenses) > 0): ?>
    <div class="table-container mb-4">
        <h5 class="mb-3">
            <i class="fas fa-list text-primary"></i>
            تفاصيل المصروفات (<?php echo count($detailed_expenses); ?>)
        </h5>
        <div class="table-responsive">
            <table class="table table-hover" id="expenses-table">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>النوع</th>
                        <th>الوصف</th>
                        <th>المبلغ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detailed_expenses as $expense): ?>
                    <tr>
                        <td><?php echo formatDate($expense['expense_date']); ?></td>
                        <td>
                            <?php 
                            $icons = ['fuel' => '⛽', 'maintenance' => '🔧', 'insurance' => '🛡️', 'other' => '📝'];
                            echo $icons[$expense['category']] ?? '📝';
                            ?>
                            <?php echo ucfirst($expense['category']); ?>
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

    <!-- Maintenance Records -->
    <?php if (count($maintenance_records) > 0): ?>
    <div class="table-container">
        <h5 class="mb-3">
            <i class="fas fa-tools text-warning"></i>
            سجل الصيانة (<?php echo count($maintenance_records); ?>)
        </h5>
        <div class="table-responsive">
            <table class="table table-hover" id="maintenance-table">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>النوع</th>
                        <th>الوصف</th>
                        <th>التكلفة</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($maintenance_records as $record): ?>
                    <tr>
                        <td><?php echo formatDate($record['maintenance_date']); ?></td>
                        <td><?php echo $record['type']; ?></td>
                        <td><?php echo $record['description']; ?></td>
                        <td><strong style="color: #f44336;"><?php echo formatCurrency($record['cost']); ?></strong></td>
                        <td>
                            <span class="badge bg-<?php echo $record['status'] == 'completed' ? 'success' : 'warning'; ?>">
                                <?php echo $record['status'] == 'completed' ? 'مكتمل' : 'معلق'; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>

</div>

<script>
function exportExcel() {
    const wb = XLSX.utils.book_new();
    
    <?php if (isset($detailed_expenses) && count($detailed_expenses) > 0): ?>
    const expensesTable = document.getElementById('expenses-table');
    const expensesWS = XLSX.utils.table_to_sheet(expensesTable);
    XLSX.utils.book_append_sheet(wb, expensesWS, 'المصروفات');
    <?php endif; ?>
    
    <?php if (isset($maintenance_records) && count($maintenance_records) > 0): ?>
    const maintenanceTable = document.getElementById('maintenance-table');
    const maintenanceWS = XLSX.utils.table_to_sheet(maintenanceTable);
    XLSX.utils.book_append_sheet(wb, maintenanceWS, 'الصيانة');
    <?php endif; ?>
    
    XLSX.writeFile(wb, 'car_expenses_<?php echo $car['plate_number'] ?? 'report'; ?>_<?php echo date('Y-m-d'); ?>.xlsx');
}
</script>

<script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>

<?php include 'includes/footer.php'; ?>