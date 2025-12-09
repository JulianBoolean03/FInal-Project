<?php
/**
 * Index page - Login and Registration (Screen 1: Profile)
 * Users can create accounts or log in
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

startSession();

// If already logged in, redirect to lobby
if (isLoggedIn()) {
    header('Location: lobby.php');
    exit();
}

// Check for remember-me cookie
if (isset($_COOKIE['remember_user']) && !isLoggedIn()) {
    $username = $_COOKIE['remember_user'];
    
    // Verify user exists
    $stmt = executeQuery(
        "SELECT id, username FROM users WHERE username = ?",
        '',
        [$username]
    );
    
    if ($stmt) {
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            loginUser($row['id'], $row['username'], true);
            header('Location: lobby.php');
            exit();
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
    <title><?php echo SITE_NAME; ?> - Login</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="theme-classic">
    <div class="login-container">
        <div class="login-card">
            <h1 class="site-title">Reindeer Games</h1>
            <p class="site-subtitle">Holiday-Themed Fifteen Puzzle Challenge</p>
            
            <?php if ($error === 'auth_required'): ?>
                <div class="alert alert-error">Please log in to continue.</div>
            <?php elseif ($error === 'invalid_credentials'): ?>
                <div class="alert alert-error">Invalid username or password.</div>
            <?php elseif ($error === 'username_taken'): ?>
                <div class="alert alert-error">Username already taken.</div>
            <?php elseif ($error === 'invalid_username'): ?>
                <div class="alert alert-error">Username must be 3-20 characters (letters, numbers, underscore only).</div>
            <?php elseif ($error === 'invalid_password'): ?>
                <div class="alert alert-error">Password must be at least 6 characters.</div>
            <?php elseif ($success === 'registered'): ?>
                <div class="alert alert-success">Account created successfully! Please log in.</div>
            <?php elseif ($success === 'logout'): ?>
                <div class="alert alert-success">You have been logged out.</div>
            <?php endif; ?>
            
            <div class="tabs">
                <button class="tab-button active" data-tab="login">Login</button>
                <button class="tab-button" data-tab="register">Create Account</button>
            </div>
            
            <!-- Login Form -->
            <div id="login-tab" class="tab-content active">
                <form action="login.php" method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="login-username">Username</label>
                        <input type="text" id="login-username" name="username" required 
                               placeholder="Enter your username" autocomplete="username">
                    </div>
                    
                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <input type="password" id="login-password" name="password" required 
                               placeholder="Enter your password" autocomplete="current-password">
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="remember" value="1">
                            Remember me for 7 days
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-large">Login</button>
                </form>
            </div>
            
            <!-- Registration Form -->
            <div id="register-tab" class="tab-content">
                <form action="register.php" method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="register-username">Username</label>
                        <input type="text" id="register-username" name="username" required 
                               placeholder="Choose a username" autocomplete="username"
                               pattern="[a-zA-Z0-9_]{3,20}" 
                               title="3-20 characters: letters, numbers, underscore">
                    </div>
                    
                    <div class="form-group">
                        <label for="register-password">Password</label>
                        <input type="password" id="register-password" name="password" required 
                               placeholder="Choose a password" autocomplete="new-password"
                               minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="register-password-confirm">Confirm Password</label>
                        <input type="password" id="register-password-confirm" name="password_confirm" required 
                               placeholder="Confirm your password" autocomplete="new-password"
                               minlength="6">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-large">Create Account</button>
                </form>
            </div>
        </div>
        
        <div class="credits">
            <p>Created by Julian Robinson & Amanda Nguyen</p>
            <p>CSC Web Programming Final Project</p>
        </div>
    </div>
    
    <script>
        // Tab switching functionality
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                const tabName = button.dataset.tab;
                
                // Update active tab button
                document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                
                // Update active tab content
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                document.getElementById(tabName + '-tab').classList.add('active');
            });
        });
        
        // Password confirmation validation
        const registerForm = document.querySelector('#register-tab form');
        registerForm.addEventListener('submit', (e) => {
            const password = document.getElementById('register-password').value;
            const confirm = document.getElementById('register-password-confirm').value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html>
