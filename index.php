<?php
// ============================================
// ROUTER — Handles all requests
// ============================================

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Get the request path
$path = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($path, PHP_URL_PATH);
$path = rtrim($path, '/');
if (empty($path)) $path = '/';

// ============================================
// ROUTE DEFINITIONS
// ============================================

// --- API Routes ---
if (strpos($path, '/api/') === 0) {
    $apiFile = 'api/' . substr($path, 5) . '.php';
    if (file_exists($apiFile)) {
        require_once $apiFile;
        exit;
    }
    http_response_code(404);
    echo json_encode(['error' => 'API endpoint not found']);
    exit;
}

// --- Page Routes ---
switch ($path) {
    case '/':
    case '/login':
        require_once 'pages/login.php';
        break;
        
    case '/dashboard':
        require_once 'pages/dashboard.php';
        break;
        
    case '/admin':
        require_once 'pages/admin.php';
        break;
        
    case '/logout':
        require_once 'pages/logout.php';
        break;
        
    default:
        http_response_code(404);
        echo '<h1>404 Not Found</h1>';
        break;
}
?>
