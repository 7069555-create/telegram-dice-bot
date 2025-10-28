<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../config.php';

class Game {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function createActiveGame($telegramId, $gameType) {
        $this->clearActiveGame($telegramId);
        $stmt = $this->db->prepare(
            "INSERT INTO active_games (telegram_id, game_type, state) VALUES (?, ?, 'waiting_bet')"
        );
        return $stmt->execute([$telegramId, $gameType]);
    }
    
    public function getActiveGame($telegramId) {
        $stmt = $this->db->prepare("SELECT * FROM active_games WHERE telegram_id = ?");
        $stmt->execute([$telegramId]);
        return $stmt->fetch();
    }
    
    public function updateActiveGame($telegramId, $data) {
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        
        $values[] = $telegramId;
        
        $sql = "UPDATE active_games SET " . implode(', ', $fields) . " WHERE telegram_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    public function clearActiveGame($telegramId) {
        $stmt = $this->db->prepare("DELETE FROM active_games WHERE telegram_id = ?");
        return $stmt->execute([$telegramId]);
    }
    
    public function analyzeDice($diceArray) {
        sort($diceArray);
        $total = array_sum($diceArray);
        $counts = array_count_values($diceArray);
        arsort($counts);
        $countValues = array_values($counts);
        
        $pattern = 'none';
        
        if (max($counts) == 5) {
            $pattern = 'five_same';
        } elseif (max($counts) == 4) {
            $pattern = 'four_same';
        } elseif (count($countValues) == 2 && $countValues[0] == 3 && $countValues[1] == 2) {
            $pattern = 'full_house';
        } elseif ($this->isStraight($diceArray)) {
            $pattern = 'straight';
        } elseif (max($counts) == 3) {
            $pattern = 'three_same';
        } elseif (count(array_filter($counts, function($c) { return $c >= 2; })) >= 2) {
            $pattern = 'two_pair';
        } elseif (max($counts) >= 2) {
            $pattern = 'one_pair';
        }
        
        return [
            'dice' => $diceArray,
            'total' => $total,
            'pattern' => $pattern,
            'is_big' => $total >= 16 && $total <= 25 && $pattern != 'five_same',
            'is_small' => $total >= 5 && $total <= 15 && $pattern != 'five_same',
            'is_odd' => $total % 2 == 1,
            'is_even' => $total % 2 == 0
        ];
    }
    
    private function isStraight($diceArray) {
        $unique = array_unique($diceArray);
        if (count($unique) != 5) return false;
        sort($unique);
        return ($unique == [1,2,3,4,5] || $unique == [2,3,4,5,6]);
    }
    
    public function checkWin($betType, $analysis) {
        global $GAME_ODDS;
        
        $isWin = false;
        $odds = 0;
        
        switch($betType) {
            case 'big':
                $isWin = $analysis['is_big'];
                $odds = $GAME_ODDS['dice']['big'];
                break;
            case 'small':
                $isWin = $analysis['is_small'];
                $odds = $GAME_ODDS['dice']['small'];
                break;
            case 'odd':
                $isWin = $analysis['is_odd'] && $analysis['pattern'] != 'five_same';
                $odds = $GAME_ODDS['dice']['odd'];
                break;
            case 'even':
                $isWin = $analysis['is_even'] && $analysis['pattern'] != 'five_same';
                $odds = $GAME_ODDS['dice']['even'];
                break;
            case 'five_same':
                $isWin = $analysis['pattern'] == 'five_same';
                $odds = $GAME_ODDS['dice']['five_same'];
                break;
            case 'four_same':
                $isWin = $analysis['pattern'] == 'four_same';
                $odds = $GAME_ODDS['dice']['four_same'];
                break;
            case 'full_house':
                $isWin = $analysis['pattern'] == 'full_house';
                $odds = $GAME_ODDS['dice']['full_house'];
                break;
            case 'straight':
                $isWin = $analysis['pattern'] == 'straight';
                $odds = $GAME_ODDS['dice']['straight'];
                break;
            case 'three_same':
                $isWin = $analysis['pattern'] == 'three_same';
                $odds = $GAME_ODDS['dice']['three_same'];
                break;
            case 'two_pair':
                $isWin = $analysis['pattern'] == 'two_pair';
                $odds = $GAME_ODDS['dice']['two_pair'];
                break;
            case 'one_pair':
                $isWin = $analysis['pattern'] == 'one_pair';
                $odds = $GAME_ODDS['dice']['one_pair'];
                break;
            case 'over_20':
                $isWin = $analysis['total'] > 20;
                $odds = $GAME_ODDS['dice']['over_20'];
                break;
            case 'under_11':
                $isWin = $analysis['total'] < 11;
                $odds = $GAME_ODDS['dice']['under_11'];
                break;
        }
        
        return ['isWin' => $isWin, 'odds' => $odds];
    }
    
    public function recordGame($userId, $telegramId, $gameType, $betType, $betAmount, $analysis, $isWin, $winAmount, $odds) {
        $stmt = $this->db->prepare(
            "INSERT INTO game_records 
            (user_id, telegram_id, game_type, bet_type, bet_amount, dice_result, total_points, is_win, win_amount, odds)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        return $stmt->execute([
            $userId,
            $telegramId,
            $gameType,
            $betType,
            $betAmount,
            json_encode($analysis['dice']),
            $analysis['total'],
            $isWin ? 1 : 0,
            $winAmount,
            $odds
        ]);
    }
    
    public function getHistory($telegramId, $limit = 5) {
        $stmt = $this->db->prepare(
            "SELECT * FROM game_records WHERE telegram_id = ? ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->execute([$telegramId, $limit]);
        return $stmt->fetchAll();
    }
    
    public function getPatternName($pattern) {
        $names = [
            'five_same' => '🎰 五同号',
            'four_same' => '🎲 四同号',
            'full_house' => '🏠 葫芦',
            'straight' => '➡️ 顺子',
            'three_same' => '🎲 三同号',
            'two_pair' => '👥 两对',
            'one_pair' => '👫 一对',
            'none' => '散牌'
        ];
        return $names[$pattern] ?? '散牌';
    }
}
