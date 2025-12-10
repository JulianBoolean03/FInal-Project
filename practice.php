<?php
/**
 * Practice Mode - Single Player Puzzle
 * Play solo without rooms or multiplayer
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireAuth();

$userId = getCurrentUserId();
$username = getCurrentUsername();

// Check if this is race mode
$raceMode = isset($_GET['race_mode']) && $_GET['race_mode'] == '1';
$opponentName = $_GET['opponent'] ?? 'Opponent';
$opponentId = $_GET['opponent_id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Practice Mode</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="theme-classic">
    <nav class="top-nav">
        <div class="nav-left">
            <h1 class="nav-title"><?php echo $raceMode ? '🏁 Race Mode' : 'Practice Mode'; ?></h1>
            <?php if ($raceMode): ?>
                <p style="margin:0; font-size:0.9em; color:#aaa;">Racing against <?php echo htmlspecialchars($opponentName); ?></p>
            <?php endif; ?>
        </div>
        <div class="nav-right">
            <span class="username-display"><?php echo htmlspecialchars($username); ?></span>
            <a href="lobby.php" class="btn btn-small">Back to Lobby</a>
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
                    <div class="stat">
                        <span class="stat-label">Best:</span>
                        <span id="best-time" class="stat-value">--:--</span>
                    </div>
                </div>
                
                <div id="puzzle-board" class="puzzle-board"></div>
                
                <div class="puzzle-controls">
                    <button id="new-game-btn" class="btn btn-primary" style="margin-bottom: 1rem;">
                        New Puzzle
                    </button>
                    <h3>Power-ups (Practice)</h3>
                    <div class="powerups">
                        <button id="powerup-hint" class="btn btn-powerup" title="Highlight 3 wrong tiles">
                            Hint
                        </button>
                        <button id="powerup-solve" class="btn btn-powerup" title="Show solution preview">
                            Peek
                        </button>
                        <button id="powerup-reset" class="btn btn-powerup" title="Reset puzzle">
                            Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="game-right">
            <!-- Instructions -->
            <div class="card">
                <h3>How to Play</h3>
                <p style="margin-bottom: 1rem;">Click tiles next to the empty space to move them. Arrange all tiles in order from 1-15.</p>
                
                <h4>Tips:</h4>
                <ul style="margin-left: 1.5rem; margin-bottom: 1rem;">
                    <li>Start with the top row</li>
                    <li>Work your way down</li>
                    <li>Use power-ups if stuck</li>
                </ul>
            </div>
            
            <!-- Stats -->
            <div class="card">
                <h3>Your Practice Stats</h3>
                <div id="practice-stats">
                    <p>Games Played: <strong id="games-count">0</strong></p>
                    <p>Puzzles Solved: <strong id="solved-count">0</strong></p>
                    <p>Best Time: <strong id="best-time-stat">--:--</strong></p>
                    <p>Avg Moves: <strong id="avg-moves-stat">--</strong></p>
                </div>
            </div>
            
            <!-- High Scores -->
            <div class="card">
                <h3>Recent Completions</h3>
                <div id="recent-games"></div>
            </div>
        </div>
    </div>
    
    <!-- Win Modal -->
    <div id="win-modal" class="modal">
        <div class="modal-content card">
            <h2>Puzzle Solved!</h2>
            <div id="completion-stats"></div>
            <div style="margin-top: 1rem;">
                <button id="play-again-btn" class="btn btn-primary btn-large">
                    Play Again
                </button>
                <button id="back-lobby-btn" class="btn btn-secondary">
                    Back to Lobby
                </button>
            </div>
        </div>
    </div>
    
    <script src="assets/js/common.js?v=2"></script>
    <script src="assets/js/practice.js?v=2"></script>
    <script>
        const userId = <?php echo $userId; ?>;
        const raceMode = <?php echo $raceMode ? 'true' : 'false'; ?>;
        const opponentId = <?php echo $opponentId; ?>;
        const opponentName = '<?php echo addslashes($opponentName); ?>';
        
        if (raceMode) {
            Practice.initRace(userId, opponentId, opponentName);
        } else {
            Practice.init(userId);
        }
    </script>
</body>
</html>
