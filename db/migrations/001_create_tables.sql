CREATE TABLE games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(12) NOT NULL UNIQUE,
    status VARCHAR(20) NOT NULL,
    question_total INT NOT NULL,
    question_current INT NOT NULL DEFAULT 0,
    countdown_seconds INT NOT NULL DEFAULT 30,
    question_started_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    finished_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    score INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    joined_at DATETIME NOT NULL,
    left_at DATETIME NULL,
    CONSTRAINT fk_players_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    sequence INT NOT NULL,
    question TEXT NOT NULL,
    options JSON NOT NULL,
    correct_index INT NOT NULL,
    source_url VARCHAR(255) NULL,
    started_at DATETIME NOT NULL,
    reveal_at DATETIME NULL,
    revealed_at DATETIME NULL,
    CONSTRAINT fk_questions_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    player_id INT NOT NULL,
    selected_index INT NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    answered_at DATETIME NOT NULL,
    CONSTRAINT fk_responses_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    CONSTRAINT fk_responses_player FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_players_game ON players(game_id);
CREATE INDEX idx_questions_game ON questions(game_id, sequence);
CREATE INDEX idx_responses_question ON responses(question_id);
