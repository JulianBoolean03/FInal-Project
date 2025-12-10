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
    '',
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
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $roomId = $row['id'];
        }
        
    }
    
    // Create new public room if none available
    if (!$roomId) {
        do {
            $newCode = generateRoomCode(6);
            $checkStmt = executeQuery("SELECT id FROM rooms WHERE code = ?", '', [$newCode]);
            $exists = $checkStmt && $checkStmt->fetch();
        } while ($exists);
        
        $stmt = executeQuery(
    "INSERT INTO rooms (code, is_private, host_user_id, max_players) VALUES (?, 0, ?, ?)",
    '',
            [$newCode, $userId, MAX_PLAYERS_PER_ROOM]
        );
        
        if ($stmt) {
            $roomId = getDB()->lastInsertId();
            
        }
    }
} elseif ($type === 'private' && $code) {
    // Join private room by code
    $stmt = executeQuery(
    "SELECT id FROM rooms WHERE code = ? AND is_private = 1 AND status = 'waiting'",
    '',
        [$code]
    );
    
    if ($stmt) {
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $roomId = $row['id'];
        }
        
    }
    
    if (!$roomId) {
        echo json_encode(['success' => false, 'message' => 'Invalid room code']);
        exit();
    }
} elseif ($roomId) {
    // Join specific room by ID
    $stmt = executeQuery(
    "SELECT id FROM rooms WHERE id = ? AND status = 'waiting'",
    '',
        [$roomId]
    );
    
    if ($stmt) {
        
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            $roomId = null;
        }
        
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
    '',
    [$roomId]
);

if ($stmt) {
    
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['player_count'] >= $row['max_players']) {
            echo json_encode(['success' => false, 'message' => 'Room is full']);
            exit();
        }
    }
    
}

// Check if already in room
$stmt = executeQuery(
    "SELECT id FROM room_players WHERE user_id = ? AND room_id = ?",
    '',
    [$userId, $roomId]
);

$alreadyInRoom = false;
if ($stmt) {
    $alreadyInRoom = $stmt->fetch() !== false;
}

if (!$alreadyInRoom) {
    // Check if first player (make host)
    $stmt = executeQuery(
    "SELECT COUNT(*) as count FROM room_players WHERE room_id = ?",
    '',
        [$roomId]
    );
    
    $isHost = 0;
    if ($stmt) {
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $isHost = ($row['count'] == 0) ? 1 : 0;
        }
        
    }
    
    // Add player to room
    $stmt = executeQuery(
    "INSERT INTO room_players (room_id, user_id, is_host) VALUES (?, ?, ?)",
    '',
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
