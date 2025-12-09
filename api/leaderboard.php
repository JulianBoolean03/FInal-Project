<?php
/**
 * Leaderboard API endpoint
 * Returns real-time leaderboard for a game
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
requireAuth();

$gameId = $_GET['game_id'] ?? null;

if (!$gameId) {
    echo json_encode(['success' => false, 'message' => 'Game ID required']);
    exit();
}

// Get all players in game
$stmt = executeQuery(
    "SELECT u.id, u.username, m.finished, m.move_count, m.time_ms, m.finished_at
     FROM moves m
     JOIN users u ON m.user_id = u.id
     WHERE m.game_id = ?
     ORDER BY m.finished DESC, m.finished_at ASC, m.time_ms ASC",
    '',
    [$gameId]
);

$players = [];
$leaderboard = [];

if ($stmt) {
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $players[] = [
            'user_id' => $row['id'],
            'username' => $row['username'],
            'finished' => (bool)$row['finished']
        ];
        
        $leaderboard[] = [
            'username' => $row['username'],
            'move_count' => $row['move_count'],
            'time_ms' => $row['time_ms'],
            'finished' => (bool)$row['finished']
        ];
    }
    
}

echo json_encode([
    'success' => true,
    'players' => $players,
    'leaderboard' => $leaderboard
]);
?>
