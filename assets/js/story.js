/**
 * Story page JavaScript
 * Handles countdown and transition to game
 */

const Story = {
    roomId: null,
    countdown: 5,
    
    init: function(roomId) {
        this.roomId = roomId;
        this.startCountdown();
        this.setupEventListeners();
    },
    
    startCountdown: function() {
        const countdownEl = document.getElementById('countdown');
        
        const interval = setInterval(() => {
            this.countdown--;
            if (countdownEl) {
                countdownEl.textContent = this.countdown;
            }
            
            if (this.countdown <= 0) {
                clearInterval(interval);
                this.goToGame();
            }
        }, 1000);
    },
    
    setupEventListeners: function() {
        const continueBtn = document.getElementById('continue-btn');
        if (continueBtn) {
            continueBtn.addEventListener('click', () => this.goToGame());
        }
    },
    
    goToGame: function() {
        window.location.href = `game.php?room_id=${this.roomId}`;
    }
};
