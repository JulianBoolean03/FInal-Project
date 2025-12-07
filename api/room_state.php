<?php
/**
 * Room state API endpoint
 * Returns room information and player lists
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

requireAuth();

$userId = getCurrentUserId();
$action = $_GET['action'] ?? 'room_info';
$roomId = $_GET['room_id'] ?? null;

if ($action === 'list_public') {
    // List all public rooms that are waiting for players
    $stmt = executeQuery(
        "SELECT r.id, r.code, r.max_players, r.status, COUNT(rp.id) as player_count
         FROM rooms r
         LEFT JOIN room_players rp ON r.id = rp.room_id
         WHERE r.is_private = 0 AND r.status = 'waiting'
         GROUP BY r.id
         HAVING player_count < r.max_players
         ORDER BY r.created_at DESC",
        '',
        []
    );
    
    $rooms = [];
    if ($stmt) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $rooms[] = $row;
        }
        $stmt->close();
    }
    
    echo json_encode(['success' => true, 'rooms' => $rooms]);
    exit();
}

if (!$roomId) {
    echo json_encode(['success' => false, 'message' => 'Room ID required']);
    exit();
}

// Get room details
$stmt = executeQuery(
    "SELECT r.*, 
            (SELECT COUNT(*) FROM room_players WHERE room_id = r.id) as player_count,
            (SELECT is_host FROM room_players WHERE room_id = r.id AND user_id = ?) as is_host
     FROM rooms r
     WHERE r.id = ?",
    'ii',
    [$userId, $roomId]
);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Room not found']);
    exit();
}

$result = $stmt->get_result();
$room = $result->fetch_assoc();
$stmt->close();

if (!$room) {
    echo json_encode(['success' => false, 'message' => 'Room not found']);
    exit();
}

// Get players in room
$stmt = executeQuery(
    "SELECT u.id, u.username, rp.is_host, rp.is_ready
     FROM room_players rp
     JOIN users u ON rp.user_id = u.id
     WHERE rp.room_id = ?
     ORDER BY rp.is_host DESC, rp.joined_at ASC",
    'i',
    [$roomId]
);

$players = [];
if ($stmt) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $players[] = $row;
    }
    $stmt->close();
}

$room['players'] = $players;
$room['is_host'] = (bool)$room['is_host'];

echo json_encode(['success' => true, 'room' => $room]);
?>
