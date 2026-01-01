<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$car_id = isset($_GET['car_id']) ? (int)$_GET['car_id'] : 0;

if ($car_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid car ID']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    $stmt = $db->prepare("
        SELECT 
            maintenance_type,
            description,
            maintenance_date,
            cost,
            status
        FROM maintenance
        WHERE car_id = ?
        ORDER BY maintenance_date DESC, created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$car_id]);
    $maintenance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($maintenance) {
        // Format date to Arabic format
        $date = new DateTime($maintenance['maintenance_date']);
        $maintenance['maintenance_date'] = $date->format('d/m/Y');
        
        // Format cost
        $maintenance['cost'] = number_format($maintenance['cost'], 2);
        
        echo json_encode([
            'success' => true,
            'maintenance' => $maintenance
        ]);
    } else {
        // لا توجد صيانة سابقة
        echo json_encode([
            'success' => true,
            'maintenance' => null,
            'message' => 'لا توجد صيانة سابقة لهذه السيارة'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => true,
        'message' => 'خطأ في تحميل البيانات: ' . $e->getMessage()
    ]);
}
?>