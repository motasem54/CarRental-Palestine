<?php
require_once '../../config/settings.php';
require_once '../../core/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح بالدخول']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طلب غير صالح']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Validate required fields
    $requiredFields = ['full_name', 'phone', 'id_number'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => 'يرجى ملء جميع الحقول المطلوبة']);
            exit;
        }
    }
    
    // Check if customer already exists
    $checkStmt = $db->prepare("SELECT id FROM customers WHERE id_number = ? OR phone = ?");
    $checkStmt->execute([$_POST['id_number'], $_POST['phone']]);
    
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'العميل موجود مسبقاً']);
        exit;
    }
    
    // Insert new customer
    $stmt = $db->prepare("
        INSERT INTO customers (
            full_name, phone, id_number, driver_license, 
            address, email, status, created_by, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'active', ?, NOW())
    ");
    
    $stmt->execute([
        sanitizeInput($_POST['full_name']),
        sanitizeInput($_POST['phone']),
        sanitizeInput($_POST['id_number']),
        sanitizeInput($_POST['driver_license'] ?? ''),
        sanitizeInput($_POST['address'] ?? ''),
        sanitizeInput($_POST['email'] ?? ''),
        $_SESSION['user_id']
    ]);
    
    $customerId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'customer_id' => $customerId,
        'message' => 'تم إضافة العميل بنجاح'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ: ' . $e->getMessage()
    ]);
}
?>