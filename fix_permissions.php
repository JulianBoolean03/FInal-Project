#!/usr/bin/env php
<?php
/**
 * Permission Diagnostic and Fix Script
 * Run: php fix_permissions.php
 */

echo "=== SQLite Permission Diagnostics ===\n\n";

$dbPath = __DIR__ . '/reindeer_games.db';
$dirPath = __DIR__;

// Check current state
echo "Database file: $dbPath\n";
echo "Directory: $dirPath\n\n";

// File checks
echo "--- File Status ---\n";
echo "File exists: " . (file_exists($dbPath) ? "YES" : "NO") . "\n";
echo "File readable: " . (is_readable($dbPath) ? "YES" : "NO") . "\n";
echo "File writable: " . (is_writable($dbPath) ? "YES" : "NO") . "\n";

if (file_exists($dbPath)) {
    $perms = fileperms($dbPath);
    echo "File permissions: " . substr(sprintf('%o', $perms), -4) . "\n";
    $owner = posix_getpwuid(fileowner($dbPath));
    echo "File owner: " . $owner['name'] . "\n";
}

// Directory checks
echo "\n--- Directory Status ---\n";
echo "Directory readable: " . (is_readable($dirPath) ? "YES" : "NO") . "\n";
echo "Directory writable: " . (is_writable($dirPath) ? "YES" : "NO") . "\n";

$dirPerms = fileperms($dirPath);
echo "Directory permissions: " . substr(sprintf('%o', $dirPerms), -4) . "\n";
$dirOwner = posix_getpwuid(fileowner($dirPath));
echo "Directory owner: " . $dirOwner['name'] . "\n";

// Web server user
echo "\n--- Web Server User ---\n";
$webUser = posix_getpwuid(posix_geteuid());
echo "PHP running as: " . $webUser['name'] . " (UID: " . posix_geteuid() . ")\n";
echo "PHP groups: " . implode(", ", array_map(function($gid) {
    $group = posix_getgrgid($gid);
    return $group['name'];
}, posix_getgroups())) . "\n";

// Check for lock files
echo "\n--- SQLite Lock Files ---\n";
$lockFiles = [
    $dbPath . '-shm',
    $dbPath . '-wal',
    $dbPath . '-journal'
];

foreach ($lockFiles as $lockFile) {
    if (file_exists($lockFile)) {
        echo "Found: " . basename($lockFile) . " (permissions: " . substr(sprintf('%o', fileperms($lockFile)), -4) . ")\n";
    } else {
        echo "Not present: " . basename($lockFile) . "\n";
    }
}

// Attempt fixes
echo "\n--- Attempting Fixes ---\n";

// Try to change file permissions
if (file_exists($dbPath)) {
    if (@chmod($dbPath, 0666)) {
        echo "✓ Set database file to 666\n";
    } else {
        echo "✗ Could not change database file permissions\n";
    }
}

// Try to change directory permissions
if (@chmod($dirPath, 0777)) {
    echo "✓ Set directory to 777\n";
} else {
    echo "✗ Could not change directory permissions\n";
}

// Try a test write to directory
$testFile = $dirPath . '/test_write_' . time() . '.tmp';
if (@file_put_contents($testFile, 'test')) {
    echo "✓ Can create files in directory\n";
    @unlink($testFile);
} else {
    echo "✗ Cannot create files in directory\n";
}

// Try to write to database
echo "\n--- Testing Database Write ---\n";
try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Try a simple write
    $pdo->exec("CREATE TABLE IF NOT EXISTS test_write (id INTEGER PRIMARY KEY, data TEXT)");
    $stmt = $pdo->prepare("INSERT INTO test_write (data) VALUES (?)");
    $result = $stmt->execute(['test_' . time()]);
    
    if ($result) {
        echo "✓ Successfully wrote to database!\n";
        $pdo->exec("DROP TABLE test_write");
    } else {
        echo "✗ Write operation returned false\n";
    }
} catch (PDOException $e) {
    echo "✗ Database write failed: " . $e->getMessage() . "\n";
    
    // Check specific error
    if (strpos($e->getMessage(), 'readonly') !== false) {
        echo "\n--- READONLY DATABASE ERROR ---\n";
        echo "This typically means:\n";
        echo "1. The web server user doesn't have write permission to the directory\n";
        echo "2. SELinux is blocking writes\n";
        echo "3. The filesystem is mounted read-only\n\n";
        
        echo "Try these commands on the server:\n";
        echo "chmod 777 " . $dirPath . "\n";
        echo "chmod 666 " . $dbPath . "\n";
        echo "chown -R \$USER:www-data " . $dirPath . "\n";
        echo "# Or if SELinux:\n";
        echo "chcon -R -t httpd_sys_rw_content_t " . $dirPath . "\n";
    }
}

echo "\n=== Manual Fix Commands ===\n";
echo "If the above fixes didn't work, SSH to server and run:\n\n";
echo "cd " . $dirPath . "\n";
echo "chmod 777 .\n";
echo "chmod 666 reindeer_games.db\n";
echo "chown \$(whoami):www-data .\n";
echo "chown \$(whoami):www-data reindeer_games.db\n";
echo "\n# Check what user Apache runs as:\n";
echo "ps aux | grep apache\n";
echo "# or\n";
echo "ps aux | grep httpd\n";

?>
