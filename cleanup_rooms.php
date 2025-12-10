#!/usr/bin/env php
<?php
/**
 * Cleanup Script - Reset stuck rooms and orphaned data
 * Run: php cleanup_rooms.php
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

echo "=== Room Cleanup Script ===\n\n";

$db = getDB();

// Option 1: Delete all rooms and start fresh
echo "Option 1: Delete ALL rooms (nuclear option)\n";
echo "Option 2: Reset rooms to 'waiting' status\n";
echo "Option 3: Delete empty rooms only\n";
echo "Option 4: Delete rooms with 0 players\n\n";

if (php_sapi_name() === 'cli') {
    echo "Enter option (1-4): ";
    $option = trim(fgets(STDIN));
} else {
    // If run via web, default to option 4
    $option = $_GET['option'] ?? '4';
    echo "Running option $option (delete rooms with 0 players)<br><br>";
}

switch ($option) {
    case '1':
        // Nuclear option - delete everything
        echo "Deleting all rooms...\n";
        $db->exec("DELETE FROM moves");
        $db->exec("DELETE FROM games");
        $db->exec("DELETE FROM chat_messages");
        $db->exec("DELETE FROM room_players");
        $db->exec("DELETE FROM rooms");
        echo "✓ All rooms deleted\n";
        break;
        
    case '2':
        // Reset all rooms to waiting
        echo "Resetting all rooms to 'waiting' status...\n";
        $result = $db->exec("UPDATE rooms SET status = 'waiting', current_round = 0");
        echo "✓ Reset $result rooms\n";
        break;
        
    case '3':
        // Delete rooms with no players
        echo "Finding empty rooms...\n";
        $stmt = $db->query("
            SELECT r.id, r.code, r.status 
            FROM rooms r 
            LEFT JOIN room_players rp ON r.id = rp.room_id 
            WHERE rp.id IS NULL
        ");
        $emptyRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($emptyRooms) > 0) {
            foreach ($emptyRooms as $room) {
                echo "Deleting room {$room['code']} (ID: {$room['id']}, Status: {$room['status']})\n";
                $db->exec("DELETE FROM games WHERE room_id = {$room['id']}");
                $db->exec("DELETE FROM chat_messages WHERE room_id = {$room['id']}");
                $db->exec("DELETE FROM rooms WHERE id = {$room['id']}");
            }
            echo "✓ Deleted " . count($emptyRooms) . " empty rooms\n";
        } else {
            echo "No empty rooms found\n";
        }
        break;
        
    case '4':
        // Delete rooms with 0 players
        echo "Finding rooms with 0 players...\n";
        $stmt = $db->query("
            SELECT r.id, r.code, r.status, COUNT(rp.id) as player_count
            FROM rooms r
            LEFT JOIN room_players rp ON r.id = rp.room_id
            GROUP BY r.id
            HAVING player_count = 0
        ");
        $zeroPlayerRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($zeroPlayerRooms) > 0) {
            foreach ($zeroPlayerRooms as $room) {
                echo "Deleting room {$room['code']} (ID: {$room['id']}, Status: {$room['status']})\n";
                $db->exec("DELETE FROM moves WHERE game_id IN (SELECT id FROM games WHERE room_id = {$room['id']})");
                $db->exec("DELETE FROM games WHERE room_id = {$room['id']}");
                $db->exec("DELETE FROM chat_messages WHERE room_id = {$room['id']}");
                $db->exec("DELETE FROM room_players WHERE room_id = {$room['id']}");
                $db->exec("DELETE FROM rooms WHERE id = {$room['id']}");
            }
            echo "✓ Deleted " . count($zeroPlayerRooms) . " rooms with 0 players\n";
        } else {
            echo "No rooms with 0 players found\n";
        }
        break;
        
    default:
        echo "Invalid option\n";
        exit(1);
}

// Show current state
echo "\n=== Current Rooms ===\n";
$stmt = $db->query("
    SELECT r.*, COUNT(rp.id) as player_count
    FROM rooms r
    LEFT JOIN room_players rp ON r.id = rp.room_id
    GROUP BY r.id
    ORDER BY r.id
");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($rooms) > 0) {
    foreach ($rooms as $room) {
        echo "Room {$room['code']} (ID: {$room['id']}): {$room['status']}, {$room['player_count']} players\n";
    }
} else {
    echo "No rooms exist\n";
}

echo "\n=== Cleanup Complete ===\n";
?>
