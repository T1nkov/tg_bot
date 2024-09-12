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
$text = $telegram->Text();
$chat_id = $telegram->ChatID();
$data = $telegram->getData();

$GLOBALS['adminHREF']         = 'https://t.me/t1nkov';
$GLOBALS['summ']              = 500;
$GLOBALS['inviteSumValue']    = 200;
$GLOBALS['offTgChannel']      = 'https://t.me/fgjhaksdlf';
$GLOBALS['cards']             = 10;
$GLOBALS['ChannelID']         = 2248476665;
$GLOBALS['subscribeSumValue'] = 1000 . $GLOBALS['currency'];
$GLOBALS['watchSumValue']     = 8;
$GLOBALS['valueTg']           = 1;
$GLOBALS['currency']          = 'INR';
$GLOBALS['joinChannelPay']    = 1000 . $GLOBALS['currency'];
$GLOBALS['adminID']           = 403480319;
$GLOBALS['minWithdraw']       = number_format(10, 2, '.', '') . $GLOBALS['currency'];
$GLOBALS['bonus']             = 10 . $GLOBALS['currency'];
$GLOBALS['buttons'] = [
    "ru" => ["🇷🇺 Русский"],
    "en" => ["🇺🇸 English"],
    "kz" => ["🇰🇿 Қазақша"]
];
  
$update = json_decode(file_get_contents('php://input'), true);
$callback_data = $update['callback_query']['data'] ?? null;
$message_id = $update['callback_query']['message']['message_id'] ?? null;
$GLOBALS['username1'] = $data['message']['from']['username'] ?? null;

function isTextMatchingButtons($text) {
	foreach ($GLOBALS['buttons'] as $buttonValues) {
		if (in_array($text, $buttonValues)) {
			return true;
		}
	}
	return false;
}

$db = new DatabaseConnection($config_file);

$telegram->sendMessage([
    'chat_id' => $chat_id,
    'text'    => 'Callback data: ' . $callback_data,
]);

$commands = [
    'withdraw' => 'handleWithdrawCommand',
    'rep_ru' => 'handleReportCommand',
    'fraud' => 'handleFraudCommand',
    'spam' => 'handleSpamCommand',
    'violence' => 'handleViolenceCommand',
    'copyright' => 'handleCopyrightCommand',
    'other' => 'handleOtherCommand',
    'invite_friend' => 'handlePartnerCommand',
    'join_channel' => 'handleJoinChannelCommand',
    'skip' => 'handleJoinChannelCommand',
    'view_post' => 'count_to_ten',
    'check' => 'handleSubscribeCheckCommand',
    'checkSub' => 'handleBalanceCommand',
    'no' => 'handleCanceledCommand',
];

if (isset($commands[$callback_data])) {
    $db->{$commands[$callback_data]}($telegram, $chat_id, $message_id, $bot_token ?? null);
} elseif (preg_match('/^$(fraud|spam|violence|copyright|other)$$/', $callback_data)) {
    $db->handleApprovCommand($telegram, $chat_id, $message_id, $callback_data);
} elseif ($callback_data === 'yes') {
    $db->handleApprovCommand($telegram, $chat_id, $message_id, $callback_data);
}

$telegram->sendMessage([
    'chat_id' => $chat_id,
    'text'    => 'Text: ' . $text,
]);

$adminCommands = [
    '/start' => 'handleStartCommand',
    'Админ кнопка' => function() use ($telegram, $bot_token, $chat_id) {
        $db->getChatIdByLink($telegram, $bot_token, $chat_id);
    },
    'Рассылка' => function() use ($db, $telegram, $chat_id) {
        if ($db->isAdmin($telegram, $chat_id) === 'admin') {
            $db->takeAllId($telegram, $chat_id);
            $db->adminModeRas($chat_id, $telegram);
        }
    },
    'Добавить текст' => function() use ($db, $telegram, $chat_id) {
        if ($db->isAdmin($telegram, $chat_id) === 'admin') {
            $db->handleUserInput($chat_id, $telegram);
        }
    },
    'Главное меню' => function() use ($db, $telegram, $chat_id) {
        $db->setInputMode($chat_id, 'def');
        $db->handleMainMenu($telegram, $chat_id);
    },
    $db->getPhraseText('download_button', $chat_id) => 'handleDwnloadCommand',
];

foreach ($adminCommands as $key => $value) {
    if (strpos($text, $key) === 0) {
        if (is_string($value)) {
            $db->{$value}($telegram, $chat_id);
        } elseif (is_callable($value)) {
            $value();
        }
        break;
    }
}

if ($text !== null && $db->isInputMode($chat_id) === 'input_mode') {
    $params = [
        'chat_id' => $chat_id,
        'text'    => 'вошло',
    ];
    $telegram->sendMessage($params);
    $db->saveUserText($chat_id, $telegram, $text);
}

