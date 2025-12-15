/**
 * Lobby page JavaScript
 * Handles room creation, joining, and lobby state
 */

const Lobby = {
  roomPoller: null,
  statsPoller: null,

  initLobby: function () {
    this.setupEventListeners();
    this.loadPublicRooms();
    this.loadStats();
    this.loadAchievements();

    // Start polling for public rooms
    this.roomPoller = new Poller('api/room_state.php?action=list_public', (data) => {
      if (data.success && data.rooms) {
        this.displayPublicRooms(data.rooms);
      }
    }, 3000);
    this.roomPoller.start();
  },

  initCurrentRoom: function (roomId) {
    this.setupEventListeners();
    this.loadRoomInfo(roomId);

    // Poll current room state
    let redirecting = false;
    this.roomPoller = new Poller(`api/room_state.php?room_id=${roomId}`, (data) => {
      if (data.success && data.room) {
        this.displayCurrentRoom(data.room);

        // Redirect if game is starting (but only once)
        if (!redirecting && (data.room.status === 'in_progress' || data.room.status === 'starting')) {
          redirecting = true;
          console.log('Game starting, redirecting to game page...');
          this.roomPoller.stop();
          // Delay to ensure game record is created
          setTimeout(() => {
            console.log('Redirecting now...');
            window.location.href = `game.php?room_id=${roomId}`;
          }, 1500);
        }
      }
    }, 2000);
    this.roomPoller.start();
  },

  setupEventListeners: function () {
    console.log('Setting up event listeners...');

    const joinPublicBtn = document.getElementById('join-public-btn');
    if (joinPublicBtn) {
      console.log('Join public button found');
      joinPublicBtn.addEventListener('click', () => {
        console.log('Join public clicked');
        this.joinPublicRoom();
      });
    } else {
      console.log('Join public button NOT found');
    }

    const createPrivateBtn = document.getElementById('create-private-btn');
    if (createPrivateBtn) {
      console.log('Create private button found');
      createPrivateBtn.addEventListener('click', () => {
        console.log('Create private clicked');
        this.createPrivateRoom();
      });
    } else {
      console.log('Create private button NOT found');
    }

    const joinPrivateForm = document.getElementById('join-private-form');
    if (joinPrivateForm) {
      joinPrivateForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const code = document.getElementById('room-code-input').value.trim().toUpperCase();
        if (code) this.joinPrivateRoom(code);
      });
    }

    const leaveRoomBtn = document.getElementById('leave-room-btn');
    if (leaveRoomBtn) {
      leaveRoomBtn.addEventListener('click', () => this.leaveRoom());
    }
  },

  async joinPublicRoom() {
    console.log('joinPublicRoom called');
    showToast('Joining public room...', 'info');

    const result = await API.post('api/join_room.php', { type: 'public' });
    console.log('Join public result:', result);

    if (result.success) {
      showToast('Joined room!', 'success');
      setTimeout(() => {
        window.location.href = `lobby.php`;
      }, 500);
    } else {
      showToast(result.message || result.error || 'Failed to join room', 'error');
    }
  },

  async createPrivateRoom() {
    console.log('createPrivateRoom called');
    showToast('Creating private room...', 'info');

    const result = await API.post('api/create_room.php', { is_private: true });
    console.log('Create room result:', result);

    if (result.success) {
      showToast(`Room created! Code: ${result.code}`, 'success');
      setTimeout(() => {
        window.location.href = `lobby.php`;
      }, 1000);
    } else {
      showToast(result.message || result.error || 'Failed to create room', 'error');
    }
  },

  async joinPrivateRoom(code) {
    const result = await API.post('api/join_room.php', { type: 'private', code: code });
    if (result.success) {
      window.location.href = `lobby.php`;
    } else {
      showToast(result.message || 'Invalid room code', 'error');
    }
  },

  async leaveRoom() {
    const result = await API.post('api/join_room.php', { action: 'leave' });
    if (result.success) {
      window.location.href = `lobby.php`;
    }
  },

  async loadPublicRooms() {
    const data = await API.get('api/room_state.php?action=list_public');
    if (data.success && data.rooms) {
      this.displayPublicRooms(data.rooms);
    }
  },

  displayPublicRooms: function (rooms) {
    const container = document.getElementById('public-rooms-list');
    if (!container) return;

    if (rooms.length === 0) {
      container.innerHTML = '<p class="text-muted">No public rooms available. Create one!</p>';
      return;
    }

    container.innerHTML = rooms.map(room => `
            <div class="player-item">
                <div>
                    <strong>Room ${room.code}</strong>
                    <span style="margin-left: 1rem; color: var(--text-muted);">
                        ${room.player_count}/${room.max_players} players
                    </span>
                </div>
                <button class="btn btn-small btn-primary" onclick="Lobby.joinRoomById(${room.id})">
                    Join
                </button>
            </div>
        `).join('');
  },

  async joinRoomById(roomId) {
    const result = await API.post('api/join_room.php', { room_id: roomId });
    if (result.success) {
      window.location.href = `lobby.php`;
    } else {
      showToast(result.message || 'Failed to join room', 'error');
    }
  },

  async loadRoomInfo(roomId) {
    const data = await API.get(`api/room_state.php?room_id=${roomId}`);
    if (data.success && data.room) {
      this.displayCurrentRoom(data.room);
    }
  },

  displayCurrentRoom: function (room) {
    const container = document.getElementById('current-room-info');
    if (!container) return;

    const html = `
            <div style="margin-bottom: 1rem;">
                <h3>Room Code: ${room.code}</h3>
                <p>Status: ${room.status}</p>
                <p>Players: ${room.players.length}/${room.max_players}</p>
            </div>
            <div class="players-list">
                ${room.players.map(p => `
                    <div class="player-item">
                        <span>${sanitizeHTML(p.username)} ${p.is_host ? '(Host)' : ''}</span>
                    </div>
                `).join('')}
            </div>
            ${room.is_host ? `
                <button id="start-game-btn" class="btn btn-primary btn-large" style="margin-top: 1rem;">
                    Start Game
                </button>
            ` : ''}
        `;

    container.innerHTML = html;

    if (room.is_host) {
      document.getElementById('start-game-btn').addEventListener('click', () => this.startGame(room.id));
    }
  },

  async startGame(roomId) {
    console.log('Starting game for room', roomId);
    const result = await API.post('api/start_game.php', { room_id: roomId });
    console.log('Start game result:', result);

    if (result.success) {
      if (this.roomPoller) {
        this.roomPoller.stop();
      }
      // Redirect immediately for host, others will follow via polling
      showToast('Starting game...', 'success');
      console.log('Redirecting host to game...');
      setTimeout(() => {
        window.location.href = `game.php?room_id=${roomId}`;
      }, 2000);
    } else {
      showToast(result.message || 'Failed to start game', 'error');
    }
  },

  async loadStats() {
    const data = await API.get('api/achievements.php?action=stats');
    const container = document.getElementById('player-stats');
    if (!container || !data.success) return;

    const stats = data.stats || {};
    container.innerHTML = `
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                <div class="stat">
                    <div class="stat-label">Games Played</div>
                    <div class="stat-value">${stats.games_played || 0}</div>
                </div>
                <div class="stat">
                    <div class="stat-label">Puzzles Solved</div>
                    <div class="stat-value">${stats.puzzles_solved || 0}</div>
                </div>
                <div class="stat">
                    <div class="stat-label">Best Time</div>
                    <div class="stat-value">${stats.best_time_ms ? formatTime(stats.best_time_ms) : '--'}</div>
                </div>
                <div class="stat">
                    <div class="stat-label">Avg Moves</div>
                    <div class="stat-value">${stats.avg_moves || '--'}</div>
                </div>
            </div>
        `;
  },

  async loadAchievements() {
    const data = await API.get('api/achievements.php?action=list');
    const container = document.getElementById('achievements-list');
    if (!container || !data.success) return;

    const achievements = data.achievements || [];
    if (achievements.length === 0) {
      container.innerHTML = '<p class="text-muted">No achievements yet. Start playing to earn some!</p>';
      return;
    }

    container.innerHTML = achievements.map(a => `
            <div class="player-item">
                <span>${a.icon} <strong>${sanitizeHTML(a.name)}</strong></span>
                <span style="color: var(--text-muted); font-size: 0.9rem;">
                    ${sanitizeHTML(a.description)}
                </span>
            </div>
        `).join('');
  }
};
