<?php
/**
 * Ranking page (Screen 5) - Show results and rankings
 * Display after each round completes
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireAuth();

$userId = getCurrentUserId();
$username = getCurrentUsername();
$roomId = $_GET['room_id'] ?? null;

if (!$roomId || !isInRoom($userId, $roomId)) {
    header('Location: lobby.php');
    exit();
}

// Get current game rankings
$stmt = executeQuery(
    "SELECT u.username, m.move_count, m.time_ms, m.finished_at,
            RANK() OVER (ORDER BY m.finished_at ASC, m.time_ms ASC) as rank
     FROM moves m
     JOIN users u ON m.user_id = u.id
     JOIN games g ON m.game_id = g.id
     WHERE g.room_id = ? AND g.id = (SELECT MAX(id) FROM games WHERE room_id = ?)
     ORDER BY rank ASC",
    '',
    [$roomId, $roomId]
);

$rankings = [];
if ($stmt) {
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rankings[] = $row;
    }
    
}

// Get room info
$stmt = executeQuery(
    "SELECT current_round, status FROM rooms WHERE id = ?",
    '',
    [$roomId]
);

$room = null;
if ($stmt) {
    
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Rankings</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="theme-classic">
    <nav class="top-nav">
        <div class="nav-left">
            <h1 class="nav-title">Reindeer Games</h1>
        </div>
        <div class="nav-right">
            <span class="username-display"><?php echo htmlspecialchars($username); ?></span>
        </div>
    </nav>
    
    <div class="container ranking-container">
        <div class="ranking-card card">
            <h1>Round <?php echo $room['current_round'] ?? 1; ?> Results</h1>
            
            <div class="rankings-list">
                <?php if (empty($rankings)): ?>
                    <p>Loading results...</p>
                <?php else: ?>
                    <?php foreach ($rankings as $i => $player): ?>
                        <div class="ranking-item <?php echo $player['username'] === $username ? 'current-user' : ''; ?>" 
                             data-rank="<?php echo $i + 1; ?>">
                            <div class="rank-number">
                                <?php if ($i === 0): ?>
                                    <span class="medal gold">1st</span>
                                <?php elseif ($i === 1): ?>
                                    <span class="medal silver">2nd</span>
                                <?php elseif ($i === 2): ?>
                                    <span class="medal bronze">3rd</span>
                                <?php else: ?>
                                    <span><?php echo $i + 1; ?>th</span>
                                <?php endif; ?>
                            </div>
                            <div class="rank-username"><?php echo htmlspecialchars($player['username']); ?></div>
                            <div class="rank-stats">
                                <span><?php echo number_format($player['time_ms'] / 1000, 1); ?>s</span>
                                <span><?php echo $player['move_count']; ?> moves</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="ranking-actions">
                <div id="next-round-countdown"></div>
                <button id="continue-btn" class="btn btn-primary btn-large">Continue</button>
                <button id="exit-btn" class="btn btn-secondary">Exit to Lobby</button>
            </div>
        </div>
        
        <!-- New Achievements -->
        <div class="card achievements-earned" id="new-achievements" style="display: none;">
            <h2>New Achievements Unlocked!</h2>
            <div id="achievements-list"></div>
        </div>
    </div>
    
    <script src="assets/js/common.js"></script>
    <script src="assets/js/ranking.js"></script>
    <script>
        const roomId = <?php echo $roomId; ?>;
        const userId = <?php echo $userId; ?>;
        
        Ranking.init(roomId, userId);
    </script>
    <script src="assets/js/theme.js"></script>
</body>
</html>
