<?php
/**
 * Submit moves API endpoint
 * Records player's completion data and awards achievements
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$userId = getCurrentUserId();
$gameId = $input['game_id'] ?? null;
$moveCount = $input['move_count'] ?? 0;
$timeMs = $input['time_ms'] ?? 0;
$powerupsUsed = $input['powerups_used'] ?? '';

if (!$gameId) {
    echo json_encode(['success' => false, 'message' => 'Game ID required']);
    exit();
}

// Update moves record
$stmt = executeQuery(
    "UPDATE moves SET move_count = ?, time_ms = ?, finished = 1, finished_at = NOW(), powerups_used = ? 
     WHERE game_id = ? AND user_id = ?",
    '',
    [$moveCount, $timeMs, $powerupsUsed, $gameId, $userId]
);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to submit']);
    exit();
}

// Update analytics
$stmt = executeQuery(
    "UPDATE analytics SET 
     games_played = games_played + 1,
     puzzles_solved = puzzles_solved + 1,
     total_time_ms = total_time_ms + ?,
     best_time_ms = CASE WHEN best_time_ms = 0 OR ? < best_time_ms THEN ? ELSE best_time_ms END,
     total_moves = total_moves + ?
     WHERE user_id = ?",
    '',
    [$timeMs, $timeMs, $timeMs, $moveCount, $userId]
);

// Check achievements
$newAchievements = [];

// Get user stats
$stmt = executeQuery("SELECT puzzles_solved FROM analytics WHERE user_id = ?", '', [$userId]);

$stats = $stmt->fetch(PDO::FETCH_ASSOC);


// First win
if ($stats['puzzles_solved'] == 1) {
    awardAchievement($userId, 'first_win');
    $newAchievements[] = 'First Victory';
}

// Speed demon (under 30 seconds)
if ($timeMs < 30000) {
    awardAchievement($userId, 'speed_demon');
    $newAchievements[] = 'Speed Demon';
}

// Efficient solver (under 50 moves)
if ($moveCount < 50) {
    awardAchievement($userId, 'efficient_solver');
    $newAchievements[] = 'Efficient Solver';
}

// Perfectionist (under 25 moves)
if ($moveCount < 25) {
    awardAchievement($userId, 'perfectionist');
    $newAchievements[] = 'Perfectionist';
}

echo json_encode(['success' => true, 'new_achievements' => $newAchievements]);

function awardAchievement($userId, $key) {
    $stmt = executeQuery("SELECT id FROM achievements WHERE achievement_key = ?", '', [$key]);
    if (!$stmt) return;
    
    
    $achievement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    
    if ($achievement) {
        $stmt = executeQuery(
    "INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?, ?)",
    '',
            [$userId, $achievement['id']]
        );
    }
}
?>
