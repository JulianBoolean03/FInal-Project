<?php
/**
 * Start game API endpoint
 * Host initiates game start, creates game record
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$userId = getCurrentUserId();
$roomId = $input['room_id'] ?? null;

if (!$roomId) {
    echo json_encode(['success' => false, 'message' => 'Room ID required']);
    exit();
}

// Verify user is host
if (!isRoomHost($userId, $roomId)) {
    echo json_encode(['success' => false, 'message' => 'Only host can start game']);
    exit();
}

// Get room info
$stmt = executeQuery("SELECT current_round, status FROM rooms WHERE id = ?", 'i', [$roomId]);
$result = $stmt->get_result();
$room = $result->fetch_assoc();
$stmt->close();

if ($room['status'] !== 'waiting') {
    echo json_encode(['success' => false, 'message' => 'Game already started']);
    exit();
}

$roundNumber = $room['current_round'] + 1;
$storyId = ($roundNumber <= 5) ? $roundNumber : (($roundNumber - 1) % 5) + 1;

// Update room status
executeQuery("UPDATE rooms SET status = 'starting', current_round = ? WHERE id = ?", 'ii', [$roundNumber, $roomId]);

// Create game record
$stmt = executeQuery(
    "INSERT INTO games (room_id, round_number, story_segment_id) VALUES (?, ?, ?)",
    'iii',
    [$roomId, $roundNumber, $storyId]
);
$gameId = $stmt->insert_id;
$stmt->close();

// Get all players in room
$stmt = executeQuery("SELECT user_id FROM room_players WHERE room_id = ?", 'i', [$roomId]);
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    executeQuery("INSERT INTO moves (game_id, user_id) VALUES (?, ?)", 'ii', [$gameId, $row['user_id']]);
}
$stmt->close();

// Update room to in_progress
executeQuery("UPDATE rooms SET status = 'in_progress' WHERE id = ?", 'i', [$roomId]);

echo json_encode(['success' => true, 'game_id' => $gameId, 'message' => 'Game starting']);
?>
