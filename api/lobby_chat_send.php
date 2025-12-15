<?php
/**
 * Lobby Chat send API endpoint
 * Sends a chat message to the global lobby (room_id = -1)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$userId = getCurrentUserId();
$message = trim($input['message'] ?? '');

if (empty($message) || strlen($message) > 200) {
    echo json_encode(['success' => false, 'message' => 'Invalid message (max 200 chars)']);
    exit();
}

// Use room_id = -1 for global lobby chat
$stmt = executeQuery(
    "INSERT INTO chat_messages (room_id, user_id, message) VALUES (?, ?, ?)",
    '',
    [-1, $userId, $message]
);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to send message']);
    exit();
}

echo json_encode(['success' => true, 'message_id' => getDB()->lastInsertId()]);
?>
