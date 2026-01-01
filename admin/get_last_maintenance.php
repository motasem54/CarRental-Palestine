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
    $maintenance = $stmt->fetch();
    
    if ($maintenance) {
        // Format date
        $maintenance['maintenance_date'] = formatDate($maintenance['maintenance_date']);
        
        echo json_encode([
            'success' => true,
            'maintenance' => $maintenance
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No maintenance found'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>