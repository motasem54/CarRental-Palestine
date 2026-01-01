<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

// Disable error display, only log
error_reporting(E_ALL);
ini_set('display_errors', 0);

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

try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT 
            maintenance_type,
            description,
            maintenance_date,
            cost,
            status
        FROM maintenance
        WHERE car_id = ?
        ORDER BY maintenance_date DESC, id DESC
        LIMIT 1
    ");
    
    $stmt->execute([$car_id]);
    $maintenance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($maintenance && is_array($maintenance)) {
        // Format date
        try {
            $date = new DateTime($maintenance['maintenance_date']);
            $maintenance['maintenance_date'] = $date->format('d/m/Y');
        } catch (Exception $e) {
            $maintenance['maintenance_date'] = $maintenance['maintenance_date'];
        }
        
        // Format cost
        $maintenance['cost'] = number_format((float)$maintenance['cost'], 2);
        
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
    
} catch (PDOException $e) {
    error_log('Database error in get_last_maintenance.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => true,
        'message' => 'خطأ في قاعدة البيانات'
    ]);
} catch (Exception $e) {
    error_log('Error in get_last_maintenance.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => true,
        'message' => 'حدث خطأ غير متوقع'
    ]);
}
?>