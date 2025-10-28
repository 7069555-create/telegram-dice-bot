CREATE DATABASE IF NOT EXISTS telegram_dice_bot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE telegram_dice_bot;

CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    telegram_id BIGINT UNIQUE NOT NULL,
    username VARCHAR(100),
    first_name VARCHAR(100),
    balance DECIMAL(10,2) DEFAULT 1000.00,
    total_games INT DEFAULT 0,
    total_wins INT DEFAULT 0,
    total_bets DECIMAL(15,2) DEFAULT 0,
    total_winnings DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_telegram_id (telegram_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS game_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    telegram_id BIGINT NOT NULL,
    game_type VARCHAR(20) NOT NULL,
    bet_type VARCHAR(50) NOT NULL,
    bet_amount DECIMAL(10,2) NOT NULL,
    dice_result TEXT,
    total_points INT,
    is_win BOOLEAN DEFAULT FALSE,
    win_amount DECIMAL(10,2) DEFAULT 0,
    odds DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_date (user_id, created_at),
    INDEX idx_telegram_id (telegram_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS active_games (
    id INT PRIMARY KEY AUTO_INCREMENT,
    telegram_id BIGINT UNIQUE NOT NULL,
    game_type VARCHAR(20) NOT NULL,
    bet_type VARCHAR(50),
    bet_amount DECIMAL(10,2),
    state VARCHAR(20) DEFAULT 'waiting_bet',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_telegram_id (telegram_id)
) ENGINE=InnoDB;
