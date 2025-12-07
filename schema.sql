-- Reindeer Games Database Schema
-- Group: Julian Robinson, Amanda Nguyen

DROP TABLE IF EXISTS user_achievements;
DROP TABLE IF EXISTS achievements;
DROP TABLE IF EXISTS analytics;
DROP TABLE IF EXISTS chat_messages;
DROP TABLE IF EXISTS moves;
DROP TABLE IF EXISTS games;
DROP TABLE IF EXISTS room_players;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS story_segments;
DROP TABLE IF EXISTS users;

-- Users table: stores user account information
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    theme VARCHAR(50) DEFAULT 'classic',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rooms table: stores game room information
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    is_private TINYINT(1) DEFAULT 0,
    host_user_id INT NOT NULL,
    status ENUM('waiting', 'starting', 'in_progress', 'finished') DEFAULT 'waiting',
    max_players INT DEFAULT 4,
    current_round INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (host_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_code (code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Room players: tracks which users are in which rooms
CREATE TABLE room_players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_host TINYINT(1) DEFAULT 0,
    is_ready TINYINT(1) DEFAULT 0,
    last_active_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_room_user (room_id, user_id),
    INDEX idx_room_id (room_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Story segments: Christmas story content
CREATE TABLE story_segments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    segment_number INT NOT NULL UNIQUE,
    title VARCHAR(100) NOT NULL,
    text TEXT NOT NULL,
    INDEX idx_segment_number (segment_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Games table: tracks individual game sessions/rounds
CREATE TABLE games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    round_number INT NOT NULL,
    story_segment_id INT,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (story_segment_id) REFERENCES story_segments(id),
    INDEX idx_room_id (room_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Moves: tracks player performance in each game
CREATE TABLE moves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    user_id INT NOT NULL,
    move_count INT DEFAULT 0,
    time_ms BIGINT DEFAULT 0,
    finished TINYINT(1) DEFAULT 0,
    finished_at TIMESTAMP NULL,
    powerups_used TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_game_id (game_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Chat messages: in-room chat
CREATE TABLE chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_room_id (room_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Achievements: predefined achievements players can earn
CREATE TABLE achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    achievement_key VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    icon VARCHAR(10) DEFAULT '🎁',
    INDEX idx_key (achievement_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User achievements: tracks which users have earned which achievements
CREATE TABLE user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_achievement (user_id, achievement_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Analytics: stores aggregate player statistics
CREATE TABLE analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    games_played INT DEFAULT 0,
    total_time_ms BIGINT DEFAULT 0,
    best_time_ms BIGINT DEFAULT 0,
    puzzles_solved INT DEFAULT 0,
    total_moves INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert story segments
INSERT INTO story_segments (segment_number, title, text) VALUES
(1, 'The North Pole Crisis', 'It is Christmas Eve at the North Pole, and disaster has struck. Santa''s magical workshop computer has malfunctioned, scrambling all the toy delivery routes. The reindeer are confused, and presents are in disarray. Santa needs the best puzzle solvers to help restore order before midnight.'),
(2, 'Rudolph''s Challenge', 'Rudolph steps forward with his glowing red nose illuminating a path through the blizzard. He challenges you to prove your worth by solving puzzles faster than ever. "Only the quickest minds can help us deliver joy to children worldwide," he says with determination.'),
(3, 'The Toy Workshop', 'Inside the bustling workshop, elves scramble to organize toys. Mrs. Claus appears with warm cookies and encouragement. "Each puzzle you solve repairs one section of our delivery system," she explains. The sound of jingling bells fills the air as you work.'),
(4, 'The Sleigh Preparation', 'The magical sleigh is almost ready, but the reindeer harnesses are tangled. Dasher and Dancer wait patiently as you work through another puzzle. "We believe in you," they seem to say with their gentle eyes. Time is running short.'),
(5, 'The Magic Returns', 'With a brilliant flash of light, the workshop computer springs back to life. All delivery routes are restored. Santa Ho-Ho-Ho''s with joy as the reindeer stomp their hooves in celebration. Thanks to your puzzle-solving skills, Christmas is saved. The sleigh takes off into the starry night, and you hear bells jingling as they disappear into the winter sky.');

-- Insert predefined achievements
INSERT INTO achievements (achievement_key, name, description, icon) VALUES
('first_win', 'First Victory', 'Complete your first puzzle', '⭐'),
('speed_demon', 'Speed Demon', 'Solve a puzzle in under 30 seconds', '⚡'),
('efficient_solver', 'Efficient Solver', 'Solve a puzzle in under 50 moves', '🎯'),
('hat_trick', 'Hat Trick', 'Win 3 games in a row', '🎩'),
('marathon', 'Marathon Runner', 'Play 10 games', '🏃'),
('perfectionist', 'Perfectionist', 'Solve a puzzle in under 25 moves', '💎'),
('night_owl', 'Night Owl', 'Play a game after midnight', '🦉'),
('social_butterfly', 'Social Butterfly', 'Play with 5 different people', '🦋'),
('story_complete', 'Story Complete', 'Finish all story segments', '📖'),
('power_master', 'Power Master', 'Use all three power-up types', '✨');

-- Create a default test user (password is 'password123')
INSERT INTO users (username, password_hash) VALUES
('test_player', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
