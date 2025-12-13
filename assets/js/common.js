/**
 * Common JavaScript utilities for Reindeer Games
 * Provides AJAX helpers and shared functions
 */

/* Sound effects for both single & multiplayer */
const Sound = {
    play(src, volume = 0.5) {
        const audio = new Audio(src);
        audio.volume = volume;
        audio.play().catch(() => {});
    }
};

const API = {
    /**
     * Make an AJAX GET request
     */
    get: async function(url) {
        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            
            return await response.json();
        } catch (error) {
            console.error('API GET error:', error);
            return { success: false, error: error.message };
        }
    },
    
    /**
     * Make an AJAX POST request
     */
    post: async function(url, data = {}) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            });
            
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            
            return await response.json();
        } catch (error) {
            console.error('API POST error:', error);
            return { success: false, error: error.message };
        }
    }
};

/**
 * Format time in milliseconds to MM:SS format
 */
function formatTime(ms) {
    const totalSeconds = Math.floor(ms / 1000);
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

/**
 * Sanitize HTML to prevent XSS
 */
function sanitizeHTML(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

/**
 * Show a toast notification
 */
function showToast(message, type = 'info') {
    // Simple toast implementation
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: var(--card-bg);
        border: 2px solid var(--primary);
        border-radius: 8px;
        color: var(--text);
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Poll an endpoint at regular intervals
 */
class Poller {
    constructor(url, callback, interval = 2000) {
        this.url = url;
        this.callback = callback;
        this.interval = interval;
        this.timerId = null;
        this.isRunning = false;
    }
    
    start() {
        if (this.isRunning) return;
        this.isRunning = true;
        this.poll();
    }
    
    stop() {
        this.isRunning = false;
        if (this.timerId) {
            clearTimeout(this.timerId);
            this.timerId = null;
        }
    }
    
    async poll() {
        if (!this.isRunning) return;
        
        try {
            const data = await API.get(this.url);
            this.callback(data);
        } catch (error) {
            console.error('Polling error:', error);
        }
        
        if (this.isRunning) {
            this.timerId = setTimeout(() => this.poll(), this.interval);
        }
    }
}
