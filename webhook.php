<?php
require_once 'config.php';
require_once 'bot.php';
require_once 'classes/User.php';
require_once 'classes/Game.php';

// 记录请求日志
file_put_contents('webhook.log', date('Y-m-d H:i:s') . " - Webhook called\n", FILE_APPEND);

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    http_response_code(200);
    exit;
}

// 记录更新日志
file_put_contents('webhook.log', "Update: " . json_encode($update) . "\n", FILE_APPEND);

$bot = new TelegramBot();
$userClass = new User();
$gameClass = new Game();

// 处理消息
if (isset($update['message'])) {
    $message = $update['message'];
    $chatId = $message['chat']['id'];
    $text = $message['text'] ?? '';
    $telegramId = $message['from']['id'];
    $username = $message['from']['username'] ?? '';
    $firstName = $message['from']['first_name'] ?? '';
    
    $user = $userClass->getOrCreate($telegramId, $username, $firstName);
    
    if ($text == '/start') {
        $welcomeText = "🎲 <b>欢迎来到骰子游戏！</b>\n\n";
        $welcomeText .= "这是一个基于5个骰子的博弈游戏。\n";
        $welcomeText .= "你的初始余额：<b>¥" . number_format($user['balance'], 2) . "</b>\n\n";
        $welcomeText .= "点击下方按钮开始游戏！";
        
        $bot->sendMessage($chatId, $welcomeText, $bot->getMainKeyboard());
    }
    elseif ($text == '🎲 开始游戏') {
        $balance = $userClass->getBalance($telegramId);
        
        if ($balance < MIN_BET) {
            $bot->sendMessage($chatId, "❌ 余额不足！当前余额：¥" . number_format($balance, 2));
            http_response_code(200);
            exit;
        }
        
        $gameClass->createActiveGame($telegramId, 'dice');
        
        $text = "🎲 <b>选择下注类型</b>\n\n";
        $text .= "当前余额：<b>¥" . number_format($balance, 2) . "</b>\n";
        $text .= "下注范围：¥" . MIN_BET . " - ¥" . MAX_BET;
        
        $bot->sendMessage($chatId, $text, $bot->getBetTypeKeyboard());
    }
    elseif ($text == '💰 我的余额') {
        $balance = $userClass->getBalance($telegramId);
        $bot->sendMessage($chatId, "💰 当前余额：<b>¥" . number_format($balance, 2) . "</b>");
    }
    elseif ($text == '📊 游戏统计') {
        $stats = $userClass->getStats($telegramId);
        
        $text = "📊 <b>你的游戏统计</b>\n\n";
        $text .= "总游戏数：<b>{$stats['total_games']}</b>\n";
        $text .= "获胜次数：<b>{$stats['total_wins']}</b>\n";
        $text .= "胜率：<b>{$stats['win_rate']}%</b>\n";
        $text .= "总下注：<b>¥" . number_format($stats['total_bets'], 2) . "</b>\n";
        $text .= "总盈利：<b>¥" . number_format($stats['total_winnings'], 2) . "</b>";
        
        $bot->sendMessage($chatId, $text);
    }
    elseif ($text == '🏆 排行榜') {
        $leaderboard = $userClass->getLeaderboard(10);
        
        $text = "🏆 <b>盈利排行榜 TOP 10</b>\n\n";
        
        $medals = ['🥇', '🥈', '🥉'];
        foreach ($leaderboard as $index => $player) {
            $medal = $medals[$index] ?? ($index + 1) . '.';
            $name = $player['first_name'] ?: $player['username'] ?: 'User';
            $text .= "{$medal} {$name}\n";
            $text .= "   盈利：¥" . number_format($player['total_winnings'], 2) . " | ";
            $text .= "胜率：{$player['win_rate']}%\n\n";
        }
        
        $bot->sendMessage($chatId, $text);
    }
    elseif ($text == '📜 游戏历史') {
        $history = $gameClass->getHistory($telegramId, 5);
        
        if (empty($history)) {
            $bot->sendMessage($chatId, "📜 暂无游戏记录");
            http_response_code(200);
            exit;
        }
        
        $text = "📜 <b>最近5场游戏</b>\n\n";
        
        foreach ($history as $record) {
            $result = $record['is_win'] ? '✅ 赢' : '❌ 输';
            $profit = $record['win_amount'] - $record['bet_amount'];
            $profitText = $profit >= 0 ? '+' . number_format($profit, 2) : number_format($profit, 2);
            
            $text .= "{$result} | {$record['bet_type']}\n";
            $text .= "骰子：" . implode('-', json_decode($record['dice_result'])) . "\n";
            $text .= "下注：¥{$record['bet_amount']} | 盈亏：¥{$profitText}\n";
            $text .= "时间：{$record['created_at']}\n\n";
        }
        
        $bot->sendMessage($chatId, $text);
    }
    elseif ($text == '❓ 帮助') {
        $text = "❓ <b>游戏帮助</b>\n\n";
        $text .= "<b>基础玩法：</b>\n";
        $text .= "• 大：总点数16-25 (赔率1.95)\n";
        $text .= "• 小：总点数5-15 (赔率1.95)\n";
        $text .= "• 单/双：总点数单双 (赔率1.95)\n\n";
        $text .= "<b>特殊牌型：</b>\n";
        $text .= "• 五同号：5个相同 (赔率500)\n";
        $text .= "• 四同号：4个相同 (赔率100)\n";
        $text .= "• 顺子：12345或23456 (赔率50)\n";
        $text .= "• 葫芦：3个+2个 (赔率30)\n\n";
        $text .= "<b>极限玩法：</b>\n";
        $text .= "• >20点 (赔率3)\n";
        $text .= "• <11点 (赔率3)\n\n";
        $text .= "游戏使用5个骰子，祝你好运！🍀";
        
        $bot->sendMessage($chatId, $text);
    }
}

// 处理回调查询
if (isset($update['callback_query'])) {
    $callback = $update['callback_query'];
    $callbackId = $callback['id'];
    $chatId = $callback['message']['chat']['id'];
    $messageId = $callback['message']['message_id'];
    $data = $callback['data'];
    $telegramId = $callback['from']['id'];
    
    $activeGame = $gameClass->getActiveGame($telegramId);
    
    if (!$activeGame && $data != 'cancel') {
        $bot->answerCallback($callbackId, "游戏已过期，请重新开始", true);
        http_response_code(200);
        exit;
    }
    
    if (strpos($data, 'bet_type_') === 0) {
        $betType = str_replace('bet_type_', '', $data);
        
        $gameClass->updateActiveGame($telegramId, [
            'bet_type' => $betType,
            'state' => 'waiting_amount'
        ]);
        
        $betTypeNames = [
            'big' => '大 (16-25)',
            'small' => '小 (5-15)',
            'odd' => '单',
            'even' => '双',
            'five_same' => '五同号',
            'four_same' => '四同号',
            'full_house' => '葫芦',
            'straight' => '顺子',
            'three_same' => '三同号',
            'two_pair' => '两对',
            'one_pair' => '一对',
            'over_20' => '>20点',
            'under_11' => '<11点'
        ];
        
        $text = "💵 <b>选择下注金额</b>\n\n";
        $text .= "下注类型：<b>{$betTypeNames[$betType]}</b>\n";
        $text .= "请选择下注金额：";
        
        $bot->editMessage($chatId, $messageId, $text, $bot->getBetAmountKeyboard());
        $bot->answerCallback($callbackId);
    }
    elseif (strpos($data, 'bet_amount_') === 0) {
        $betAmount = floatval(str_replace('bet_amount_', '', $data));
        
        $balance = $userClass->getBalance($telegramId);
        
        if ($balance < $betAmount) {
            $bot->answerCallback($callbackId, "余额不足！", true);
            http_response_code(200);
            exit;
        }
        
        $gameClass->updateActiveGame($telegramId, [
            'bet_amount' => $betAmount,
            'state' => 'rolling'
        ]);
        
        $activeGame = $gameClass->getActiveGame($telegramId);
        
        $text = "🎲 <b>开始摇骰子...</b>\n\n";
        $text .= "下注类型：<b>{$activeGame['bet_type']}</b>\n";
        $text .= "下注金额：<b>¥{$betAmount}</b>";
        
        $bot->editMessage($chatId, $messageId, $text);
        $bot->answerCallback($callbackId);
        
        sleep(1);
        $dice = [];
        for ($i = 0; $i < 5; $i++) {
            $dice[] = rand(1, 6);
        }
        
        $analysis = $gameClass->analyzeDice($dice);
        $checkResult = $gameClass->checkWin($activeGame['bet_type'], $analysis);
        
        $isWin = $checkResult['isWin'];
        $odds = $checkResult['odds'];
        $winAmount = $isWin ? $betAmount * $odds : 0;
        $profit = $winAmount - $betAmount;
        
        $userClass->updateBalance($telegramId, $profit);
        $userClass->updateStats($telegramId, $isWin, $betAmount, $winAmount);
        
        $user = $userClass->getOrCreate($telegramId);
        $gameClass->recordGame(
            $user['id'],
            $telegramId,
            'dice',
            $activeGame['bet_type'],
            $betAmount,
            $analysis,
            $isWin,
            $winAmount,
            $odds
        );
        
        $gameClass->clearActiveGame($telegramId);
        
        $resultText = "🎲 <b>游戏结果</b>\n\n";
        $resultText .= "骰子：" . implode(' - ', $analysis['dice']) . "\n";
        $resultText .= "总点数：<b>{$analysis['total']}</b>\n";
        $resultText .= "牌型：" . $gameClass->getPatternName($analysis['pattern']) . "\n\n";
        
        if ($isWin) {
            $resultText .= "🎉 <b>恭喜你赢了！</b>\n";
            $resultText .= "赢得：<b>¥" . number_format($profit, 2) . "</b>\n";
        } else {
            $resultText .= "😔 <b>很遗憾，你输了</b>\n";
            $resultText .= "损失：<b>¥" . number_format(abs($profit), 2) . "</b>\n";
        }
        
        $newBalance = $userClass->getBalance($telegramId);
        $resultText .= "当前余额：<b>¥" . number_format($newBalance, 2) . "</b>";
        
        $bot->editMessage($chatId, $messageId, $resultText);
    }
    elseif ($data == 'cancel') {
        $gameClass->clearActiveGame($telegramId);
        $bot->editMessage($chatId, $messageId, "❌ 已取消游戏");
        $bot->answerCallback($callbackId);
    }
}

http_response_code(200);
