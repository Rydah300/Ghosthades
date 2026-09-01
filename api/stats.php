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

$stmt = $db->prepare("SELECT SUM(total) FROM extractions WHERE user_id = ? AND status != 'failed'");
$stmt->execute([$user_id]);
$total = $stmt->fetchColumn() ?: 0;

$stmt = $db->prepare("SELECT SUM(total) FROM extractions WHERE user_id = ? AND DATE(created_at) = DATE('now') AND status != 'failed'");
$stmt->execute([$user_id]);
$today = $stmt->fetchColumn() ?: 0;

$stmt = $db->prepare("SELECT COUNT(*) FROM saved_results WHERE user_id = ?");
$stmt->execute([$user_id]);
$saved = $stmt->fetchColumn() ?: 0;

$limits = checkUserLimits($user_id);

echo json_encode([
    'total' => $total,
    'today' => $today,
    'saved' => $saved,
    'remaining' => $limits['total_remaining']
]);
?>
