<?php
/**
 * Login handler
 * Processes user login attempts
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

startSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']) && $_POST['remember'] == '1';

// Fetch user from database
$stmt = executeQuery(
    "SELECT id, username, password_hash FROM users WHERE username = ?",
    '',
    [$username]
);

if (!$stmt) {
    die("Database query failed");
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found: " . htmlspecialchars($username));
}

// Verify password is correct
if (!password_verify($password, $user['password_hash'])) {
    die("Password verification failed");
}

// Login successful - create session
loginUser($user['id'], $user['username'], $remember);

// Redirect to lobby
header('Location: lobby.php');
exit();
?>
