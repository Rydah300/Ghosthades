<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $input['action'] ?? '';

if ($action === 'redeem') {
    $code = trim($input['code'] ?? '');
    if (!$code) {
        echo json_encode(['success' => false, 'message' => 'License code required.']);
        exit;
    }
    $result = redeemLicense($user_id, $code);
    $limits = checkUserLimits($user_id);
    $result['remaining'] = $limits['total_remaining'];
    echo json_encode($result);
    
} elseif ($action === 'list') {
    requireAdmin();
    $stmt = $db->prepare("SELECT l.*, u.username as redeemed_by FROM licenses l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC");
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    
} elseif ($action === 'generate') {
    requireAdmin();
    $limit_amount = (int)($input['limit_amount'] ?? 100);
    $user_id_target = $input['user_id'] ?? null;
    $expiry_days = $input['expiry_days'] ?? null;
    $count = (int)($input['count'] ?? 1);
    
    if ($count > 1) {
        $codes = generateBulkLicenses($_SESSION['user_id'], $limit_amount, $count, $expiry_days);
        echo json_encode(['status' => 'ok', 'codes' => $codes, 'count' => count($codes)]);
    } else {
        $code = generateLicense($_SESSION['user_id'], $limit_amount, $user_id_target, $expiry_days);
        echo json_encode(['status' => 'ok', 'code' => $code]);
    }
    
} else {
    http_response_code(400);
    echo json_encode(['error' => 'invalid action']);
}
?>
