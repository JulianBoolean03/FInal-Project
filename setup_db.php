#!/usr/bin/env php
<?php
/**
 * Database Setup Script
 * Run this on the remote server to initialize the database
 * Usage: php setup_db.php
 */

echo "=== Reindeer Games Database Setup ===\n\n";

// Check if we're in the right directory
if (!file_exists('includes/db.php')) {
    die("Error: Please run this script from the project root directory.\n");
}

require_once 'includes/config.php';
require_once 'includes/db.php';

echo "Step 1: Checking database file...\n";
$dbPath = __DIR__ . '/reindeer_games.db';
echo "Database path: $dbPath\n";

if (file_exists($dbPath)) {
    echo "✓ Database file exists\n";
    
    // Check permissions
    if (is_readable($dbPath)) {
        echo "✓ Database is readable\n";
    } else {
        echo "✗ Database is NOT readable\n";
        echo "  Run: chmod 666 reindeer_games.db\n";
    }
    
    if (is_writable($dbPath)) {
        echo "✓ Database is writable\n";
    } else {
        echo "✗ Database is NOT writable\n";
        echo "  Run: chmod 666 reindeer_games.db\n";
        die("Please fix permissions and try again.\n");
    }
} else {
    echo "Database file does not exist. Attempting to create...\n";
    
    // Check if directory is writable
    if (!is_writable(__DIR__)) {
        die("✗ Directory is not writable. Run: chmod 755 " . __DIR__ . "\n");
    }
    
    // Try to create the file
    if (touch($dbPath)) {
        echo "✓ Created database file\n";
        chmod($dbPath, 0666);
        echo "✓ Set permissions to 666\n";
    } else {
        die("✗ Failed to create database file. Check directory permissions.\n");
    }
}

echo "\nStep 2: Connecting to database...\n";
try {
    $db = getDB();
    echo "✓ Successfully connected to database\n";
} catch (Exception $e) {
    die("✗ Database connection failed: " . $e->getMessage() . "\n");
}

echo "\nStep 3: Checking tables...\n";
$tables = ['users', 'rooms', 'room_players', 'games', 'moves', 'chat_messages', 
           'achievements', 'user_achievements', 'analytics', 'story_segments'];

$allTablesExist = true;
foreach ($tables as $table) {
    $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
    $exists = $stmt->fetch();
    if ($exists) {
        echo "✓ Table '$table' exists\n";
    } else {
        echo "✗ Table '$table' missing\n";
        $allTablesExist = false;
    }
}

if (!$allTablesExist) {
    echo "\nTables are missing. Schema should have been initialized automatically.\n";
    echo "Check if schema_sqlite.sql exists and is readable.\n";
}

echo "\nStep 4: Checking data...\n";
$stmt = $db->query("SELECT COUNT(*) as count FROM users");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Users in database: " . $row['count'] . "\n";

$stmt = $db->query("SELECT COUNT(*) as count FROM story_segments");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Story segments: " . $row['count'] . "\n";

$stmt = $db->query("SELECT COUNT(*) as count FROM achievements");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Achievements: " . $row['count'] . "\n";

echo "\n=== Setup Complete ===\n";
echo "You can now use the application.\n";
echo "If you still have issues, check PHP error logs.\n";
?>
