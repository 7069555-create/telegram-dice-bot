<?php
require_once 'config.php';
require_once 'classes/User.php';
require_once 'classes/Game.php';

class TelegramBot {
    private $user;
    private $game;
    
    public function __construct() {
        $this->user = new User();
        $this->game = new Game();
    }
    
    public function sendMessage($chatId, $text, $replyMarkup = null) {
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];
        
        if ($replyMarkup) {
            $data['reply_markup'] = json_encode($replyMarkup);
        }
        
        return $this->apiRequest('sendMessage', $data);
    }
    
    public function editMessage($chatId, $messageId, $text, $replyMarkup = null) {
        $data = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];
        
        if ($replyMarkup) {
            $data['reply_markup'] = json_encode($replyMarkup);
        }
        
        return $this->apiRequest('editMessageText', $data);
    }
    
    public function answerCallback($callbackId, $text = '', $showAlert = false) {
        $data = [
            'callback_query_id' => $callbackId,
            'text' => $text,
            'show_alert' => $showAlert
        ];
        
        return $this->apiRequest('answerCallbackQuery', $data);
    }
    
    private function apiRequest($method, $data) {
        $ch = curl_init(API_URL . $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($result, true);
    }
    
    public function getMainKeyboard() {
        return [
            'keyboard' => [
                [
                    ['text' => '🎲 开始游戏'],
                    ['text' => '💰 我的余额']
                ],
                [
                    ['text' => '📊 游戏统计'],
                    ['text' => '🏆 排行榜']
                ],
                [
                    ['text' => '📜 游戏历史'],
                    ['text' => '❓ 帮助']
                ]
            ],
            'resize_keyboard' => true
        ];
    }
    
    public function getBetTypeKeyboard() {
        return [
            'inline_keyboard' => [
                [
                    ['text' => '🔴 大 (16-25)', 'callback_data' => 'bet_type_big'],
                    ['text' => '🔵 小 (5-15)', 'callback_data' => 'bet_type_small']
                ],
                [
                    ['text' => '⚪ 单', 'callback_data' => 'bet_type_odd'],
                    ['text' => '⚫ 双', 'callback_data' => 'bet_type_even']
                ],
                [
                    ['text' => '🎰 五同号 (×500)', 'callback_data' => 'bet_type_five_same'],
                    ['text' => '🎲 四同号 (×100)', 'callback_data' => 'bet_type_four_same']
                ],
                [
                    ['text' => '🏠 葫芦 (×30)', 'callback_data' => 'bet_type_full_house'],
                    ['text' => '➡️ 顺子 (×50)', 'callback_data' => 'bet_type_straight']
                ],
                [
                    ['text' => '⚡ >20点 (×3)', 'callback_data' => 'bet_type_over_20'],
                    ['text' => '⚡ <11点 (×3)', 'callback_data' => 'bet_type_under_11']
                ],
                [
                    ['text' => '❌ 取消', 'callback_data' => 'cancel']
                ]
            ]
        ];
    }
    
    public function getBetAmountKeyboard() {
        return [
            'inline_keyboard' => [
                [
                    ['text' => '💵 10', 'callback_data' => 'bet_amount_10'],
                    ['text' => '💵 50', 'callback_data' => 'bet_amount_50'],
                    ['text' => '💵 100', 'callback_data' => 'bet_amount_100']
                ],
                [
                    ['text' => '💵 200', 'callback_data' => 'bet_amount_200'],
                    ['text' => '💵 500', 'callback_data' => 'bet_amount_500'],
                    ['text' => '💵 1000', 'callback_data' => 'bet_amount_1000']
                ],
                [
                    ['text' => '❌ 取消', 'callback_data' => 'cancel']
                ]
            ]
        ];
    }
}
