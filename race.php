<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireAuth();
$userId = getCurrentUserId();
$username = getCurrentUsername();
$opponentId = $_GET['opponent_id'] ?? 0;
$opponentName = $_GET['opponent'] ?? 'Opponent';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Race Mode</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="theme-classic">
    <nav class="top-nav">
        <div class="nav-left">
            <h1 class="nav-title">🏁 Racing against <?php echo htmlspecialchars($opponentName); ?></h1>
        </div>
        <div class="nav-right">
            <span class="username-display" style="color: <?php echo $usernameColor; ?>; font-weight: bold;"><?php echo htmlspecialchars($username); ?></span>
        </div>
    </nav>
    
    <div class="game-container">
        <div class="game-left">
            <div class="card puzzle-card">
                <div class="game-stats">
                    <div class="stat">
                        <span class="stat-label">⏱️Time:</span>
                        <span id="timer" class="stat-value">00:00</span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">▶️Moves:</span>
                        <span id="move-counter" class="stat-value">0</span>
                    </div>
                </div>
                <div id="puzzle-board" class="puzzle-board"></div>
                <button id="forfeit-btn" class="btn btn-danger" style="margin-top: 1rem;">🥶Forfeit</button>
            </div>
        </div>
        
        <div class="game-right">
            <div class="card">
                <h3>🏁 Race Status</h3>
                <p id="race-status">Race in progress...</p>
            </div>
        </div>
    </div>
    
    <div id="win-modal" class="modal">
        <div class="modal-content card">
            <div id="result-message"></div>
            <div style="margin-top: 1rem;">
                <button onclick="window.location.href='quick_match.php'" class="btn btn-primary btn-large">Find New Match</button>
                <button onclick="window.location.href='lobby.php'" class="btn btn-secondary">Back to Lobby</button>
            </div>
        </div>
    </div>
    
    <script src="assets/js/common.js"></script>
    <script>
        const Race = {
            userId: <?php echo $userId; ?>,
            opponentId: <?php echo $opponentId; ?>,
            opponentName: '<?php echo addslashes($opponentName); ?>',
            board: [],
            emptyPos: 15,
            moves: 0,
            startTime: null,
            timerInterval: null,
            checkInterval: null,
            
            init: function() {
                this.startNewGame();
                this.startMonitoring();
                document.getElementById('forfeit-btn').addEventListener('click', () => this.handleForfeit());
            },
            
            startNewGame: function() {
                this.board = Array.from({length: 16}, (_, i) => i < 15 ? i + 1 : 0);
                this.emptyPos = 15;
                this.moves = 0;
                this.shufflePuzzle();
                this.renderBoard();
                this.startTimer();
            },
            
            shufflePuzzle: function() {
                for (let i = 0; i < 100; i++) {
                    const validMoves = this.getValidMoves();
                    const randomMove = validMoves[Math.floor(Math.random() * validMoves.length)];
                    this.swapTiles(this.emptyPos, randomMove);
                }
            },
            
            getValidMoves: function() {
                const row = Math.floor(this.emptyPos / 4);
                const col = this.emptyPos % 4;
                const moves = [];
                if (row > 0) moves.push(this.emptyPos - 4);
                if (row < 3) moves.push(this.emptyPos + 4);
                if (col > 0) moves.push(this.emptyPos - 1);
                if (col < 3) moves.push(this.emptyPos + 1);
                return moves;
            },
            
            swapTiles: function(pos1, pos2) {
                [this.board[pos1], this.board[pos2]] = [this.board[pos2], this.board[pos1]];
                this.emptyPos = pos2;
            },
            
            renderBoard: function() {
                const boardEl = document.getElementById('puzzle-board');
                boardEl.innerHTML = '';
                this.board.forEach((num, index) => {
                    const tile = document.createElement('div');
                    tile.className = 'puzzle-tile' + (num === 0 ? ' empty' : '');
                    tile.textContent = num === 0 ? '' : num;
                    if (num !== 0) {
                        tile.addEventListener('click', () => this.handleTileClick(index));
                    }
                    boardEl.appendChild(tile);
                });
            },
            
            handleTileClick: function(index) {
                const validMoves = this.getValidMoves();
                if (validMoves.includes(index)) {
                    this.swapTiles(this.emptyPos, index);
                    this.moves++;
                    document.getElementById('move-counter').textContent = this.moves;
                    this.renderBoard();
                    if (this.isSolved()) {
                        this.handleWin();
                    }
                }
            },
            
            isSolved: function() {
                for (let i = 0; i < 15; i++) {
                    if (this.board[i] !== i + 1) return false;
                }
                return this.board[15] === 0;
            },
            
            handleWin: function() {
                this.stopTimer();
                this.stopMonitoring();
                const timeMs = Date.now() - this.startTime;
                
                fetch('api/chat_send.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ room_id: 0, user_id: this.userId, message: 'RACE_WIN_' + this.userId })
                });
                
                document.getElementById('forfeit-btn').disabled = true;
                document.getElementById('win-modal').classList.add('show');
                document.getElementById('result-message').innerHTML = `
                    <h2 style="color: gold;">🏆 YOU WON! 🏆</h2>
                    <p><strong>Time:</strong> ${formatTime(timeMs)}</p>
                    <p><strong>Moves:</strong> ${this.moves}</p>
                    <p>You beat ${sanitizeHTML(this.opponentName)}!</p>
                `;
            },
            
            handleForfeit: function() {
                if (!confirm('Are you sure you want to forfeit?')) return;
                
                this.stopTimer();
                this.stopMonitoring();
                
                // Notify that current user forfeited
                fetch('api/chat_send.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ room_id: 0, user_id: this.userId, message: 'RACE_FORFEIT_' + this.userId })
                });

                // Notify opponent they won
                fetch('api/chat_send.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ room_id: 0, user_id: this.userId, message: 'RACE_WIN_' + this.userId })
                });
                
                // Show user who forfeited they lost before redirecting to lobby
                //window.location.href = 'lobby.php';
                this.handleLoss(true);
            },
            
            handleLoss: function(forfeited = false) {
                this.stopTimer();
                this.stopMonitoring();
                document.getElementById('forfeit-btn').disabled = true;
                document.getElementById('win-modal').classList.add('show');
                
                if (forfeited) { //Tells user they lost because they forfeited
                    document.getElementById('result-message').innerHTML = `
                        <h2 style="color: #ff6b6b;">You Lost!</h2>
                        <p>You forfeited the race & ${sanitizeHTML(this.opponentName)} won.</p>
                    `;
                } else {
                    document.getElementById('result-message').innerHTML = `
                        <h2 style="color: #ff6b6b;">You Lost!</h2>
                        <p>${sanitizeHTML(this.opponentName)} finished first!</p>
                    `;
                    setTimeout(() => window.location.href = 'lobby.php', 3000);
                }
            },

            //For handling when opponent forfeits
            handleOpponentForfeit: function() {
                this.stopTimer();
                this.stopMonitoring();
                
                // Record win in analytics (opponent forfeited, current user wins)
                fetch('api/record_race_win.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: this.userId })
                });
                
                document.getElementById('forfeit-btn').disabled = true;
                document.getElementById('win-modal').classList.add('show');
                    document.getElementById('result-message').innerHTML = `
                        <h2 style="color: gold;">🏆 YOU WON! 🏆</h2>
                        <p>${sanitizeHTML(this.opponentName)} forfeited!</p>
                    `;
            },
            
            startTimer: function() {
                this.startTime = Date.now();
                this.timerInterval = setInterval(() => {
                    document.getElementById('timer').textContent = formatTime(Date.now() - this.startTime);
                }, 100);
            },
            
            stopTimer: function() {
                if (this.timerInterval) clearInterval(this.timerInterval);
            },
            
            startMonitoring: function() {
                this.checkInterval = setInterval(async () => {
                    const response = await fetch('api/chat_poll.php?room_id=0');
                    const data = await response.json();
                    if (data.success && data.messages) {
                        // Check if opponent won
                        const opponentWin = data.messages.find(m => 
                            m.message && m.message.includes('RACE_WIN_') && m.user_id == this.opponentId
                        );
                        if (opponentWin) {
                            this.handleLoss(false);
                            return;
                        }
                        
                        // Check if opponent forfeited
                        const opponentForfeit = data.messages.find(m => 
                            m.message && m.message.includes('RACE_FORFEIT_') && m.user_id == this.opponentId
                        );
                        if (opponentForfeit) {
                            this.handleOpponentForfeit();
                            return;
                        }
                    }
                }, 1000);
            },
            
            stopMonitoring: function() {
                if (this.checkInterval) clearInterval(this.checkInterval);
            }
        };
        
        Race.init();
    </script>
    <script src="assets/js/theme.js"></script>
</body>
</html>
