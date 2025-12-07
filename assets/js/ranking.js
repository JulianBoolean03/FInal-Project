/**
 * Ranking page JavaScript
 * Handles results display and next round transition
 */

const Ranking = {
    roomId: null,
    userId: null,
    
    init: function(roomId, userId) {
        this.roomId = roomId;
        this.userId = userId;
        
        this.setupEventListeners();
        this.loadNewAchievements();
    },
    
    setupEventListeners: function() {
        const continueBtn = document.getElementById('continue-btn');
        if (continueBtn) {
            continueBtn.addEventListener('click', () => {
                window.location.href = `story.php?room_id=${this.roomId}`;
            });
        }
        
        const exitBtn = document.getElementById('exit-btn');
        if (exitBtn) {
            exitBtn.addEventListener('click', () => {
                this.leaveRoom();
            });
        }
    },
    
    async loadNewAchievements() {
        const data = await API.get('api/achievements.php?action=new');
        if (data.success && data.achievements && data.achievements.length > 0) {
            this.displayNewAchievements(data.achievements);
        }
    },
    
    displayNewAchievements: function(achievements) {
        const container = document.getElementById('new-achievements');
        const list = document.getElementById('achievements-list');
        
        if (container && list) {
            container.style.display = 'block';
            list.innerHTML = achievements.map(a => `
                <div class="player-item" style="animation: slideIn 0.5s ease;">
                    <span>${a.icon} <strong>${sanitizeHTML(a.name)}</strong></span>
                    <span style="color: var(--text-muted);">${sanitizeHTML(a.description)}</span>
                </div>
            `).join('');
        }
    },
    
    async leaveRoom() {
        await API.post('api/join_room.php', { action: 'leave' });
        window.location.href = 'lobby.php';
    }
};
