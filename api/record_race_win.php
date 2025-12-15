<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$userId = $data['user_id'] ?? 0;

if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'No user ID']);
    exit;
}

// Increment race_wins in analytics
executeQuery(
    "UPDATE analytics SET race_wins = race_wins + 1 WHERE user_id = ?",
    '',
    [$userId]
);

// Get current race wins
$stmt = executeQuery("SELECT race_wins FROM analytics WHERE user_id = ?", '', [$userId]);
$row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
$raceWins = $row ? $row['race_wins'] : 0;

// Check for color achievements
$achievements = [
    1 => 'bronze_racer',
    5 => 'silver_racer',
    10 => 'gold_racer',
    25 => 'platinum_racer',
    50 => 'legendary_racer'
];

foreach ($achievements as $wins => $key) {
    if ($raceWins == $wins) {
        // Get achievement ID
        $stmt = executeQuery("SELECT id FROM achievements WHERE achievement_key = ?", '', [$key]);
        $achievement = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        
        if ($achievement) {
            // Award achievement
            executeQuery(
                "INSERT OR IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?, ?)",
                '',
                [$userId, $achievement['id']]
            );
        }
    }
}

echo json_encode(['success' => true, 'race_wins' => $raceWins]);
?>
