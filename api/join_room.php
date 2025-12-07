<?php
/**
 * Join/Leave room API endpoint
 * Handles joining public/private rooms and leaving rooms
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$userId = getCurrentUserId();
$action = $input['action'] ?? 'join';

// Handle leave action
if ($action === 'leave') {
    $stmt = executeQuery(
        "DELETE FROM room_players WHERE user_id = ?",
        'i',
        [$userId]
    );
    
    echo json_encode(['success' => true, 'message' => 'Left room']);
    exit();
}

// Handle join action
$type = $input['type'] ?? null;
$roomId = $input['room_id'] ?? null;
$code = $input['code'] ?? null;

// Find room to join
if ($type === 'public') {
    // Find an available public room or create one
    $stmt = executeQuery(
        "SELECT r.id FROM rooms r
         WHERE r.is_private = 0 AND r.status = 'waiting'
         AND (SELECT COUNT(*) FROM room_players WHERE room_id = r.id) < r.max_players
         ORDER BY r.created_at DESC
         LIMIT 1",
        '',
        []
    );
    
    if ($stmt) {
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $roomId = $row['id'];
        }
        $stmt->close();
    }
    
    // Create new public room if none available
    if (!$roomId) {
        do {
            $newCode = generateRoomCode(6);
            $checkStmt = executeQuery("SELECT id FROM rooms WHERE code = ?", 's', [$newCode]);
            $result = $checkStmt ? $checkStmt->get_result() : null;
            $exists = $result && $result->num_rows > 0;
            if ($checkStmt) $checkStmt->close();
        } while ($exists);
        
        $stmt = executeQuery(
            "INSERT INTO rooms (code, is_private, host_user_id, max_players) VALUES (?, 0, ?, ?)",
            'sii',
            [$newCode, $userId, MAX_PLAYERS_PER_ROOM]
        );
        
        if ($stmt) {
            $roomId = $stmt->insert_id;
            $stmt->close();
        }
    }
} elseif ($type === 'private' && $code) {
    // Join private room by code
    $stmt = executeQuery(
        "SELECT id FROM rooms WHERE code = ? AND is_private = 1 AND status = 'waiting'",
        's',
        [$code]
    );
    
    if ($stmt) {
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $roomId = $row['id'];
        }
        $stmt->close();
    }
    
    if (!$roomId) {
        echo json_encode(['success' => false, 'message' => 'Invalid room code']);
        exit();
    }
} elseif ($roomId) {
    // Join specific room by ID
    $stmt = executeQuery(
        "SELECT id FROM rooms WHERE id = ? AND status = 'waiting'",
        'i',
        [$roomId]
    );
    
    if ($stmt) {
        $result = $stmt->get_result();
        if (!$result->fetch_assoc()) {
            $roomId = null;
        }
        $stmt->close();
    }
}

if (!$roomId) {
    echo json_encode(['success' => false, 'message' => 'No room available']);
    exit();
}

// Check if room is full
$stmt = executeQuery(
    "SELECT r.max_players, COUNT(rp.id) as player_count
     FROM rooms r
     LEFT JOIN room_players rp ON r.id = rp.room_id
     WHERE r.id = ?
     GROUP BY r.id",
    'i',
    [$roomId]
);

if ($stmt) {
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if ($row['player_count'] >= $row['max_players']) {
            echo json_encode(['success' => false, 'message' => 'Room is full']);
            exit();
        }
    }
    $stmt->close();
}

// Check if already in room
$stmt = executeQuery(
    "SELECT id FROM room_players WHERE user_id = ? AND room_id = ?",
    'ii',
    [$userId, $roomId]
);

$alreadyInRoom = false;
if ($stmt) {
    $result = $stmt->get_result();
    $alreadyInRoom = $result->num_rows > 0;
    $stmt->close();
}

if (!$alreadyInRoom) {
    // Check if first player (make host)
    $stmt = executeQuery(
        "SELECT COUNT(*) as count FROM room_players WHERE room_id = ?",
        'i',
        [$roomId]
    );
    
    $isHost = 0;
    if ($stmt) {
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $isHost = ($row['count'] == 0) ? 1 : 0;
        }
        $stmt->close();
    }
    
    // Add player to room
    $stmt = executeQuery(
        "INSERT INTO room_players (room_id, user_id, is_host) VALUES (?, ?, ?)",
        'iii',
        [$roomId, $userId, $isHost]
    );
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to join room']);
        exit();
    }
}

echo json_encode([
    'success' => true,
    'room_id' => $roomId,
    'message' => 'Joined room successfully'
]);
?>
