<?php
/**
 * Achievements API endpoint
 * Returns user achievements and statistics
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
requireAuth();

$userId = getCurrentUserId();
$action = $_GET['action'] ?? 'list';

if ($action === 'stats') {
    // Get user statistics
    $stmt = executeQuery(
    "SELECT games_played, puzzles_solved, best_time_ms, total_moves, total_time_ms 
         FROM analytics WHERE user_id = ?",
    '',
        [$userId]
    );
    
    $stats = ['games_played' => 0, 'puzzles_solved' => 0, 'best_time_ms' => 0, 'avg_moves' => 0];
    
    if ($stmt) {
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats = $row;
            $stats['avg_moves'] = $row['puzzles_solved'] > 0 ? 
                round($row['total_moves'] / $row['puzzles_solved']) : 0;
        }
        
    }
    
    echo json_encode(['success' => true, 'stats' => $stats]);
    exit();
}

if ($action === 'list') {
    // Get user's earned achievements
    $stmt = executeQuery(
    "SELECT a.achievement_key, a.name, a.description, a.icon, ua.earned_at
         FROM user_achievements ua
         JOIN achievements a ON ua.achievement_id = a.id
         WHERE ua.user_id = ?
         ORDER BY ua.earned_at DESC",
    '',
        [$userId]
    );
    
    $achievements = [];
    if ($stmt) {
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $achievements[] = $row;
        }
        
    }
    
    echo json_encode(['success' => true, 'achievements' => $achievements]);
    exit();
}

if ($action === 'new') {
    // Get recently earned achievements (last 5 minutes)
    $stmt = executeQuery(
    "SELECT a.achievement_key, a.name, a.description, a.icon
         FROM user_achievements ua
         JOIN achievements a ON ua.achievement_id = a.id
         WHERE ua.user_id = ? AND ua.earned_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
         ORDER BY ua.earned_at DESC",
    '',
        [$userId]
    );
    
    $achievements = [];
    if ($stmt) {
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $achievements[] = $row;
        }
        
    }
    
    echo json_encode(['success' => true, 'achievements' => $achievements]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
