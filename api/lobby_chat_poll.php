<?php
/**
 * Lobby Chat poll API endpoint
 * Returns recent chat messages from the global lobby (room_id = -1)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
requireAuth();

$userId = getCurrentUserId();
$since = $_GET['since'] ?? 0; // Get messages after this ID

// Use room_id = -1 for global lobby chat
$stmt = executeQuery(
    "SELECT cm.id, cm.user_id, cm.message, cm.created_at, u.username, 
            COALESCE(a.race_wins, 0) as race_wins
     FROM chat_messages cm
     JOIN users u ON cm.user_id = u.id
     LEFT JOIN analytics a ON cm.user_id = a.user_id
     WHERE cm.room_id = -1 AND cm.id > ?
     ORDER BY cm.created_at DESC
     LIMIT 50",
    '',
    [$since]
);

$messages = [];
if ($stmt) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Calculate color based on race wins
        $raceWins = $row['race_wins'];
        $color = '#FFFFFF';
        if ($raceWins >= 50) $color = '#FF0000';
        else if ($raceWins >= 25) $color = '#E5E4E2';
        else if ($raceWins >= 10) $color = '#FFD700';
        else if ($raceWins >= 5) $color = '#C0C0C0';
        else if ($raceWins >= 1) $color = '#CD7F32';
        
        $messages[] = [
            'id' => $row['id'],
            'user_id' => $row['user_id'],
            'username' => $row['username'],
            'color' => $color,
            'message' => $row['message'],
            'time' => date('g:i A', strtotime($row['created_at']))
        ];
    }
}

echo json_encode(['success' => true, 'messages' => array_reverse($messages)]);
?>
