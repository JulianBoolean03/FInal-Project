<?php
/**
 * API Test Page - Test room creation and joining
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

echo "<h1>API Test - User: $username (ID: $userId)</h1>";

// Test 1: Database write
echo "<h2>Test 1: Database Write Test</h2>";
try {
    $db = getDB();
    $testTable = "test_write_" . time();
    $db->exec("CREATE TABLE $testTable (id INTEGER PRIMARY KEY, data TEXT)");
    $stmt = $db->prepare("INSERT INTO $testTable (data) VALUES (?)");
    $result = $stmt->execute(['test_data']);
    
    if ($result) {
        echo "✓ Database write successful<br>";
        $db->exec("DROP TABLE $testTable");
    } else {
        echo "✗ Database write failed<br>";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

// Test 2: Create room
echo "<h2>Test 2: Create Private Room</h2>";
try {
    // Generate unique room code
    do {
        $code = generateRoomCode(6);
        $stmt = executeQuery("SELECT id FROM rooms WHERE code = ?", '', [$code]);
        $exists = $stmt && $stmt->fetch();
    } while ($exists);
    
    echo "Generated code: $code<br>";
    
    // Create room
    $stmt = executeQuery(
        "INSERT INTO rooms (code, is_private, host_user_id, max_players) VALUES (?, ?, ?, ?)",
        '',
        [$code, 1, $userId, MAX_PLAYERS_PER_ROOM]
    );
    
    if ($stmt) {
        $roomId = getDB()->lastInsertId();
        echo "✓ Room created with ID: $roomId<br>";
        
        // Add host to room
        $stmt = executeQuery(
            "INSERT INTO room_players (room_id, user_id, is_host) VALUES (?, ?, 1)",
            '',
            [$roomId, $userId]
        );
        
        if ($stmt) {
            echo "✓ Host added to room<br>";
            echo "<strong>Success! Room $code created.</strong><br>";
            
            // Clean up test room
            executeQuery("DELETE FROM room_players WHERE room_id = ?", '', [$roomId]);
            executeQuery("DELETE FROM rooms WHERE id = ?", '', [$roomId]);
            echo "<em>(Test room cleaned up)</em><br>";
        } else {
            echo "✗ Failed to add host to room<br>";
        }
    } else {
        echo "✗ Failed to create room - executeQuery returned false<br>";
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "<br>";
}

// Test 3: Check existing rooms
echo "<h2>Test 3: Existing Rooms</h2>";
try {
    $stmt = executeQuery("SELECT * FROM rooms ORDER BY id DESC LIMIT 5", '', []);
    if ($stmt) {
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($rooms) > 0) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Code</th><th>Status</th><th>Host ID</th><th>Players</th></tr>";
            foreach ($rooms as $room) {
                $playerStmt = executeQuery("SELECT COUNT(*) as count FROM room_players WHERE room_id = ?", '', [$room['id']]);
                $playerCount = $playerStmt ? $playerStmt->fetch(PDO::FETCH_ASSOC)['count'] : 0;
                echo "<tr><td>{$room['id']}</td><td>{$room['code']}</td><td>{$room['status']}</td><td>{$room['host_user_id']}</td><td>$playerCount</td></tr>";
            }
            echo "</table>";
        } else {
            echo "No rooms found<br>";
        }
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

// Test 4: Your current room status
echo "<h2>Test 4: Your Room Status</h2>";
try {
    $stmt = executeQuery(
        "SELECT rp.*, r.code, r.status FROM room_players rp 
         JOIN rooms r ON rp.room_id = r.id 
         WHERE rp.user_id = ?",
        '',
        [$userId]
    );
    
    if ($stmt) {
        $membership = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($membership) {
            echo "You are in room: {$membership['code']}<br>";
            echo "Room status: {$membership['status']}<br>";
            echo "You are " . ($membership['is_host'] ? "HOST" : "PLAYER") . "<br>";
        } else {
            echo "You are not in any room<br>";
        }
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><a href='lobby.php'>Back to Lobby</a></p>";
echo "<p style='color: red;'><strong>Delete this file after testing!</strong></p>";
?>
