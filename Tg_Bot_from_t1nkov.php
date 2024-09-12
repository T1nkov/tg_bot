<?php

include 'class/Telegram.php';
include 'class/DatabaseConnection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$config_file = require __DIR__ . '/config.php';

if (!isset($config_file['db'])) { die("# db key error - Database configuration not found."); }

$bot_token = $config_file['bot_token'];
$telegram = new Telegram($bot_token);
$GLOBALS['TOKEN'] = $bot_token;

$GLOBALS = array_merge($GLOBALS, [
    'adminHREF' => 'https://t.me/t1nkov',
    'summ' => 500,
    'inviteSumValue' => 200,
    'offTgChannel' => 'https://t.me/fgjhaksdlf',
    'cards' => 10,
    'ChannelID' => 2248476665,
    'currency' => 'INR',
    'joinChannelPay' => '1000INR',
    'adminID' => 403480319,
    'minWithdraw' => '10.00INR',
    'bonus' => '10INR',
    'buttons' => [
        "ru" => ["🇷🇺 Русский"],
        "en" => ["🇺🇸 English"],
        "kz" => ["🇰🇿 Қазақша"]
    ]
]);

$update = json_decode(file_get_contents('php://input'), true);
$callback_data = $update['callback_query']['data'] ?? null;
$message_id = $update['callback_query']['message']['message_id'] ?? null;
$chat_id = $telegram->ChatID();
$text = $telegram->Text() ?? '';

$db = new DatabaseConnection($config_file);

$telegram->sendMessage(['chat_id' => $chat_id, 'text' => 'Callback data: ' . ($callback_data ?? 'Нет данных')]);

$commands = [
    'withdraw'           => 'handleWithdrawCommand',
    'rep_ru'             => 'handleReportCommand',
    'fraud'              => 'handleFraudCommand',
    '(fraud)'            => 'handleApprovCommand',
    'spam'               => 'handleSpamCommand',
    '(spam)'             => 'handleApprovCommand',
    'violence'           => 'handleViolenceCommand',
    '(violence)'         => 'handleApprovCommand',
    'copyright'          => 'handleCopyrightCommand',
    '(copyright)'        => 'handleApprovCommand',
    'other'              => 'handleOtherCommand',
    '(other)'            => 'handleApprovCommand',
    'invite_friend'      => 'handlePartnerCommand',
    'join_channel'       => 'handleJoinChannelCommand',
    'skip'               => 'handleJoinChannelCommand',
    'view_post'          => 'count_to_ten',
    'check'              => 'handleSubscribeCheckCommand',
    'checkSub'           => 'handleBalanceCommand',
    'yes'                => 'handleApprovCommand',
    'no'                 => 'handleCanceledCommand'
];

if (isset($commands[$callback_data])) {
    call_user_func([$db, $commands[$callback_data]], $telegram, $chat_id, $message_id, $callback_data);
}

$telegram->sendMessage(['chat_id' => $chat_id, 'text' => 'Text: ' . $text]);

if (strpos($text, '/start') === 0) {
    $db->handleStartCommand($telegram, $chat_id, $update);
} elseif ($commandKey = array_search($text, array_merge(...array_values($GLOBALS['buttons'])))) {
    $db->updateUserLanguage($chat_id, $commandKey);
    $db->handleLanguage($telegram, $chat_id);
} elseif ($phraseText = $db->getPhraseText($text, $chat_id)) {
    call_user_func([$db, "handle" . ucfirst($phraseText)], $telegram, $chat_id);
} elseif ($text && $db->isInputMode($chat_id) == 'input_mode') {
    $telegram->sendMessage(['chat_id' => $chat_id, 'text' => 'вошло']);
    $db->saveUserText($chat_id, $telegram, $text);
}

?>
