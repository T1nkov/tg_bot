<?php

include 'class/Telegram.php';
include 'class/DatabaseConnection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$config_file = require __DIR__ . '/config/config.php';

$bot_token = $config_file['bot_token'];
$telegram = new Telegram($bot_token);
$GLOBALS['TOKEN'] = $bot_token;
$command = $telegram->Text();
$chat_id = $telegram->ChatID();
$data = $telegram->getData();

$GLOBALS['bot_name']          = 'testest0001_bot';
$GLOBALS['adminHREF']         = 'https://t.me/t1nkov';
$GLOBALS['currency']          = 'INR';
$GLOBALS['amount']            = 500;
$GLOBALS['inviteSumValue']    = 200 . $GLOBALS['currency'];
$GLOBALS['cards']             = 10;
$GLOBALS['ChannelID']         = 2248476665;
$GLOBALS['subscribeSumValue'] = 1000 . $GLOBALS['currency'];
$GLOBALS['watchSumValue']     = 8 . $GLOBALS['currency'];
$GLOBALS['valueTg']           = 1;
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

function isTextMatchingButtons($command) {
    return in_array($command, array_merge(...array_values($GLOBALS['buttons'])));
}

$db = new DatabaseConnection($config_file);

$telegram->sendMessage([
    'chat_id' => $chat_id,
    'text'    => 'Callback data: ' . $callback_data,
]);

$commands = [
    'withdraw'       => 'handleWithdrawCommand',
    'rep_ru'         => 'handleReportCommand',
    'fraud'          => 'handleFraudCommand',
    'spam'           => 'handleSpamCommand',
    'violence'       => 'handleViolenceCommand',
    'copyright'      => 'handleCopyrightCommand',
    'other'          => 'handleOtherCommand',
    'invite_friend'  => 'handlePartnerCommand',
    'join_channel'   => 'handleJoinChannelCommand',
    'skip'           => 'handleJoinChannelCommand',
    'view_post'      => 'handleViewPost',
    'check'          => 'handleSubscribeCommand',
    'checkSub'       => 'handleBalanceCommand',
    'no'             => 'handleCanceledCommand',
	'add_channel'    => 'promptAddChannel',
    'remove_channel' => 'promptRemoveChannel',
    'cancel_remove'  => 'displayChannels',
    'next'           => 'handleJoinChannelCommand',
    'init_cast'      => 'initiateBroadcast',
    'create_post'    => 'handleNewPost'
];

if (isset($commands[$callback_data])) {
    $db->{$commands[$callback_data]}($telegram, $chat_id, $message_id, $bot_token ?? null);
    return;
} elseif (isset($callback_data) && preg_match('/^remove_/', $callback_data)) {
    $urlToRemove = str_replace('remove_', '', $callback_data);
    $db->removeChannelURL($telegram, $chat_id, $urlToRemove);
    $db->displayChannels($telegram, $chat_id);
}

$telegram->sendMessage([
	'chat_id' => $chat_id,
	'text'    => 'Text: ' . json_encode($data, JSON_PRETTY_PRINT)
]);

if ($db->isInputMode($chat_id) === 'input_mode') {
    if (!empty($command)) {
        $db->addChannelURL($telegram, $chat_id, $command);
        $db->setInputMode($chat_id, 'def');
        return;
    }
}

if ($db->isInputMode($chat_id) === 'post_edit') {
    if (!empty($command)) {
        // 
        $db->setInputMode($chat_id, 'def');
        return;
    }
}

if ($db->isInputMode($chat_id) === 'def') {
    switch ($command) {
        case $command !== null && strpos($command, '/start') === 0:
            $db->handleStartCommand($telegram, $chat_id, $update);
            break;
        case $command !== null && strpos($command, '/admin') === 0:
            if ($db->isAdmin($telegram, $chat_id)) {
                $db->handleAdminPanel($telegram, $chat_id);
            } else {
                $message = 'Sorry, you\'re not an admin, contact with the owner if you really are.';
                $db->handleMainMenu($telegram, $chat_id, $message);
            }
            break;
        case $command !== null && (strpos($command, '/exit') === 0 || stripos($command, 'Выход') === 0):
            $db->handleMainMenu($telegram, $chat_id);
            break;
        case $command !== null && strpos($command, 'Каналы') === 0:
            if ($db->isAdmin($telegram, $chat_id)) { $db->displayChannels($telegram, $chat_id); }
            break;
        case $command !== null && strpos($command, 'Рассылка') === 0:
            if ($db->isAdmin($telegram, $chat_id)) { $db->displayPosts($telegram, $chat_id); }
            break;
        case isTextMatchingButtons($command):
            $hhh = null;
            foreach ($GLOBALS['buttons'] as $key => $values) {
                if (in_array($command, $values)) {
                    $hhh = $key;
                    break;
                }
            }
            if ($hhh !== null) { $db->updateUserLanguage($chat_id, $hhh); }
            $db->handleLanguage($telegram, $chat_id);
            break;
        case $db->getPhraseText("button_earn", $chat_id):
            $db->handleEarnCommand($telegram, $chat_id);
            break;
        case $db->getPhraseText("welcome_button", $chat_id):
            $db->handleMainMenu($telegram, $chat_id);
            break;
        case $db->getPhraseText("button_balance", $chat_id):
            $db->handleBalanceCommand($telegram, $chat_id, $bot_token);
            break;
        case $db->getPhraseText("button_partners", $chat_id):
            $db->handlePartnerCommand($telegram, $chat_id);
            break;
        case $db->getPhraseText("button_changeLang", $chat_id):
            $db->handleStartCommand($telegram, $chat_id, $update);
            break;
        case $db->getPhraseText("button_Help", $chat_id):
            $db->handleHelpCommand($telegram, $chat_id);
            break;
        case $db->getPhraseText('download_button', $chat_id):
            $db->handleDownloadCommand($telegram, $chat_id);
            break;
        default:
            return;
            break;
    }
}

?>
