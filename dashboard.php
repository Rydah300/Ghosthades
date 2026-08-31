<?php
session_start();

// Debug: show session
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (!isset($_SESSION['user_id'])) {
    echo "Not logged in! Redirecting...";
    // header('Location: /');
    exit;
}

echo "Welcome " . htmlspecialchars($_SESSION['username']) . "!";
?>
