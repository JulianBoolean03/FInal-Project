<?php
/**
 * Test script for lobby chat
 */
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireAuth();

$userId = getCurrentUserId();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Lobby Chat</title>
</head>
<body>
    <h1>Testing Lobby Chat APIs</h1>
    <div id="results"></div>
    
    <script>
        const results = document.getElementById('results');
        
        // Test 1: Poll messages
        results.innerHTML += '<h2>Test 1: Polling messages...</h2>';
        fetch('api/lobby_chat_poll.php?since=0')
            .then(response => {
                results.innerHTML += '<p>Status: ' + response.status + '</p>';
                return response.json();
            })
            .then(data => {
                results.innerHTML += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            })
            .catch(error => {
                results.innerHTML += '<p style="color: red;">Error: ' + error.message + '</p>';
            });
        
        // Test 2: Send message
        setTimeout(() => {
            results.innerHTML += '<h2>Test 2: Sending test message...</h2>';
            fetch('api/lobby_chat_send.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: 'Test message from test script' })
            })
            .then(response => {
                results.innerHTML += '<p>Status: ' + response.status + '</p>';
                return response.json();
            })
            .then(data => {
                results.innerHTML += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            })
            .catch(error => {
                results.innerHTML += '<p style="color: red;">Error: ' + error.message + '</p>';
            });
        }, 2000);
    </script>
</body>
</html>
