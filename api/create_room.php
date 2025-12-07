<?php
/**
 * Create room API endpoint
 * Creates a new game room (public or private)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$userId = getCurrentUserId();
$isPrivate = isset($input['is_private']) && $input['is_private'] ? 1 : 0;

// Generate unique room code
do {
    $code = generateRoomCode(6);
    $stmt = executeQuery("SELECT id FROM rooms WHERE code = ?", 's', [$code]);
    $result = $stmt ? $stmt->get_result() : null;
    $exists = $result && $result->num_rows > 0;
    if ($stmt) $stmt->close();
} while ($exists);

// Create room
$stmt = executeQuery(
    "INSERT INTO rooms (code, is_private, host_user_id, max_players) VALUES (?, ?, ?, ?)",
    'siii',
    [$code, $isPrivate, $userId, MAX_PLAYERS_PER_ROOM]
);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to create room']);
    exit();
}

$roomId = $stmt->insert_id;
$stmt->close();

// Add host to room
$stmt = executeQuery(
    "INSERT INTO room_players (room_id, user_id, is_host) VALUES (?, ?, 1)",
    'ii',
    [$roomId, $userId]
);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to join room']);
    exit();
}

echo json_encode([
    'success' => true,
    'room_id' => $roomId,
    'code' => $code,
    'message' => 'Room created successfully'
]);
?>
