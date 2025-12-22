<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();
$current_page = 'expenses';

// Handle expense operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $data = [
                'expense_type' => $_POST['expense_type'],
                'amount' => (float)$_POST['amount'],
                'description' => sanitizeInput($_POST['description']),
                'expense_date' => $_POST['expense_date'],
                'payment_method' => $_POST['payment_method'],
                'reference_number' => sanitizeInput($_POST['reference_number']),
                'notes' => sanitizeInput($_POST['notes']),
                'created_by' => $_SESSION['user_id']
            ];

            try {
                $stmt = $db->prepare("
                    INSERT INTO expenses (expense_type, amount, description, expense_date, 
                                        payment_method, reference_number, notes, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $data['expense_type'], $data['amount'], $data['description'],
                    $data['expense_date'], $data['payment_method'], $data['reference_number'],
                    $data['notes'], $data['created_by']
                ]);
                
                logActivity($_SESSION['user_id'], 'expense_add', 'تم إضافة مصروف: ' . $data['expense_type']);
                $_SESSION['success'] = 'تم إضافة المصروف بنجاح';
                redirect('expenses.php');
            } catch (Exception $e) {
                $error = 'خطأ في إضافة المصروف: ' . $e->getMessage();
            }
        }
    }
}

// Get expenses
try {
    $stmt = $db->query("
        SELECT e.*, u.full_name as created_by_name
        FROM expenses e
        LEFT JOIN users u ON e.created_by = u.id
        ORDER BY e.expense_date DESC, e.created_at DESC
    ");
    $expenses = $stmt->fetchAll();
    
    // Calculate totals
    $total_expenses = array_sum(array_column($expenses, 'amount'));
} catch (Exception $e) {
    $error = 'خطأ في جلب البيانات: ' . $e->getMessage();
    $expenses = [];
    $total_expenses = 0;
}

$page_title = 'إدارة المصروفات - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-receipt me-2"></i>إدارة المصروفات</h5>
            <p>تسجيل ومتابعة جميع المصروفات</p>
        </div>
        <div class="top-bar-right">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                <i class="fas fa-plus me-2"></i>إضافة مصروف
            </button>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-danger">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-content">
                    <h6>إجمالي المصروفات</h6>
                    <h3><?php echo formatCurrency($total_expenses); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-info">
                    <i class="fas fa-list"></i>
                </div>
                <div class="stat-content">
                    <h6>عدد المصروفات</h6>
                    <h3><?php echo count($expenses); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-warning">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-content">
                    <h6>مصروفات هذا الشهر</h6>
                    <h3><?php 
                        $month_expenses = array_filter($expenses, function($e) {
                            return date('Y-m', strtotime($e['expense_date'])) == date('Y-m');
                        });
                        echo formatCurrency(array_sum(array_column($month_expenses, 'amount')));
                    ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="table-container">
        <div class="table-header">
            <h5>قائمة المصروفات</h5>
            <input type="text" id="searchInput" class="form-control" style="max-width: 300px;" placeholder="بحث...">
        </div>

        <table class="table table-hover" id="expensesTable">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>النوع</th>
                    <th>الوصف</th>
                    <th>المبلغ</th>
                    <th>طريقة الدفع</th>
                    <th>الرقم المرجعي</th>
                    <th>أضيف بواسطة</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($expenses) > 0): ?>
                    <?php foreach ($expenses as $expense): ?>
                    <tr>
                        <td><?php echo formatDate($expense['expense_date']); ?></td>
                        <td>
                            <span class="badge bg-secondary"><?php echo EXPENSE_TYPES[$expense['expense_type']]; ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($expense['description']); ?></td>
                        <td><strong class="text-danger"><?php echo formatCurrency($expense['amount']); ?></strong></td>
                        <td><?php echo PAYMENT_METHODS[$expense['payment_method']]; ?></td>
                        <td><?php echo htmlspecialchars($expense['reference_number']) ?: '-'; ?></td>
                        <td><?php echo $expense['created_by_name']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p>لا توجد مصروفات</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة مصروف جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">نوع المصروف *</label>
                        <select name="expense_type" class="form-control" required>
                            <?php foreach (EXPENSE_TYPES as $key => $value): ?>
                            <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">المبلغ (₪) *</label>
                        <input type="number" name="amount" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الوصف *</label>
                        <input type="text" name="description" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">التاريخ *</label>
                        <input type="date" name="expense_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">طريقة الدفع *</label>
                        <select name="payment_method" class="form-control" required>
                            <?php foreach (PAYMENT_METHODS as $key => $value): ?>
                            <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الرقم المرجعي</label>
                        <input type="text" name="reference_number" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    let filter = this.value.toUpperCase();
    let table = document.getElementById('expensesTable');
    let tr = table.getElementsByTagName('tr');
    
    for (let i = 1; i < tr.length; i++) {
        let td = tr[i].getElementsByTagName('td');
        let found = false;
        for (let j = 0; j < td.length; j++) {
            if (td[j] && td[j].textContent.toUpperCase().indexOf(filter) > -1) {
                found = true;
                break;
            }
        }
        tr[i].style.display = found ? '' : 'none';
    }
});
</script>

<?php include 'includes/footer.php'; ?>