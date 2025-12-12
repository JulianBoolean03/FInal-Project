<?php
/**
 * Lobby page (Screen 2) - Join or create game rooms
 * Users can join public rooms or create/join private rooms
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireAuth();

$userId = getCurrentUserId();
$username = getCurrentUsername();

// Clean up quick match waiting flag
executeQuery("UPDATE analytics SET best_time_ms = 0 WHERE user_id = ? AND best_time_ms = 999999999", '', [$userId]);

// Check if user is already in a room
$currentRoomId = getCurrentRoomId($userId);
if ($currentRoomId) {
    // Check room status
    $stmt = executeQuery(
    "SELECT status FROM rooms WHERE id = ?",
    '',
        [$currentRoomId]
    );
    
    if ($stmt) {
        
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        
        
        if ($room) {
            // Don't auto-redirect, let JavaScript handle it to avoid loops
            // Otherwise stay in lobby to see current room
        }
    }
}

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Lobby</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="theme-classic">
    <nav class="top-nav">
        <div class="nav-left">
            <h1 class="nav-title">Reindeer Games</h1>
        </div>
        <div class="nav-right">
            <span class="username-display">Welcome, <?php echo htmlspecialchars($username); ?></span>
            <a href="logout.php" class="btn btn-small">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <!-- Theme Selector -->
        <div class="card">
            <h2>Theme Selector</h2>
            <div class="theme-options">
                <button class="btn btn-secondary" data-theme="theme-classic">Classic</button>
                <button class="btn btn-secondary" data-theme="theme-snowy">Snowy</button>
                <button class="btn btn-secondary" data-theme="theme-candycane">Candy Cane</button>
            </div>
        </div>

    
        <?php if ($error === 'room_full'): ?>
            <div class="alert alert-error">Room is full.</div>
        <?php elseif ($error === 'invalid_code'): ?>
            <div class="alert alert-error">Invalid room code.</div>
        <?php elseif ($error === 'room_started'): ?>
            <div class="alert alert-error">Game has already started.</div>
        <?php elseif ($success === 'room_created'): ?>
            <div class="alert alert-success">Room created successfully!</div>
        <?php endif; ?>
        
        <!-- Current Room Section (if in a room) -->
        <?php if ($currentRoomId): ?>
            <div class="current-room-panel card">
                <h2>Current Room</h2>
                <div id="current-room-info"></div>
                <a href="leave_room.php" class="btn btn-danger">Leave Room</a>
            </div>
        <?php else: ?>
            <!-- Join/Create Room Options -->
            <div class="lobby-options">
                <div class="card option-card">
                    <h2>Practice Mode</h2>
                    <p>Play solo and improve your skills</p>
                    <a href="practice.php" class="btn btn-primary btn-large" style="text-decoration: none;">Practice Solo</a>
                </div>
                
                <div class="card option-card">
                    <h2>Quick Match</h2>
                    <p>Race against another player!</p>
                    <a href="quick_match.php" class="btn btn-primary btn-large" style="text-decoration: none;">Find Match</a>
                </div>
            </div>
            
        <?php endif; ?>
        
        <!-- Player Stats -->
        <div class="card">
            <h2>Your Stats</h2>
            <div id="player-stats"></div>
        </div>
        
        <!-- Achievements -->
        <div class="card">
            <h2>Your Achievements</h2>
            <div id="achievements-list"></div>
        </div>
    </div>
    
    <script src="assets/js/common.js"></script>
    <script src="assets/js/lobby.js"></script>
    <script>
        const currentRoomId = <?php echo $currentRoomId ? $currentRoomId : 'null'; ?>;
        const userId = <?php echo $userId; ?>;
        
        // Initialize lobby
        if (currentRoomId) {
            Lobby.initCurrentRoom(currentRoomId);
        } else {
            Lobby.initLobby();
        }

        //Theme Switching via JavaScript
        function updateActiveButton(theme) {
            document.querySelectorAll('.theme-options button').forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('data-theme') === theme);
            });
        }

        document.querySelectorAll('.theme-options button').forEach(btn => {
            btn.addEventListener('click', () => {
                const selectedTheme = btn.getAttribute('data-theme');

                // Remove previous used theme classes
                document.body.classList.remove('theme-classic', 'theme-snowy', 'theme-candycane');

                // Apply selected theme to lobby
                document.body.classList.add(selectedTheme);

                // Save preference in localStorage
                localStorage.setItem('selectedTheme', selectedTheme);
            });
        });

        // Apply saved theme on page load
        const savedTheme = localStorage.getItem('selectedTheme') || 'theme-classic';
        document.body.classList.add(savedTheme);
        updateActiveButton(savedTheme);
    </script>
    <script src="assets/js/theme.js"></script>
</body>
</html>
