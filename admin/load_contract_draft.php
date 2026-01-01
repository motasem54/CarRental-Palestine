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
    $rental_id = (int)($_GET['rental_id'] ?? 0);
    
    if (!$rental_id) {
        throw new Exception('Rental ID missing');
    }
    
    // Check if draft exists
    $stmt = $db->prepare("SELECT * FROM contract_drafts WHERE rental_id = ? LIMIT 1");
    $stmt->execute([$rental_id]);
    $draft = $stmt->fetch();
    
    if (!$draft) {
        echo json_encode([
            'success' => false,
            'message' => 'No draft found',
            'has_draft' => false
        ]);
        exit;
    }
    
    // Convert binary signatures to base64 for display
    $result = [
        'success' => true,
        'has_draft' => true,
        'data' => [
            'inspection_notes' => $draft['inspection_notes'] ?? '',
            'has_promissory' => (bool)$draft['has_promissory'],
            'signatures' => []
        ]
    ];
    
    // Add signatures if they exist
    if ($draft['customer_signature']) {
        $result['data']['signatures']['customerSignature'] = 'data:image/png;base64,' . base64_encode($draft['customer_signature']);
    }
    if ($draft['company_signature']) {
        $result['data']['signatures']['companySignature'] = 'data:image/png;base64,' . base64_encode($draft['company_signature']);
    }
    if ($draft['promissory_signature']) {
        $result['data']['signatures']['promissorySignature'] = 'data:image/png;base64,' . base64_encode($draft['promissory_signature']);
    }
    if ($draft['car_front']) {
        $result['data']['signatures']['carFront'] = 'data:image/png;base64,' . base64_encode($draft['car_front']);
    }
    if ($draft['car_back']) {
        $result['data']['signatures']['carBack'] = 'data:image/png;base64,' . base64_encode($draft['car_back']);
    }
    if ($draft['car_left']) {
        $result['data']['signatures']['carLeft'] = 'data:image/png;base64,' . base64_encode($draft['car_left']);
    }
    if ($draft['car_right']) {
        $result['data']['signatures']['carRight'] = 'data:image/png;base64,' . base64_encode($draft['car_right']);
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
