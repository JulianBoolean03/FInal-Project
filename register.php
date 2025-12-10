<?php
/**
 * Registration handler
 * Processes new user account creation
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
$password_confirm = $_POST['password_confirm'] ?? '';

// Validate username format
if (!isValidUsername($username)) {
    header('Location: index.php?error=invalid_username');
    exit();
}

// Validate password
if (!isValidPassword($password)) {
    header('Location: index.php?error=invalid_password');
    exit();
}

// Check passwords match
if ($password !== $password_confirm) {
    header('Location: index.php?error=passwords_mismatch');
    exit();
}

// Check if username already exists
$stmt = executeQuery(
    "SELECT id FROM users WHERE username = ?",
    '',
    [$username]
);

if ($stmt) {
    if ($stmt->fetch()) {
        header('Location: index.php?error=username_taken');
        exit();
    }
}

// Hash password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Insert new user
$stmt = executeQuery(
    "INSERT INTO users (username, password_hash) VALUES (?, ?)",
    '',
    [$username, $password_hash]
);

if ($stmt) {
    $db = getDB();
    $userId = $db->lastInsertId();
    
    // Verify user was created
    if ($userId > 0) {
        // Create analytics record for new user
        executeQuery(
            "INSERT INTO analytics (user_id) VALUES (?)",
            '',
            [$userId]
        );
        
        // Registration successful
        error_log("User registered successfully: $username (ID: $userId)");
        header('Location: index.php?success=registered');
        exit();
    } else {
        error_log("User insert failed - no ID returned");
        header('Location: index.php?error=registration_failed');
        exit();
    }
} else {
    error_log("User insert failed - query returned false");
    header('Location: index.php?error=registration_failed');
    exit();
}
?>
