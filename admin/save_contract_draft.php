<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $rental_id = (int)($input['rental_id'] ?? 0);
    $inspection_notes = trim($input['inspection_notes'] ?? '');
    $signatures = $input['signatures'] ?? [];
    $has_promissory = (bool)($input['has_promissory'] ?? false);
    
    if (!$rental_id) {
        throw new Exception('Rental ID missing');
    }
    
    // Create contract_drafts table if not exists
    $db->exec("
        CREATE TABLE IF NOT EXISTS contract_drafts (
            id INT PRIMARY KEY AUTO_INCREMENT,
            rental_id INT NOT NULL UNIQUE,
            inspection_notes LONGTEXT,
            has_promissory TINYINT DEFAULT 0,
            customer_signature LONGBLOB,
            company_signature LONGBLOB,
            promissory_signature LONGBLOB,
            car_front LONGBLOB,
            car_back LONGBLOB,
            car_left LONGBLOB,
            car_right LONGBLOB,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (rental_id) REFERENCES rentals(id) ON DELETE CASCADE
        )
    ");
    
    // Decode base64 signatures
    $customer_sig = isset($signatures['customerSignature']) ? base64_decode(str_replace('data:image/png;base64,', '', $signatures['customerSignature'])) : null;
    $company_sig = isset($signatures['companySignature']) ? base64_decode(str_replace('data:image/png;base64,', '', $signatures['companySignature'])) : null;
    $promissory_sig = isset($signatures['promissorySignature']) ? base64_decode(str_replace('data:image/png;base64,', '', $signatures['promissorySignature'])) : null;
    $car_front = isset($signatures['carFront']) ? base64_decode(str_replace('data:image/png;base64,', '', $signatures['carFront'])) : null;
    $car_back = isset($signatures['carBack']) ? base64_decode(str_replace('data:image/png;base64,', '', $signatures['carBack'])) : null;
    $car_left = isset($signatures['carLeft']) ? base64_decode(str_replace('data:image/png;base64,', '', $signatures['carLeft'])) : null;
    $car_right = isset($signatures['carRight']) ? base64_decode(str_replace('data:image/png;base64,', '', $signatures['carRight'])) : null;
    
    // Insert or update draft
    $stmt = $db->prepare("
        INSERT INTO contract_drafts 
        (rental_id, inspection_notes, has_promissory, customer_signature, company_signature, promissory_signature, car_front, car_back, car_left, car_right)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        inspection_notes = VALUES(inspection_notes),
        has_promissory = VALUES(has_promissory),
        customer_signature = COALESCE(VALUES(customer_signature), customer_signature),
        company_signature = COALESCE(VALUES(company_signature), company_signature),
        promissory_signature = COALESCE(VALUES(promissory_signature), promissory_signature),
        car_front = COALESCE(VALUES(car_front), car_front),
        car_back = COALESCE(VALUES(car_back), car_back),
        car_left = COALESCE(VALUES(car_left), car_left),
        car_right = COALESCE(VALUES(car_right), car_right),
        updated_at = NOW()
    ");
    
    $stmt->execute([
        $rental_id,
        $inspection_notes,
        $has_promissory ? 1 : 0,
        $customer_sig,
        $company_sig,
        $promissory_sig,
        $car_front,
        $car_back,
        $car_left,
        $car_right
    ]);
    
    // Log the action
    $log_stmt = $db->prepare("
        INSERT INTO activity_logs (user_id, action, description, table_name, record_id, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $log_stmt->execute([
        $_SESSION['user_id'] ?? 1,
        'save_contract_draft',
        'Saved contract draft for rental',
        'rentals',
        $rental_id
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Contract draft saved successfully',
        'draft_id' => $db->lastInsertId()
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
