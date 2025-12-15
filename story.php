<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireAuth();
$userId = getCurrentUserId();
$username = getCurrentUsername();

$story = [
    "It's Christmas Eve at the North Pole! 🎄",
    "Santa's elves have scrambled the toy delivery map!",
    "Help Santa solve the puzzle to save Christmas!",
    "Every tile you move gets Santa closer to his sleigh...",
    "🎅 Ho Ho Ho! Let's deliver some joy! 🎁"
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Christmas Story Mode</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="theme-classic">
    <nav class="top-nav">
        <div class="nav-left">
            <h1 class="nav-title">🎄 Christmas Story Mode 🎅</h1>
        </div>
        <div class="nav-right">
            <span class="username-display" style="color: <?php echo $usernameColor; ?>; font-weight: bold;"><?php echo htmlspecialchars($username); ?></span>
            <a href="lobby.php" class="btn btn-small">Back to Lobby</a>
        </div>
    </nav>
    
    <div class="container" style="max-width: 800px; margin: 2rem auto;">
        <div class="card">
            <div id="story-text" style="text-align: center; font-size: 1.5rem; padding: 2rem; min-height: 200px;">
                <p style="margin-bottom: 2rem;"><?php echo $story[0]; ?></p>
            </div>
            <div style="text-align: center;">
                <button id="next-btn" class="btn btn-primary btn-large">Next</button>
            </div>
        </div>
        
        <div id="puzzle-section" class="card" style="display: none;">
            <h2 style="text-align: center;">Help Santa Solve This!</h2>
            <div class="game-stats" style="display: flex; justify-content: center; gap: 2rem; margin-bottom: 1rem;">
                <div class="stat">
                    <span class="stat-label">Time:</span>
                    <span id="timer" class="stat-value">00:00</span>
                </div>
                <div class="stat">
                    <span class="stat-label">Moves:</span>
                    <span id="move-counter" class="stat-value">0</span>
                </div>
            </div>
            <div id="puzzle-board" class="puzzle-board"></div>
        </div>
    </div>
    
    <div id="win-modal" class="modal">
        <div class="modal-content card">
            <h2>🎄 Christmas Saved! 🎅</h2>
            <div id="completion-stats"></div>
            <p style="font-size: 1.2rem; margin-top: 1rem;">Santa's sleigh is ready to deliver presents!</p>
            <div style="margin-top: 1rem;">
                <button onclick="window.location.reload()" class="btn btn-primary btn-large">Play Again</button>
                <button onclick="window.location.href='lobby.php'" class="btn btn-secondary">Back to Lobby</button>
            </div>
        </div>
    </div>
    
    <script src="assets/js/common.js"></script>
    <script>
        let currentStory = 0;
        const story = <?php echo json_encode($story); ?>;
        
        // Simple puzzle implementation
        const Puzzle = {
            board: [],
            emptyPos: 15,
            moves: 0,
            startTime: null,
            timerInterval: null,
            
            init: function() {
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
                const timeMs = Date.now() - this.startTime;
                document.getElementById('win-modal').classList.add('show');
                document.getElementById('completion-stats').innerHTML = `
                    <p><strong>Time:</strong> ${formatTime(timeMs)}</p>
                    <p><strong>Moves:</strong> ${this.moves}</p>
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
            }
        };
        
        document.getElementById('next-btn').addEventListener('click', () => {
            currentStory++;
            if (currentStory < story.length) {
                document.getElementById('story-text').innerHTML = '<p style="margin-bottom: 2rem;">' + story[currentStory] + '</p>';
            } else {
                document.querySelector('.card').style.display = 'none';
                document.getElementById('puzzle-section').style.display = 'block';
                Puzzle.init();
            }
        });
    </script>
    <script src="assets/js/theme.js"></script>
</body>
</html>
