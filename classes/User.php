<?php
require_once __DIR__ . '/Database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getOrCreate($telegramId, $username = null, $firstName = null) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE telegram_id = ?");
        $stmt->execute([$telegramId]);
        $user = $stmt->fetch();
        
        if ($user) {
            $stmt = $this->db->prepare(
                "UPDATE users SET username = ?, first_name = ?, last_active = NOW() WHERE telegram_id = ?"
            );
            $stmt->execute([$username, $firstName, $telegramId]);
            return $user;
        }
        
        $stmt = $this->db->prepare(
            "INSERT INTO users (telegram_id, username, first_name, balance) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$telegramId, $username, $firstName, INITIAL_BALANCE]);
        
        return $this->getOrCreate($telegramId, $username, $firstName);
    }
    
    public function getBalance($telegramId) {
        $stmt = $this->db->prepare("SELECT balance FROM users WHERE telegram_id = ?");
        $stmt->execute([$telegramId]);
        $result = $stmt->fetch();
        return $result ? $result['balance'] : 0;
    }
    
    public function updateBalance($telegramId, $amount) {
        $stmt = $this->db->prepare(
            "UPDATE users SET balance = balance + ? WHERE telegram_id = ?"
        );
        return $stmt->execute([$amount, $telegramId]);
    }
    
    public function updateStats($telegramId, $isWin, $betAmount, $winAmount) {
        $stmt = $this->db->prepare(
            "UPDATE users SET 
                total_games = total_games + 1,
                total_wins = total_wins + ?,
                total_bets = total_bets + ?,
                total_winnings = total_winnings + ?
            WHERE telegram_id = ?"
        );
        return $stmt->execute([
            $isWin ? 1 : 0,
            $betAmount,
            $winAmount - $betAmount,
            $telegramId
        ]);
    }
    
    public function getStats($telegramId) {
        $stmt = $this->db->prepare(
            "SELECT total_games, total_wins, total_bets, total_winnings,
                    ROUND(total_wins * 100.0 / NULLIF(total_games, 0), 2) as win_rate
             FROM users WHERE telegram_id = ?"
        );
        $stmt->execute([$telegramId]);
        return $stmt->fetch();
    }
    
    public function getLeaderboard($limit = 10) {
        $stmt = $this->db->prepare(
            "SELECT telegram_id, username, first_name, balance, total_games, total_wins, total_winnings,
                    ROUND(total_wins * 100.0 / NULLIF(total_games, 0), 2) as win_rate
             FROM users 
             WHERE total_games > 0
             ORDER BY total_winnings DESC 
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
