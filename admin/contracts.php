<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();
$current_page = 'contracts';

// Get all contracts with rental and customer info
try {
    $stmt = $db->query("
        SELECT r.*, 
               c.full_name as customer_name, 
               car.brand, car.model, car.plate_number,
               u.full_name as created_by_name
        FROM rentals r
        JOIN customers c ON r.customer_id = c.id
        JOIN cars car ON r.car_id = car.id
        LEFT JOIN users u ON r.created_by = u.id
        WHERE r.status IN ('confirmed', 'active', 'completed')
        ORDER BY r.created_at DESC
    ");
    $contracts = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'خطأ في جلب البيانات: ' . $e->getMessage();
    $contracts = [];
}

$page_title = 'إدارة العقود - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-file-contract me-2"></i>إدارة العقود</h5>
            <p>عرض وطباعة عقود التأجير</p>
        </div>
        <div class="top-bar-right">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print me-2"></i>طباعة الكل
            </button>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="table-container">
        <div class="table-header">
            <h5>قائمة العقود (<?php echo count($contracts); ?>)</h5>
            <div class="search-box">
                <input type="text" id="searchInput" class="form-control" placeholder="بحث في العقود...">
            </div>
        </div>

        <table class="table table-hover" id="contractsTable">
            <thead>
                <tr>
                    <th>رقم العقد</th>
                    <th>العميل</th>
                    <th>السيارة</th>
                    <th>تاريخ البدء</th>
                    <th>تاريخ الانتهاء</th>
                    <th>الأيام</th>
                    <th>المبلغ الكلي</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($contracts) > 0): ?>
                    <?php foreach ($contracts as $contract): ?>
                    <tr>
                        <td><strong><?php echo $contract['rental_number']; ?></strong></td>
                        <td><?php echo htmlspecialchars($contract['customer_name']); ?></td>
                        <td><?php echo $contract['brand'] . ' ' . $contract['model']; ?><br>
                            <small class="text-muted"><?php echo $contract['plate_number']; ?></small>
                        </td>
                        <td><?php echo formatDate($contract['start_date']); ?></td>
                        <td><?php echo formatDate($contract['end_date']); ?></td>
                        <td><?php echo $contract['total_days']; ?> يوم</td>
                        <td><strong><?php echo formatCurrency($contract['total_amount']); ?></strong></td>
                        <td><?php echo getRentalStatusBadge($contract['status']); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="rental_view.php?id=<?php echo $contract['id']; ?>" 
                                   class="btn btn-sm btn-info" title="عرض">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="contract_print.php?id=<?php echo $contract['id']; ?>" 
                                   class="btn btn-sm btn-primary" title="طباعة" target="_blank">
                                    <i class="fas fa-print"></i>
                                </a>
                                <a href="contract_pdf.php?id=<?php echo $contract['id']; ?>" 
                                   class="btn btn-sm btn-danger" title="PDF" target="_blank">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p>لا توجد عقود</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    let filter = this.value.toUpperCase();
    let table = document.getElementById('contractsTable');
    let tr = table.getElementsByTagName('tr');
    
    for (let i = 1; i < tr.length; i++) {
        let td = tr[i].getElementsByTagName('td');
        let found = false;
        
        for (let j = 0; j < td.length; j++) {
            if (td[j]) {
                let txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        tr[i].style.display = found ? '' : 'none';
    }
});
</script>

<?php include 'includes/footer.php'; ?>