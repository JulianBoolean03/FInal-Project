<?php
/**
 * Configuration file for Reindeer Games
 * Contains database credentials and application settings
 * IMPORTANT: Update these values for your codd.cs.gsu.edu environment
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'jrobinson262');  // Your codd username
define('DB_PASS', 'your_mysql_password');  // Your MySQL password on codd
define('DB_NAME', 'reindeer_games');

// Application settings
define('SITE_NAME', 'Reindeer Games');
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('COOKIE_LIFETIME', 604800); // 7 days in seconds

// Room settings
define('MAX_PLAYERS_PER_ROOM', 4);
define('ROOM_CODE_LENGTH', 6);
define('GAME_START_COUNTDOWN', 5); // seconds

// Polling intervals (milliseconds)
define('POLL_INTERVAL_LOBBY', 2000);
define('POLL_INTERVAL_GAME', 1500);
define('POLL_INTERVAL_CHAT', 1000);

// Power-up settings
define('POWERUP_HIGHLIGHT_COUNT', 3);
define('POWERUP_HINT_DURATION', 3000); // milliseconds

// Available themes
$THEMES = [
    'classic' => 'Classic Christmas',
    'snowy' => 'Snowy Night',
    'candycane' => 'Candy Cane Lane'
];

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('America/New_York');
?>
