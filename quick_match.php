<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireAuth();
$userId = getCurrentUserId();
$username = getCurrentUsername();
$db = getDB();

executeQuery("DELETE FROM room_players WHERE user_id = ?", '', [$userId]);
$stmt = executeQuery("SELECT a.user_id, u.username FROM analytics a JOIN users u ON a.user_id = u.id WHERE a.best_time_ms = 999999999 AND a.user_id != ? LIMIT 1", '', [$userId]);
$waitingPlayer = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
if ($waitingPlayer) {
    $opponentId = $waitingPlayer['user_id'];
    $opponentName = $waitingPlayer['username'];
    executeQuery("UPDATE analytics SET best_time_ms = 888888888 WHERE user_id = ?", '', [$opponentId]);
    executeQuery("UPDATE analytics SET best_time_ms = 888888888 WHERE user_id = ?", '', [$userId]);
    header('Location: race.php?opponent=' . urlencode($opponentName) . '&opponent_id=' . $opponentId);
    exit();
} else {
    $stmt = executeQuery("SELECT best_time_ms FROM analytics WHERE user_id = ?", '', [$userId]);
    $analytics = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
    if ($analytics && $analytics['best_time_ms'] == 888888888) {
        executeQuery("UPDATE analytics SET best_time_ms = 0 WHERE user_id = ?", '', [$userId]);
        header('Location: race.php?opponent=Opponent&opponent_id=0');
        exit();
    }
    executeQuery("UPDATE analytics SET best_time_ms = 999999999 WHERE user_id = ?", '', [$userId]);
    if (!$analytics) executeQuery("INSERT INTO analytics (user_id, best_time_ms) VALUES (?, 999999999)", '', [$userId]);
?>
<!DOCTYPE html><html><head><title>Finding Match...</title><link rel="stylesheet" href="assets/css/styles.css"></head><body class="theme-classic"><div style="display:flex;align-items:center;justify-content:center;height:100vh;flex-direction:column;"><h1>🎄 Finding Opponent... 🎄</h1><p>Waiting for another player...</p><div class="spinner" style="border:4px solid #f3f3f3;border-top:4px solid #3498db;border-radius:50%;width:50px;height:50px;animation:spin 1s linear infinite;margin:20px;"></div><a href="lobby.php" class="btn btn-danger">Cancel</a></div><style>@keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}</style><script>setTimeout(()=>{window.location.reload();},500);</script></body></html>
<?php } ?>
<script src="assets/js/theme.js"></script>
