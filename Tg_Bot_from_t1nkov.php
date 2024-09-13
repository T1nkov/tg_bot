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
$command = $telegram->Text();
$chat_id = $telegram->ChatID();
$data = $telegram->getData();

$GLOBALS['adminHREF']         = 'https://t.me/t1nkov';
$GLOBALS['amount']              = 500;
$GLOBALS['inviteSumValue']    = 200;
$GLOBALS['offTgChannel']      = 'https://t.me/fgjhaksdlf'; // do you need this here?
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

function isTextMatchingButtons($command) {
	foreach ($GLOBALS['buttons'] as $buttonValues) {
		if (in_array($command, $buttonValues)) return true;
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
} elseif ($callback_data !== null && preg_match('/^(fraud|spam|violence|copyright|other)$/', $callback_data, $matches)) {
    $db->handleApprovCommand($telegram, $chat_id, $message_id, $callback_data);
} elseif ($callback_data === 'yes') {
    $db->handleApprovCommand($telegram, $chat_id, $message_id, $callback_data);
} else {

}

$telegram->sendMessage([
	'chat_id' => $chat_id,
	'text'    => 'Text: ' . $command,
]);

switch ($command) {
    case $command !== null && strpos($command, '/start') === 0:
        $db->handleStartCommand($telegram, $chat_id);
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
		
	case isTextMatchingButtons($command): // Language set
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
		$db->handleStartCommand($telegram, $chat_id);
		break;
	case $db->getPhraseText("button_Help", $chat_id):
		$db->handleHelpCommand($telegram, $chat_id);
		break;
	case $db->getPhraseText('download_button', $chat_id):
		$db->handleDownloadCommand($telegram, $chat_id);
		break;
	default:
		break;
}

?>
<!-- 
case 'Админ кнопка':
		$db->getChatIdByLink($telegram, $bot_token, $chat_id);
		break;
	case $db->getPhraseText("button_earn", $chat_id):
		$db->handleEarnCommand($telegram, $chat_id);
		break;
	case 'Рассылка':
		if ($db->isAdmin($telegram, $chat_id) == 'admin') {
			$db->takeAllId($telegram, $chat_id);
			$db->adminModeRas($chat_id, $telegram);
		}
		break;
	case 'Добавить текст':
		if ($db->isAdmin($telegram, $chat_id) == 'admin') {

			$db->handleUserInput($chat_id, $telegram);
		}
		break;
	case 'Главное меню':
		$db->setInputMode($chat_id, 'def');
		$db->handleMainMenu($telegram, $chat_id);
		break; -->
