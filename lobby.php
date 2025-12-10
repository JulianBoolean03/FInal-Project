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
                    <p>Jump into a game with another player</p>
                    <a href="quick_match.php" class="btn btn-primary btn-large" style="text-decoration: none;">Find Match</a>
                </div>
                
                <div class="card option-card">
                    <h2>Create Private Room</h2>
                    <p>Create a room and invite friends with a code</p>
                    <button id="create-private-btn" class="btn btn-primary btn-large">Create Private Room</button>
                </div>
                
                <div class="card option-card">
                    <h2>Join Private Room</h2>
                    <p>Enter a room code to join your friends</p>
                    <form id="join-private-form">
                        <input type="text" id="room-code-input" placeholder="Enter room code" 
                               maxlength="10" style="text-transform: uppercase;">
                        <button type="submit" class="btn btn-primary btn-large">Join Room</button>
                    </form>
                </div>
            </div>
            
            <!-- Available Public Rooms -->
            <div class="card">
                <h2>Available Public Rooms</h2>
                <div id="public-rooms-list"></div>
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
    </script>
</body>
</html>
