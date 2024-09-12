<?php

include 'class/Telegram.php';
include 'class/DatabaseConnection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$config_file = require __DIR__ . '/config.php';
if (!isset($config_file['db'])) {
    die("# db key error - Database configuration not found.");
}

$bot_token = $config_file['bot_token'];
$telegram = new Telegram($bot_token);
$GLOBALS['TOKEN'] = $bot_token;

$text = $telegram->Text();
$chat_id = $telegram->ChatID();
$data = $telegram->getData();

$GLOBALS['currency'] = 'INR';
$settings = [
    'adminHREF'         => 'https://t.me/t1nkov',
    'summ'              => 500,
    'inviteSumValue'    => 200,
    'offTgChannel'      => 'https://t.me/fgjhaksdlf',
    'cards'             => 10,
    'ChannelID'         => 2248476665,
    'subscribeSumValue' => 1000 . $GLOBALS['currency'],
    'watchSumValue'     => 8,
    'valueTg'           => 1,
    'joinChannelPay'    => 1000 . $GLOBALS['currency'],
    'adminID'           => 403480319,
    'minWithdraw'       => number_format(10, 2, '.', '') . $GLOBALS['currency'],
    'bonus'             => 10 . $GLOBALS['currency'],
];

$GLOBALS = array_merge($GLOBALS, $settings);

$GLOBALS['buttons'] = [
    "ru" => ["🇷🇺 Русский"],
    "en" => ["🇺🇸 English"],
    "kz" => ["🇰🇿 Қазақша"]
];
  
$update = json_decode(file_get_contents('php://input'), true);
$callback_data = $update['callback_query']['data'] ?? null;
$message_id = $update['callback_query']['message']['message_id'] ?? null;
$GLOBALS['username1'] = $data['message']['from']['username'] ?? null;

$db = new DatabaseConnection($config_file);

$telegram->sendMessage([
    'chat_id' => $chat_id,
    'text'    => 'Callback data: ' . $callback_data,
]);

function handleCallback($callback_data, $telegram, $chat_id, $message_id, $db) {
    $actionMap = [
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
        'yes'                => 'handleApprovCommand',
        'invite_friend'      => 'handlePartnerCommand',
        'join_channel'       => 'handleJoinChannelCommand',
        'skip'               => 'handleJoinChannelCommand',
        'view_post'          => 'count_to_ten',
        'check'              => 'handleSubscribeCheckCommand',
        'checkSub'           => 'handleBalanceCommand',
        'no'                 => 'handleCanceledCommand'
    ];
    
    $action = $actionMap[$callback_data] ?? null;
    if ($action) {
        if (in_array($callback_data, ['(spam)', '(violence)', '(copyright)', '(other)'])) {
            $db->$action($telegram, $chat_id, $message_id, $callback_data);
        } elseif (in_array($callback_data, ['view_post'])) {
            $db->$action($telegram, $chat_id, $message_id);
        } else {
            $db->$action($telegram, $chat_id);
        }
    }
}

handleCallback($callback_data, $telegram, $chat_id, $message_id, $db);

$telegram->sendMessage([
    'chat_id' => $chat_id,
    'text'    => 'Text: ' . $text,
]);

function handleTextCommand($text, $telegram, $chat_id, $update, $db) {
    if (strpos($text, '/start') === 0) {
        $db->handleStartCommand($telegram, $chat_id, $update);
    } elseif (isTextMatchingButtons($text)) {
        foreach ($GLOBALS['buttons'] as $key => $values) {
            if (in_array($text, $values)) {
                $db->updateUserLanguage($chat_id, $key);
                break;
            }
        }
        $db->handleLanguage($telegram, $chat_id);
    } else {
        $phrases = [
            "welcome_button"   => 'handleMainMenu',
            "button_balance"   => 'handleBalanceCommand',
            "button_partners"  => 'handlePartnerCommand',
            "button_changeLang" => 'handleStartCommand',
            "button_Help"      => 'handleHelpCommand',
            "button_earn"      => 'handleEarnCommand',
            'download_button'   => 'handleDwnloadCommand'
        ];
        
        foreach ($phrases as $phraseKey => $handleMethod) {
            if ($db->getPhraseText($phraseKey, $chat_id) === $text) {
                $db->$handleMethod($telegram, $chat_id, ...($phraseKey === 'download_button' ? [] : [$bot_token]));
                return;
            }
        }
        
        // Admin commands
        if ($text === 'Админ кнопка' && $db->isAdmin($telegram, $chat_id) === 'admin') {
            $db->getChatIdByLink($telegram, $bot_token, $chat_id);
        } elseif ($text === 'Рассылка' && $db->isAdmin($telegram, $chat_id) === 'admin') {
            $db->takeAllId($telegram, $chat_id);
            $db->adminModeRas($chat_id, $telegram);
        } elseif ($text === 'Добавить текст' && $db->isAdmin($telegram, $chat_id) === 'admin') {
            $db->handleUserInput($chat_id, $telegram);
        } elseif ($text === 'Главное меню') {
            $db->setInputMode($chat_id, 'def');
            $db->handleMainMenu($telegram, $chat_id);
        } elseif ($text !== null && $db->isInputMode($chat_id) === 'input_mode') {
            $telegram->sendMessage(['chat_id' => $chat_id, 'text' => 'вошло']);
            $db->saveUserText($chat_id, $telegram, $text);
        }
    }
}

handleTextCommand($text, $telegram, $chat_id, $update, $db);

?>
