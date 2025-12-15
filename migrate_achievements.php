#!/usr/bin/env php
<?php
/**
 * Migration Script for Race Win Achievements
 * Run this to add race_wins column and color achievements to existing databases
 * Usage: php migrate_achievements.php
 */

echo "=== Race Win Achievements Migration ===\n\n";

if (!file_exists('includes/db.php')) {
    die("Error: Please run this script from the project root directory.\n");
}

require_once 'includes/config.php';
require_once 'includes/db.php';

echo "Step 1: Connecting to database...\n";
try {
    $db = getDB();
    echo "✓ Successfully connected\n";
} catch (Exception $e) {
    die("✗ Database connection failed: " . $e->getMessage() . "\n");
}

echo "\nStep 2: Adding race_wins column to analytics table...\n";
try {
    // Check if column already exists
    $stmt = $db->query("PRAGMA table_info(analytics)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $hasRaceWins = false;
    
    foreach ($columns as $col) {
        if ($col['name'] === 'race_wins') {
            $hasRaceWins = true;
            break;
        }
    }
    
    if ($hasRaceWins) {
        echo "✓ Column 'race_wins' already exists\n";
    } else {
        $db->exec("ALTER TABLE analytics ADD COLUMN race_wins INTEGER DEFAULT 0");
        echo "✓ Added 'race_wins' column\n";
    }
} catch (Exception $e) {
    echo "✗ Error adding column: " . $e->getMessage() . "\n";
}

echo "\nStep 3: Adding color achievements...\n";
$achievements = [
    ['bronze_racer', 'Bronze Racer - #CD7F32', 'Win 1 race match', '🥉'],
    ['silver_racer', 'Silver Racer - #C0C0C0', 'Win 5 race matches', '🥈'],
    ['gold_racer', 'Gold Racer - #FFD700', 'Win 10 race matches', '🥇'],
    ['platinum_racer', 'Platinum Racer - #E5E4E2', 'Win 25 race matches', '💎'],
    ['legendary_racer', 'Legendary Racer - #FF0000', 'Win 50 race matches', '👑']
];

foreach ($achievements as $ach) {
    try {
        $stmt = $db->prepare("INSERT OR IGNORE INTO achievements (achievement_key, name, description, icon) VALUES (?, ?, ?, ?)");
        $stmt->execute($ach);
        echo "✓ Added achievement: {$ach[1]}\n";
    } catch (Exception $e) {
        echo "✗ Error adding {$ach[0]}: " . $e->getMessage() . "\n";
    }
}

echo "\nStep 4: Checking current data...\n";
$stmt = $db->query("SELECT COUNT(*) as count FROM achievements WHERE achievement_key LIKE '%_racer'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Race achievements in database: " . $row['count'] . "\n";

echo "\n=== Migration Complete ===\n";
echo "Color achievements are now available!\n";
echo "Users will earn colored usernames as they win races.\n";
?>
