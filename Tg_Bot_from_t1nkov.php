<?php

include 'class/Telegram.php';
include 'class/DatabaseConnection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$config_file = require __DIR__ . '/config.php';

if (!isset($config_file['db'])) { die("# db key error - Database configuration not found."); }
var_dump($config_file); // debugging junk 

$bot_token = $config_file['bot_token'];
$telegram = new Telegram($bot_token);
$GLOBALS['TOKEN'] = $bot_token;
$text = $telegram->Text();
$chat_id = $telegram->ChatID();
$data = $telegram->getData();

$GLOBALS['config'] = [
    'adminHREF' => 'https://t.me/t1nkov',
    'summ' => 500,
    'inviteSumValue' => 200,
    'offTgChannel' => 'https://t.me/fgjhaksdlf',
    'cards' => 10,
    'ChannelID' => 2248476665,
    'subscribeSumValue' => '1000 INR',
    'watchSumValue' => 8,
    'valueTg' => 1,
    'currency' => 'INR',
    'joinChannelPay' => '1000 INR',
    'minWithdraw' => number_format(10, 2, '.', '') . ' INR',
    'bonus' => '10 INR',
    'buttons' => [
        "ru" => ["Русский"],
        "en" => ["English"],
        "kz" => ["Қазақша"]
    ]
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

switch ($callback_data) {
	case 'withdraw':
		$db->handleWithdrawCommand($telegram, $chat_id, $message_id);
		break;
	case 'rep_ru':
		$db->handleReportCommand($telegram, $chat_id, $message_id);
		break;
	case'fraud':
		$db->handleFraudCommand($telegram, $chat_id, $message_id);
		break;
	case'(fraud)':
		$db->handleApprovCommand($telegram, $chat_id, $message_id, $callback_data);
		break;
	case 'spam':
		$db->handleSpamCommand($telegram, $chat_id, $message_id);
		break;
	case '(spam)':
		$db->handleApprovCommand($telegram, $chat_id, $message_id, $callback_data);
		break;
	case'violence':
		$db->handleViolenceCommand($telegram, $chat_id, $message_id);
		break;
	case'(violence)':
		$db->handleApprovCommand($telegram, $chat_id, $message_id, $callback_data);
		break;
	case'copyright':
		$db->handleCopyrightCommand($telegram, $chat_id, $message_id);
		break;
	case'(copyright)':
		$db->handleApprovCommand($telegram, $chat_id, $message_id, $callback_data);
		break;
	case'other':
		$db->handleOtherCommand($telegram, $chat_id, $message_id);
		break;
	case'(other)':
		$db->handleApprovCommand($telegram, $chat_id, $message_id, $callback_data);
		break;
	case 'yes':
		$db->handleApprovCommand($telegram, $chat_id, $message_id, $callback_data);
		break;
	case 'invite_friend':
		$db->handlePartnerCommand($telegram, $chat_id);
		break;
	case 'join_channel':
		$db->handleJoinChannelCommand($telegram, $chat_id, $message_id);
		break;
	case 'skip':
		$db->handleJoinChannelCommand($telegram, $chat_id, $message_id);
		break;
	case 'view_post':
		$db->count_to_ten($telegram, $chat_id, $message_id);
		break;
	case 'check':
		$db->handleSubscribeCheckCommand($chat_id, $telegram, $bot_token, $message_id);
		break;
	case 'checkSub':
		$db->handleBalanceCommand($telegram, $chat_id, $bot_token);
		break;
	case 'no':
		$db->handleCanceledCommand($telegram, $chat_id, $message_id);
		break;
}

$telegram->sendMessage([
	'chat_id' => $chat_id,
	'text'    => 'Text: ' . $text,
]);

switch ($text) {
	case strpos($text, '/start') === 0:
		$db->handleStartCommand($telegram, $chat_id, $update);
		break;
	case isTextMatchingButtons($text):
		$hhh = null;
		foreach ($GLOBALS['buttons'] as $key => $values) {
			if (in_array($text, $values)) {
				$hhh = $key;
				break;
			}
		}
		if ($hhh !== null) {
			$db->updateUserLanguage($chat_id, $hhh);
		}
		$db->handleLanguage($telegram, $chat_id);
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
		break;
	case ($text != null):
		if ($db->isInputMode($chat_id) == 'input_mode') {
			$params = [
				'chat_id' => $chat_id,
				'text'    => 'вошло'
			];
			$telegram->sendMessage($params);
			$db->saveUserText($chat_id, $telegram, $text);
		}
		break;
	case $db->getPhraseText('download_button', $chat_id):
		$db->handleDwnloadCommand($telegram, $chat_id);
		break;
}
?>