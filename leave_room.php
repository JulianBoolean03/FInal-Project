<?php
/**
 * Leave Room - Simple redirect to clean up and go back to lobby
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireAuth();

$userId = getCurrentUserId();

// Remove user from all rooms
executeQuery("DELETE FROM room_players WHERE user_id = ?", '', [$userId]);

// Clean up empty rooms
executeQuery(
    "DELETE FROM rooms WHERE id NOT IN (
        SELECT DISTINCT room_id FROM room_players
    )",
    '',
    []
);

// Redirect back to lobby
header('Location: lobby.php');
exit();
?>
