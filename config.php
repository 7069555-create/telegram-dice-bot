config.php
<?php
// Telegram Bot 配置
define('BOT_TOKEN', getenv('BOT_TOKEN') ?: 'YOUR_TOKEN_HERE');
define('BOT_USERNAME', getenv('BOT_USERNAME') ?: 'your_bot_username');
define('WEBHOOK_URL', getenv('WEBHOOK_URL') ?: 'https://your-app.railway.app/webhook.php');

// 数据库配置
$db_url = getenv('DATABASE_URL');
if ($db_url) {
    $db = parse_url($db_url);
    define('DB_HOST', $db['host']);
    define('DB_USER', $db['user']);
    define('DB_PASS', $db['pass']);
    define('DB_NAME', ltrim($db['path'], '/'));
} else {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'telegram_dice_bot');
}

// 游戏配置
define('MIN_BET', 10);
define('MAX_BET', 1000);
define('INITIAL_BALANCE', 1000);

// 赔率配置
$GAME_ODDS = [
    'dice' => [
        'big' => 1.95,
        'small' => 1.95,
        'odd' => 1.95,
        'even' => 1.95,
        'five_same' => 500.0,
        'four_same' => 100.0,
        'full_house' => 30.0,
        'straight' => 50.0,
        'three_same' => 20.0,
        'two_pair' => 10.0,
        'one_pair' => 5.0,
        'over_20' => 3.0,
        'under_11' => 3.0,
    ]
];

define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');
date_default_timezone_set('Asia/Shanghai');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');
