<?php
/**
 * Authentication helper functions
 * Handles user sessions, login, and access control
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Start session if not already started
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Check if user is logged in
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require authentication - redirect to login if not authenticated
 */
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: /index.php?error=auth_required');
        exit();
    }
}

/**
 * Get current user ID
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId() {
    startSession();
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current username
 * @return string|null Username or null if not logged in
 */
function getCurrentUsername() {
    startSession();
    return $_SESSION['username'] ?? null;
}

/**
 * Login user and create session
 * @param int $userId User ID
 * @param string $username Username
 * @param bool $remember Whether to set remember-me cookie
 */
function loginUser($userId, $username, $remember = false) {
    startSession();
    
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['login_time'] = time();
    
    // Set remember-me cookie if requested
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        setcookie('remember_token', $token, time() + COOKIE_LIFETIME, '/', '', false, true);
        
        // Store hashed token in database (you would need a tokens table for production)
        // For this project, we'll use a simpler approach with username
        setcookie('remember_user', $username, time() + COOKIE_LIFETIME, '/', '', false, true);
    }
}

/**
 * Logout user and destroy session
 */
function logoutUser() {
    startSession();
    
    // Clear session variables
    $_SESSION = [];
    
    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Clear remember-me cookies
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
    if (isset($_COOKIE['remember_user'])) {
        setcookie('remember_user', '', time() - 3600, '/');
    }
    
    session_destroy();
}

/**
 * Check if user owns a room (is host)
 * @param int $userId User ID
 * @param int $roomId Room ID
 * @return bool True if user is host
 */
function isRoomHost($userId, $roomId) {
    $stmt = executeQuery(
    "SELECT is_host FROM room_players WHERE user_id = ? AND room_id = ?",
    '',
        [$userId, $roomId]
    );
    
    if (!$stmt) return false;
    
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    
    return $row && $row['is_host'] == 1;
}

/**
 * Check if user is in a room
 * @param int $userId User ID
 * @param int $roomId Room ID
 * @return bool True if user is in room
 */
function isInRoom($userId, $roomId) {
    $stmt = executeQuery(
    "SELECT id FROM room_players WHERE user_id = ? AND room_id = ?",
    '',
        [$userId, $roomId]
    );
    
    if (!$stmt) return false;
    
    // Use fetch() instead of rowCount() for SQLite compatibility
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $row !== false;
}

/**
 * Update user's last active timestamp
 * @param int $userId User ID
 * @param int $roomId Room ID
 */
function updateLastActive($userId, $roomId) {
    executeQuery(
    "UPDATE room_players SET last_active_at = CURRENT_TIMESTAMP WHERE user_id = ? AND room_id = ?",
    '',
        [$userId, $roomId]
    );
}

/**
 * Get user's current room ID
 * @param int $userId User ID
 * @return int|null Room ID or null if not in a room
 */
function getCurrentRoomId($userId) {
    $stmt = executeQuery(
    "SELECT room_id FROM room_players WHERE user_id = ? ORDER BY joined_at DESC LIMIT 1",
    '',
        [$userId]
    );
    
    if (!$stmt) return null;
    
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    
    return $row ? $row['room_id'] : null;
}

/**
 * Validate username format
 * @param string $username Username to validate
 * @return bool True if valid
 */
function isValidUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

/**
 * Validate password format
 * @param string $password Password to validate
 * @return bool True if valid
 */
function isValidPassword($password) {
    return strlen($password) >= 6;
}
?>
