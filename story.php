<?php
/**
 * Story page (Screen 3) - Display Christmas story segments
 * Shows story before each puzzle round
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireAuth();

$userId = getCurrentUserId();
$username = getCurrentUsername();
$roomId = $_GET['room_id'] ?? null;

if (!$roomId || !isInRoom($userId, $roomId)) {
    header('Location: lobby.php');
    exit();
}

// Get current game and story segment
$stmt = executeQuery(
    "SELECT g.id, g.round_number, g.story_segment_id, s.title, s.text, r.status
     FROM games g
     JOIN rooms r ON g.room_id = r.id
     LEFT JOIN story_segments s ON g.story_segment_id = s.id
     WHERE g.room_id = ? 
     ORDER BY g.id DESC 
     LIMIT 1",
    'i',
    [$roomId]
);

$game = null;
$story = null;

if ($stmt) {
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    
    if ($data) {
        $game = $data;
        $story = [
            'title' => $data['title'] ?? 'Chapter ' . $data['round_number'],
            'text' => $data['text'] ?? 'Get ready for the next puzzle!'
        ];
    }
}

if (!$story) {
    // No game found yet, show default story
    $game = ['round_number' => 1];
    $story = [
        'title' => 'Get Ready!',
        'text' => 'The game is about to begin. Prepare yourself for the Christmas puzzle challenge!'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Story</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="theme-classic">
    <nav class="top-nav">
        <div class="nav-left">
            <h1 class="nav-title">Reindeer Games</h1>
        </div>
        <div class="nav-right">
            <span class="username-display"><?php echo htmlspecialchars($username); ?></span>
        </div>
    </nav>
    
    <div class="container story-container">
        <div class="story-card card">
            <div class="story-header">
                <h1 class="story-title"><?php echo htmlspecialchars($story['title']); ?></h1>
                <div class="story-round">Round <?php echo $game['round_number']; ?></div>
            </div>
            
            <div class="story-content">
                <p><?php echo nl2br(htmlspecialchars($story['text'])); ?></p>
            </div>
            
            <div class="story-actions">
                <div class="countdown-display">
                    <p>Starting game in <span id="countdown">5</span> seconds...</p>
                </div>
                <button id="continue-btn" class="btn btn-primary btn-large">Continue to Puzzle</button>
            </div>
        </div>
        
        <div class="card players-waiting">
            <h3>Players Ready</h3>
            <div id="players-list"></div>
        </div>
    </div>
    
    <script src="assets/js/common.js"></script>
    <script src="assets/js/story.js"></script>
    <script>
        const roomId = <?php echo $roomId; ?>;
        Story.init(roomId);
    </script>
</body>
</html>
