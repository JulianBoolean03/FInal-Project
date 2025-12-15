-- Reindeer Games SQLite Schema
-- SQLite version - no password needed!

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    theme TEXT DEFAULT 'classic',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS rooms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    is_private INTEGER DEFAULT 0,
    host_user_id INTEGER NOT NULL,
    status TEXT DEFAULT 'waiting',
    max_players INTEGER DEFAULT 4,
    current_round INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (host_user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS room_players (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    room_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_host INTEGER DEFAULT 0,
    is_ready INTEGER DEFAULT 0,
    last_active_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(room_id, user_id)
);

CREATE TABLE IF NOT EXISTS story_segments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    segment_number INTEGER NOT NULL UNIQUE,
    title TEXT NOT NULL,
    text TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS games (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    room_id INTEGER NOT NULL,
    round_number INTEGER NOT NULL,
    story_segment_id INTEGER,
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ended_at DATETIME,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (story_segment_id) REFERENCES story_segments(id)
);

CREATE TABLE IF NOT EXISTS moves (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    game_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    move_count INTEGER DEFAULT 0,
    time_ms INTEGER DEFAULT 0,
    finished INTEGER DEFAULT 0,
    finished_at DATETIME,
    powerups_used TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS chat_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    room_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS achievements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    achievement_key TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    description TEXT NOT NULL,
    icon TEXT DEFAULT '🎁'
);

CREATE TABLE IF NOT EXISTS user_achievements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    achievement_id INTEGER NOT NULL,
    earned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    UNIQUE(user_id, achievement_id)
);

CREATE TABLE IF NOT EXISTS analytics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL UNIQUE,
    games_played INTEGER DEFAULT 0,
    total_time_ms INTEGER DEFAULT 0,
    best_time_ms INTEGER DEFAULT 0,
    puzzles_solved INTEGER DEFAULT 0,
    total_moves INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert story segments
INSERT OR IGNORE INTO story_segments (segment_number, title, text) VALUES
(1, 'The North Pole Crisis', 'It is Christmas Eve at the North Pole, and disaster has struck. Santa''s magical workshop computer has malfunctioned, scrambling all the toy delivery routes. The reindeer are confused, and presents are in disarray. Santa needs the best puzzle solvers to help restore order before midnight.'),
(2, 'Rudolph''s Challenge', 'Rudolph steps forward with his glowing red nose illuminating a path through the blizzard. He challenges you to prove your worth by solving puzzles faster than ever. "Only the quickest minds can help us deliver joy to children worldwide," he says with determination.'),
(3, 'The Toy Workshop', 'Inside the bustling workshop, elves scramble to organize toys. Mrs. Claus appears with warm cookies and encouragement. "Each puzzle you solve repairs one section of our delivery system," she explains. The sound of jingling bells fills the air as you work.'),
(4, 'The Sleigh Preparation', 'The magical sleigh is almost ready, but the reindeer harnesses are tangled. Dasher and Dancer wait patiently as you work through another puzzle. "We believe in you," they seem to say with their gentle eyes. Time is running short.'),
(5, 'The Magic Returns', 'With a brilliant flash of light, the workshop computer springs back to life. All delivery routes are restored. Santa Ho-Ho-Ho''s with joy as the reindeer stomp their hooves in celebration. Thanks to your puzzle-solving skills, Christmas is saved. The sleigh takes off into the starry night, and you hear bells jingling as they disappear into the winter sky.');

-- Insert achievements
INSERT OR IGNORE INTO achievements (achievement_key, name, description, icon) VALUES
('first_win', 'First Victory', 'Complete your first puzzle', '⭐'),
('speed_demon', 'Speed Demon', 'Solve a puzzle in under 30 seconds', '⚡'),
('efficient_solver', 'Efficient Solver', 'Solve a puzzle in under 50 moves', '🎯'),
('hat_trick', 'Hat Trick', 'Win 3 games in a row', '🎩'),
('marathon', 'Marathon Runner', 'Play 10 games', '🏃'),
('perfectionist', 'Perfectionist', 'Solve a puzzle in under 25 moves', '💎'),
('night_owl', 'Night Owl', 'Play a game after midnight', '🦉'),
('social_butterfly', 'Social Butterfly', 'Play with 5 different people', '🦋'),
('story_complete', 'Story Complete', 'Finish all story segments', '📖'),
('power_master', 'Power Master', 'Use all three power-up types', '✨'),
('bronze_racer', 'Bronze Racer - #CD7F32', 'Win 1 race match', '🥉'),
('silver_racer', 'Silver Racer - #C0C0C0', 'Win 5 race matches', '🥈'),
('gold_racer', 'Gold Racer - #FFD700', 'Win 10 race matches', '🥇'),
('platinum_racer', 'Platinum Racer - #E5E4E2', 'Win 25 race matches', '💎'),
('legendary_racer', 'Legendary Racer - #FF0000', 'Win 50 race matches', '👑');
