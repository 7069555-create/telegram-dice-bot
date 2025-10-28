<?php
require_once 'config.php';

echo "<h2>🔧 设置 Webhook</h2>";

// 删除旧的 Webhook
$deleteUrl = API_URL . 'deleteWebhook';
$ch = curl_init($deleteUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$result = curl_exec($ch);
curl_close($ch);

echo "<p>删除旧 Webhook: $result</p>";

// 设置新的 Webhook
$url = API_URL . 'setWebhook';
$data = [
    'url' => WEBHOOK_URL,
    'allowed_updates' => json_encode(['message', 'callback_query'])
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$result = curl_exec($ch);
curl_close($ch);

$response = json_decode($result, true);

echo "<p>设置新 Webhook: $result</p>";

if ($response && $response['ok']) {
    echo "<h3 style='color: green;'>✅ Webhook 设置成功！</h3>";
    echo "<p><b>Webhook URL:</b> " . WEBHOOK_URL . "</p>";
    
    // 获取 Webhook 信息
    $infoUrl = API_URL . 'getWebhookInfo';
    $ch = curl_init($infoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $infoResult = curl_exec($ch);
    curl_close($ch);
    
    echo "<h3>Webhook 信息：</h3>";
    echo "<pre>" . json_encode(json_decode($infoResult, true), JSON_PRETTY_PRINT) . "</pre>";
} else {
    echo "<h3 style='color: red;'>❌ Webhook 设置失败！</h3>";
    if (isset($response['description'])) {
        echo "<p>错误: " . $response['description'] . "</p>";
    }
}
