<?php
/**
 * Login handler
 * Processes user login attempts
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

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
    header('Location: index.php?error=invalid_credentials');
    exit();
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Verify user exists and password is correct
if (!$user || !password_verify($password, $user['password_hash'])) {
    header('Location: index.php?error=invalid_credentials');
    exit();
}

// Login successful - create session
loginUser($user['id'], $user['username'], $remember);

// Redirect to lobby
header('Location: lobby.php');
exit();
?>
