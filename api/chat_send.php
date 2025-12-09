<?php
/**
 * Chat send API endpoint
 * Sends a chat message to a room
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$userId = getCurrentUserId();
$roomId = $input['room_id'] ?? null;
$message = trim($input['message'] ?? '');

if (!$roomId || !isInRoom($userId, $roomId)) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

if (empty($message) || strlen($message) > 200) {
    echo json_encode(['success' => false, 'message' => 'Invalid message']);
    exit();
}

$stmt = executeQuery(
    "INSERT INTO chat_messages (room_id, user_id, message) VALUES (?, ?, ?)",
    '',
    [$roomId, $userId, $message]
);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to send message']);
    exit();
}

echo json_encode(['success' => true, 'message_id' => getDB()->lastInsertId()]);
?>
