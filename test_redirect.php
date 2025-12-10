<?php
/**
 * Test redirect to game page
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

startSession();

if (!isLoggedIn()) {
    die("You must be logged in. <a href='index.php'>Login here</a>");
}

$userId = getCurrentUserId();
$username = getCurrentUsername();

echo "<h1>Game Redirect Test</h1>";
echo "<p>User: $username (ID: $userId)</p>";

// Get user's current room
$roomId = getCurrentRoomId($userId);

if (!$roomId) {
    echo "<p style='color: red;'>You are not in any room!</p>";
    echo "<p><a href='lobby.php'>Go to Lobby</a></p>";
    exit;
}

echo "<h2>Current Room: $roomId</h2>";

// Check if in room
$inRoom = isInRoom($userId, $roomId);
echo "<p>isInRoom check: " . ($inRoom ? "TRUE" : "FALSE") . "</p>";

// Get room status
$stmt = executeQuery("SELECT * FROM rooms WHERE id = ?", '', [$roomId]);
$room = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;

if ($room) {
    echo "<p>Room status: {$room['status']}</p>";
    echo "<p>Room code: {$room['code']}</p>";
    
    // Get game
    $stmt = executeQuery("SELECT * FROM games WHERE room_id = ? ORDER BY id DESC LIMIT 1", '', [$roomId]);
    $game = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
    
    if ($game) {
        echo "<p>Game ID: {$game['id']}</p>";
        echo "<p>Round: {$game['round_number']}</p>";
    } else {
        echo "<p style='color: orange;'>No game record found yet</p>";
    }
} else {
    echo "<p style='color: red;'>Room not found!</p>";
}

echo "<hr>";
echo "<h2>Test Actions</h2>";

if ($room && $room['status'] === 'waiting') {
    // Start game button
    echo "<button onclick='startGame($roomId)'>Start Game (will update room to starting)</button><br><br>";
}

echo "<a href='game.php?room_id=$roomId' style='font-size: 1.2em;'>→ Try to open game.php directly</a><br><br>";
echo "<a href='lobby.php'>← Back to Lobby</a>";

?>

<script src="assets/js/common.js"></script>
<script>
async function startGame(roomId) {
    console.log('Starting game...');
    const result = await API.post('api/start_game.php', { room_id: roomId });
    console.log('Result:', result);
    
    if (result.success) {
        alert('Game started! game_id=' + result.game_id);
        
        // Wait a moment then redirect
        setTimeout(() => {
            window.location.href = 'game.php?room_id=' + roomId;
        }, 1000);
    } else {
        alert('Failed: ' + (result.message || result.error));
    }
}
</script>

<style>
button {
    padding: 10px 20px;
    font-size: 16px;
    cursor: pointer;
}
a {
    display: inline-block;
    padding: 10px 20px;
    background: #4CAF50;
    color: white;
    text-decoration: none;
    border-radius: 5px;
}
</style>
