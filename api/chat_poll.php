<?php
/**
 * Chat poll API endpoint
 * Returns recent chat messages for a room
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
requireAuth();

$roomId = $_GET['room_id'] ?? null;
$userId = getCurrentUserId();

if (!$roomId || !isInRoom($userId, $roomId)) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$stmt = executeQuery(
    "SELECT cm.id, cm.message, cm.created_at, u.username
     FROM chat_messages cm
     JOIN users u ON cm.user_id = u.id
     WHERE cm.room_id = ?
     ORDER BY cm.created_at DESC
     LIMIT 50",
    'i',
    [$roomId]
);

$messages = [];
if ($stmt) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'id' => $row['id'],
            'username' => $row['username'],
            'message' => $row['message'],
            'time' => date('g:i A', strtotime($row['created_at']))
        ];
    }
    $stmt->close();
}

echo json_encode(['success' => true, 'messages' => array_reverse($messages)]);
?>
