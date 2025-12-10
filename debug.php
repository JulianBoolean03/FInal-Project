<?php
/**
 * Debug Script - Test database operations
 * Access via browser: https://codd.cs.gsu.edu/~jrobinson262/WP/PW/Final/debug.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

echo "<h1>Database Debug</h1>";

// Test 1: Database connection
echo "<h2>Test 1: Database Connection</h2>";
try {
    $db = getDB();
    echo "✓ Database connected<br>";
    $dbPath = __DIR__ . '/reindeer_games.db';
    echo "Database path: $dbPath<br>";
    echo "File exists: " . (file_exists($dbPath) ? "YES" : "NO") . "<br>";
    echo "Readable: " . (is_readable($dbPath) ? "YES" : "NO") . "<br>";
    echo "Writable: " . (is_writable($dbPath) ? "YES" : "NO") . "<br>";
} catch (Exception $e) {
    echo "✗ Connection failed: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Check tables
echo "<h2>Test 2: Database Tables</h2>";
$stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Tables: " . implode(", ", $tables) . "<br>";

// Test 3: Check users
echo "<h2>Test 3: Existing Users</h2>";
try {
    $stmt = $db->query("SELECT id, username, created_at FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Total users: " . count($users) . "<br>";
    if (count($users) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Username</th><th>Created At</th></tr>";
        foreach ($users as $user) {
            echo "<tr><td>{$user['id']}</td><td>{$user['username']}</td><td>{$user['created_at']}</td></tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// Test 4: Create test user
echo "<h2>Test 4: Create Test User</h2>";
$testUsername = "testuser_" . time();
$testPassword = "test123";
$passwordHash = password_hash($testPassword, PASSWORD_DEFAULT);

try {
    $stmt = $db->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
    $result = $stmt->execute([$testUsername, $passwordHash]);
    
    if ($result) {
        $userId = $db->lastInsertId();
        echo "✓ Test user created<br>";
        echo "Username: $testUsername<br>";
        echo "Password: $testPassword<br>";
        echo "User ID: $userId<br>";
        
        // Verify it was created
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "✓ User verified in database<br>";
            echo "Password hash length: " . strlen($user['password_hash']) . "<br>";
        } else {
            echo "✗ User NOT found after insert<br>";
        }
    } else {
        echo "✗ Insert failed<br>";
    }
} catch (Exception $e) {
    echo "✗ Error creating user: " . $e->getMessage() . "<br>";
}

// Test 5: Test login with the user we just created
echo "<h2>Test 5: Test Login</h2>";
try {
    $stmt = $db->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
    $stmt->execute([$testUsername]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✓ User found: {$user['username']}<br>";
        
        // Test password verification
        $passwordVerify = password_verify($testPassword, $user['password_hash']);
        echo "Password verification: " . ($passwordVerify ? "✓ SUCCESS" : "✗ FAILED") . "<br>";
        
        if ($passwordVerify) {
            echo "<strong>Login would work!</strong><br>";
        } else {
            echo "<strong>Login would fail - password mismatch</strong><br>";
        }
    } else {
        echo "✗ User not found<br>";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

// Test 6: Check for any existing users with simple passwords
echo "<h2>Test 6: Test Existing User Login</h2>";
if (count($users) > 0) {
    echo "<form method='POST'>";
    echo "Try logging in with an existing user:<br>";
    echo "Username: <input type='text' name='test_username' placeholder='Enter username'><br>";
    echo "Password: <input type='password' name='test_password' placeholder='Enter password'><br>";
    echo "<input type='submit' value='Test Login'>";
    echo "</form>";
    
    if (isset($_POST['test_username']) && isset($_POST['test_password'])) {
        $testUser = $_POST['test_username'];
        $testPass = $_POST['test_password'];
        
        $stmt = $db->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
        $stmt->execute([$testUser]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<div style='background: #ffffcc; padding: 10px; margin: 10px 0;'>";
            echo "User found: {$user['username']}<br>";
            echo "Password hash: " . substr($user['password_hash'], 0, 30) . "...<br>";
            
            $verify = password_verify($testPass, $user['password_hash']);
            if ($verify) {
                echo "<strong style='color: green;'>✓ LOGIN SUCCESSFUL</strong><br>";
            } else {
                echo "<strong style='color: red;'>✗ LOGIN FAILED - Wrong password</strong><br>";
            }
            echo "</div>";
        } else {
            echo "<div style='background: #ffcccc; padding: 10px; margin: 10px 0;'>";
            echo "✗ User '$testUser' not found in database";
            echo "</div>";
        }
    }
}

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Try creating a new account via the registration form</li>";
echo "<li>Then come back here and test login with that username</li>";
echo "<li>If registration shows 'success' but user doesn't appear above, the INSERT is failing silently</li>";
echo "</ol>";

// IMPORTANT: Delete this file after debugging
echo "<p style='color: red;'><strong>SECURITY WARNING: Delete this file after debugging!</strong></p>";
?>
