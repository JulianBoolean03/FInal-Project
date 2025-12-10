/**
 * Practice Mode JavaScript
 * Single-player puzzle game
 */

const Practice = {
    userId: null,
    board: [],
    emptyPos: 15,
    moves: 0,
    startTime: null,
    timerInterval: null,
    stats: { gamesPlayed: 0, solved: 0, bestTime: 0, totalMoves: 0 },
    recentGames: [],
    raceMode: false,
    opponentId: null,
    opponentName: '',
    raceCheckInterval: null,
    
    init: function(userId) {
        this.userId = userId;
        this.loadStats();
        this.setupEventListeners();
        this.startNewGame();
    },
    
    initRace: function(userId, opponentId, opponentName) {
        this.userId = userId;
        this.opponentId = opponentId;
        this.opponentName = opponentName;
        this.raceMode = true;
        // Don't load stats in race mode
        this.setupEventListeners();
        this.startNewGame();
        this.startRaceMonitoring();
    },
    
    setupEventListeners: function() {
        document.getElementById('new-game-btn').addEventListener('click', () => this.startNewGame());
        document.getElementById('powerup-hint').addEventListener('click', () => this.useHint());
        document.getElementById('powerup-solve').addEventListener('click', () => this.usePeek());
        document.getElementById('powerup-reset').addEventListener('click', () => this.resetPuzzle());
        document.getElementById('play-again-btn').addEventListener('click', () => {
            document.getElementById('win-modal').classList.remove('show');
            this.startNewGame();
        });
        document.getElementById('back-lobby-btn').addEventListener('click', () => {
            window.location.href = 'lobby.php';
        });
    },
    
    startNewGame: function() {
        this.board = Array.from({length: 16}, (_, i) => i < 15 ? i + 1 : 0);
        this.emptyPos = 15;
        this.moves = 0;
        this.shufflePuzzle();
        this.renderBoard();
        this.updateMoveCounter();
        this.startTimer();
        
        if (!this.raceMode) {
            this.stats.gamesPlayed++;
            this.saveStats();
            this.updateStatsDisplay();
        }
    },
    
    shufflePuzzle: function() {
        // Perform 100 random valid moves to ensure solvability
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
        
        if (row > 0) moves.push(this.emptyPos - 4); // Up
        if (row < 3) moves.push(this.emptyPos + 4); // Down
        if (col > 0) moves.push(this.emptyPos - 1); // Left
        if (col < 3) moves.push(this.emptyPos + 1); // Right
        
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
            tile.dataset.index = index;
            
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
            this.updateMoveCounter();
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
        
        if (this.raceMode) {
            // Mark as finished in race
            this.stopRaceMonitoring();
            
            // Mark self as winner (set flag to 777777777)
            fetch('api/chat_send.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    room_id: 0, 
                    user_id: this.userId,
                    message: 'RACE_WIN_' + this.userId 
                })
            });
            
            // Show win modal
            const modal = document.getElementById('win-modal');
            modal.classList.add('show');
            document.getElementById('completion-stats').innerHTML = `
                <h2 style="color: gold;">🏆 YOU WON! 🏆</h2>
                <p><strong>Time:</strong> ${formatTime(timeMs)}</p>
                <p><strong>Moves:</strong> ${this.moves}</p>
                <p>You beat ${sanitizeHTML(this.opponentName)}!</p>
            `;
            
            document.getElementById('play-again-btn').textContent = 'Find New Match';
            document.getElementById('play-again-btn').onclick = () => {
                window.location.href = 'quick_match.php';
            };
            return;
        }
        
        // Normal practice mode win
        this.stats.solved++;
        this.stats.totalMoves += this.moves;
        if (this.stats.bestTime === 0 || timeMs < this.stats.bestTime) {
            this.stats.bestTime = timeMs;
        }
        
        this.recentGames.unshift({
            time: timeMs,
            moves: this.moves,
            date: new Date().toLocaleTimeString()
        });
        if (this.recentGames.length > 5) this.recentGames.pop();
        
        this.saveStats();
        this.updateStatsDisplay();
        this.displayRecentGames();
        
        const modal = document.getElementById('win-modal');
        modal.classList.add('show');
        
        document.getElementById('completion-stats').innerHTML = `
            <p><strong>Time:</strong> ${formatTime(timeMs)}</p>
            <p><strong>Moves:</strong> ${this.moves}</p>
            ${timeMs === this.stats.bestTime ? '<p style="color: var(--accent);">New Best Time!</p>' : ''}
        `;
    },
    
    startRaceMonitoring: function() {
        // Check every second if opponent finished or left
        this.raceCheckInterval = setInterval(async () => {
            // Check if opponent finished (look for their win marker)
            const response = await fetch('api/chat_poll.php?room_id=0');
            const data = await response.json();
            
            if (data.success && data.messages) {
                const opponentWin = data.messages.find(m => 
                    m.message && m.message.includes('RACE_WIN_') && m.user_id == this.opponentId
                );
                
                if (opponentWin) {
                    this.handleRaceLoss();
                }
            }
        }, 1000);
        
        // Also detect if user closes tab/navigates away
        window.addEventListener('beforeunload', () => {
            if (this.raceMode) {
                this.stopRaceMonitoring();
            }
        });
    },
    
    stopRaceMonitoring: function() {
        if (this.raceCheckInterval) {
            clearInterval(this.raceCheckInterval);
            this.raceCheckInterval = null;
        }
    },
    
    handleRaceLoss: function() {
        this.stopTimer();
        this.stopRaceMonitoring();
        
        const modal = document.getElementById('win-modal');
        modal.classList.add('show');
        document.getElementById('completion-stats').innerHTML = `
            <h2 style="color: #ff6b6b;">You Lost!</h2>
            <p>${sanitizeHTML(this.opponentName)} finished first!</p>
            <p>Better luck next time!</p>
        `;
        
        document.getElementById('play-again-btn').textContent = 'Find New Match';
        document.getElementById('play-again-btn').onclick = () => {
            window.location.href = 'quick_match.php';
        };
        
        // Auto-redirect after 3 seconds
        setTimeout(() => {
            window.location.href = 'lobby.php';
        }, 3000);
    },
    
    startTimer: function() {
        this.startTime = Date.now();
        this.timerInterval = setInterval(() => {
            const elapsed = Date.now() - this.startTime;
            document.getElementById('timer').textContent = formatTime(elapsed);
        }, 100);
    },
    
    stopTimer: function() {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
            this.timerInterval = null;
        }
    },
    
    updateMoveCounter: function() {
        document.getElementById('move-counter').textContent = this.moves;
    },
    
    useHint: function() {
        const wrongTiles = [];
        for (let i = 0; i < 15; i++) {
            if (this.board[i] !== i + 1) {
                wrongTiles.push(i);
            }
        }
        
        const tilesToHighlight = wrongTiles.sort(() => 0.5 - Math.random()).slice(0, 3);
        const tiles = document.querySelectorAll('.puzzle-tile');
        
        tilesToHighlight.forEach(index => {
            tiles[index].classList.add('hint');
        });
        
        setTimeout(() => {
            tiles.forEach(tile => tile.classList.remove('hint'));
        }, 2000);
    },
    
    usePeek: function() {
        const tiles = document.querySelectorAll('.puzzle-tile');
        
        tiles.forEach((tile, index) => {
            if (index < 15) {
                const originalText = tile.textContent;
                tile.textContent = index + 1;
                tile.classList.add('solved-preview');
                
                setTimeout(() => {
                    tile.textContent = originalText;
                    tile.classList.remove('solved-preview');
                }, 1500);
            }
        });
    },
    
    resetPuzzle: function() {
        if (confirm('Reset the current puzzle? Your progress will be lost.')) {
            this.startNewGame();
        }
    },
    
    loadStats: function() {
        const saved = localStorage.getItem('practice_stats');
        if (saved) {
            this.stats = JSON.parse(saved);
        }
        
        const savedGames = localStorage.getItem('practice_recent');
        if (savedGames) {
            this.recentGames = JSON.parse(savedGames);
        }
    },
    
    saveStats: function() {
        localStorage.setItem('practice_stats', JSON.stringify(this.stats));
        localStorage.setItem('practice_recent', JSON.stringify(this.recentGames));
    },
    
    updateStatsDisplay: function() {
        document.getElementById('games-count').textContent = this.stats.gamesPlayed;
        document.getElementById('solved-count').textContent = this.stats.solved;
        document.getElementById('best-time-stat').textContent = 
            this.stats.bestTime > 0 ? formatTime(this.stats.bestTime) : '--:--';
        document.getElementById('best-time').textContent = 
            this.stats.bestTime > 0 ? formatTime(this.stats.bestTime) : '--:--';
        
        const avgMoves = this.stats.solved > 0 ? 
            Math.round(this.stats.totalMoves / this.stats.solved) : 0;
        document.getElementById('avg-moves-stat').textContent = avgMoves || '--';
        
        this.displayRecentGames();
    },
    
    displayRecentGames: function() {
        const container = document.getElementById('recent-games');
        
        if (this.recentGames.length === 0) {
            container.innerHTML = '<p style="color: var(--text-muted);">No games yet</p>';
            return;
        }
        
        container.innerHTML = this.recentGames.map((game, i) => `
            <div class="leaderboard-item" style="animation: slideIn 0.3s ease ${i * 0.1}s;">
                <span>${game.date}</span>
                <span>${formatTime(game.time)} / ${game.moves} moves</span>
            </div>
        `).join('');
    }
};
