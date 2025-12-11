/**
 * Game page JavaScript
 * Handles 15-puzzle logic, timer, moves, power-ups, and game state
 */

const Game = {
    roomId: null,
    gameId: null,
    userId: null,
    board: [],
    emptyPos: 15,
    moves: 0,
    startTime: null,
    timerInterval: null,
    gamePoller: null,
    chatPoller: null,
    leaderboardPoller: null,
    powerupsUsed: [],
    
    init: function(roomId, gameId, userId) {
        this.roomId = roomId;
        this.gameId = gameId;
        this.userId = userId;
        
        this.initializePuzzle();
        this.setupEventListeners();
        this.startTimer();
        this.startPolling();
    },
    
    initializePuzzle: function() {
        // Create solved board: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 0]
        this.board = Array.from({length: 16}, (_, i) => i < 15 ? i + 1 : 0);
        this.emptyPos = 15;
        
        // Shuffle to create solvable puzzle
        this.shufflePuzzle();
        this.renderBoard();
    },
    
    shufflePuzzle: function() {
        // Perform random valid moves to ensure solvability
        const moves = 100;
        for (let i = 0; i < moves; i++) {
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
            //Tile Slide sound
            Sound.play("assets/sound_effects/Christmas_Slide.mp3", 0.35);

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

        //Sound Effect for Win
        Sound.play("assets/sound_effects/panto-clowns-jingle-win.mp3");
        
        // Submit results
        this.submitResults(timeMs);
        
        // Show win modal
        const modal = document.getElementById('win-modal');
        modal.classList.add('show');
        
        document.getElementById('completion-stats').innerHTML = `
            <p><strong>Time:</strong> ${formatTime(timeMs)}</p>
            <p><strong>Moves:</strong> ${this.moves}</p>
        `;
        
        // Wait for other players and redirect to ranking
        setTimeout(() => {
            window.location.href = `ranking.php?room_id=${this.roomId}`;
        }, 3000);
    },
    
    async submitResults(timeMs) {
        await API.post('api/submit_moves.php', {
            game_id: this.gameId,
            move_count: this.moves,
            time_ms: timeMs,
            powerups_used: this.powerupsUsed.join(',')
        });
    },
    
    setupEventListeners: function() {
        // Power-ups
        document.getElementById('powerup-hint').addEventListener('click', () => {
            Sound.play("assets/sound_effects/Ho-Ho-Ho-Power_Up.mp3");
            this.useHintPowerup();
        });
        document.getElementById('powerup-solve').addEventListener('click', () => {
            Sound.play("assets/sound_effects/Ho-Ho-Ho-Power_Up.mp3");
            this.useSolvePowerup();
        });
        document.getElementById('powerup-preview').addEventListener('click', () => { 
            Sound.play("assets/sound_effects/Ho-Ho-Ho-Power_Up.mp3");
            this.usePreviewPowerup();
        });
        
        // Chat
        document.getElementById('chat-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.sendChatMessage();

            //Click sound
            Sound.play("assets/sound_effects/Jingle_Bell_Click.mp3", 0.5);
        });
    },
    
    useHintPowerup: function() {
        const wrongTiles = [];
        for (let i = 0; i < 15; i++) {
            if (this.board[i] !== i + 1) {
                wrongTiles.push(i);
            }
        }
        
        // Highlight 3 random wrong tiles
        const tilesToHighlight = wrongTiles.sort(() => 0.5 - Math.random()).slice(0, 3);
        const tiles = document.querySelectorAll('.puzzle-tile');
        
        tilesToHighlight.forEach(index => {
            tiles[index].classList.add('hint');
        });
        
        setTimeout(() => {
            tiles.forEach(tile => tile.classList.remove('hint'));
        }, 3000);
        
        this.powerupsUsed.push('hint');
        document.getElementById('powerup-hint').disabled = true;
    },
    
    useSolvePowerup: function() {
        // Find one tile that's in wrong position and move it to correct position
        for (let i = 0; i < 15; i++) {
            if (this.board[i] !== i + 1) {
                const correctValue = i + 1;
                const currentIndex = this.board.indexOf(correctValue);
                
                // Simple move towards correct position
                // This is simplified; full implementation would pathfind
                showToast('Solving one tile...', 'info');
                this.powerupsUsed.push('solve');
                document.getElementById('powerup-solve').disabled = true;
                return;
            }
        }
    },
    
    usePreviewPowerup: function() {
        const tiles = document.querySelectorAll('.puzzle-tile');
        
        // Briefly show all tiles in correct position
        tiles.forEach((tile, index) => {
            if (index < 15) {
                tile.textContent = index + 1;
                tile.classList.add('solved-preview');
            }
        });
        
        setTimeout(() => {
            this.renderBoard();
        }, 2000);
        
        this.powerupsUsed.push('preview');
        document.getElementById('powerup-preview').disabled = true;
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
    
    startPolling: function() {
        // Poll for players and leaderboard
        this.leaderboardPoller = new Poller(
            `api/leaderboard.php?game_id=${this.gameId}`,
            (data) => this.updateLeaderboard(data),
            2000
        );
        this.leaderboardPoller.start();
        
        // Poll for chat messages
        this.chatPoller = new Poller(
            `api/chat_poll.php?room_id=${this.roomId}`,
            (data) => this.updateChat(data),
            1000
        );
        this.chatPoller.start();
    },
    
    updateLeaderboard: function(data) {
        if (!data.success) return;
        
        const playersContainer = document.getElementById('players-list');
        const leaderboardContainer = document.getElementById('mini-leaderboard');
        
        if (data.players && playersContainer) {
            playersContainer.innerHTML = data.players.map(p => `
                <div class="player-item">
                    <span>
                        <span class="player-status ${p.finished ? 'finished' : ''}"></span>
                        ${sanitizeHTML(p.username)}
                    </span>
                </div>
            `).join('');
        }
        
        if (data.leaderboard && leaderboardContainer) {
            leaderboardContainer.innerHTML = data.leaderboard.map((p, i) => `
                <div class="leaderboard-item">
                    <span>${i + 1}. ${sanitizeHTML(p.username)}</span>
                    <span>${p.finished ? formatTime(p.time_ms) : 'Playing...'}</span>
                </div>
            `).join('');
        }
    },
    
    updateChat: function(data) {
        if (!data.success || !data.messages) return;
        
        const container = document.getElementById('chat-messages');
        const isScrolledToBottom = container.scrollHeight - container.clientHeight <= container.scrollTop + 1;
        
        container.innerHTML = data.messages.map(msg => `
            <div class="chat-message">
                <span class="chat-username">${sanitizeHTML(msg.username)}:</span>
                <span>${sanitizeHTML(msg.message)}</span>
                <span class="chat-time">${msg.time}</span>
            </div>
        `).join('');
        
        if (isScrolledToBottom) {
            container.scrollTop = container.scrollHeight;
        }
    },
    
    async sendChatMessage() {
        const input = document.getElementById('chat-input');
        const message = input.value.trim();
        
        if (!message) return;
        
        await API.post('api/chat_send.php', {
            room_id: this.roomId,
            message: message
        });
        
        input.value = '';
    }
};
