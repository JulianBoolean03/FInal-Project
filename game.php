<?php
/**
 * Game page (Screen 4) - Main puzzle gameplay
 * Shows 15-puzzle, timer, chat, and mini-leaderboard
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

// Update last active timestamp
updateLastActive($userId, $roomId);

// Get current game ID
$stmt = executeQuery(
    "SELECT id, round_number FROM games WHERE room_id = ? ORDER BY id DESC LIMIT 1",
    '',
    [$roomId]
);

$gameId = null;
$roundNumber = 1;

if ($stmt) {
    
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $gameId = $row['id'];
        $roundNumber = $row['round_number'];
    }
    
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Game</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="theme-classic">
    <nav class="top-nav">
        <div class="nav-left">
            <h1 class="nav-title">Reindeer Games - Round <?php echo $roundNumber; ?></h1>
        </div>
        <div class="nav-right">
            <span class="username-display"><?php echo htmlspecialchars($username); ?></span>
        </div>
    </nav>
    
    <div class="game-container">
        <div class="game-left">
            <!-- Puzzle Board -->
            <div class="card puzzle-card">
                <div class="game-stats">
                    <div class="stat">
                        <span class="stat-label">Time:</span>
                        <span id="timer" class="stat-value">00:00</span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Moves:</span>
                        <span id="move-counter" class="stat-value">0</span>
                    </div>
                </div>
                
                <div id="puzzle-board" class="puzzle-board"></div>
                
                <div class="puzzle-controls">
                    <h3>Power-ups</h3>
                    <div class="powerups">
                        <button id="powerup-hint" class="btn btn-powerup" title="Highlight 3 wrong tiles">
                            Hint (3 tiles)
                        </button>
                        <button id="powerup-solve" class="btn btn-powerup" title="Auto-solve one tile">
                            Solve One
                        </button>
                        <button id="powerup-preview" class="btn btn-powerup" title="Preview solved image">
                            Preview
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="game-right">
            <!-- Current Players -->
            <div class="card">
                <h3>Players in Room</h3>
                <div id="players-list" class="players-list"></div>
            </div>
            
            <!-- Mini Leaderboard -->
            <div class="card">
                <h3>Live Rankings</h3>
                <div id="mini-leaderboard" class="mini-leaderboard"></div>
            </div>
            
            <!-- Chat -->
            <div class="card chat-card">
                <h3>Chat</h3>
                <div id="chat-messages" class="chat-messages"></div>
                <form id="chat-form" class="chat-form">
                    <input type="text" id="chat-input" placeholder="Type a message..." maxlength="200">
                    <button type="submit" class="btn btn-small">Send</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Win Modal -->
    <div id="win-modal" class="modal">
        <div class="modal-content card">
            <h2>Puzzle Complete!</h2>
            <div id="completion-stats"></div>
            <p>Waiting for other players...</p>
        </div>
    </div>
    
    <script src="assets/js/common.js"></script>
    <script src="assets/js/game.js"></script>
    <script>
        const roomId = <?php echo $roomId; ?>;
        const gameId = <?php echo $gameId ?? 'null'; ?>;
        const userId = <?php echo $userId; ?>;
        
        Game.init(roomId, gameId, userId);
    </script>
</body>
</html>
