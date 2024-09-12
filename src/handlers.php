<?php

function handleCallbackData($callback_data, $telegram, $chat_id, $message_id, $db) {
    switch ($callback_data) {
        case 'withdraw':
            $db->handleWithdrawCommand($telegram, $chat_id, $message_id);
            break;
        case 'rep_ru':
            $db->handleReportCommand($telegram, $chat_id, $message_id);
            break;
        case 'fraud':
            $db->handleFraudCommand($telegram, $chat_id, $message_id);
            break;
        case '(fraud)':
            $db->handleApprovCommand($telegram, $chat_id, $message_id, $callback_data);
            break;
        case 'spam':
            $db->handleSpamCommand($telegram, $chat_id, $message_id);
            break;
        case '(spam)':
            $db->handleApprovCommand($telegram, $chat_id, $message_id, $callback_data);
            break;
        case 'violence':
            $db->handleViolenceCommand($telegram, $chat_id, $message_id);
            break;
        case '(violence)':
            $db->handleApprovCommand($telegram, $chat_id, $message_id, $callback_data);
            break;
        case 'copyright':
            $db->handleCopyrightCommand($telegram, $chat_id, $message_id);
            break;
        case '(copyright)':
            $db->handleApprovCommand($telegram, $chat_id, $message_id, $callback_data);
            break;
        case 'other':
            $db->handleOtherCommand($telegram, $chat_id, $message_id);
            break;
        case '(other)':
            $db->handleApprovCommand($telegram, $chat_id, $message_id, $callback_data);
            break;
        case 'yes':
            $db->handleApprovCommand($telegram, $chat_id, $message_id, $callback_data);
            break;
        case 'invite_friend':
            $db->handlePartnerCommand($telegram, $chat_id);
            break;
        case 'join_channel':
        case 'skip':
            $db->handleJoinChannelCommand($telegram, $chat_id, $message_id);
            break;
        case 'check':
            $db->handleSubscribeCheckCommand($chat_id, $telegram, $GLOBALS['TOKEN'], $message_id);
            break;
        case 'checkSub':
            $db->handleBalanceCommand($telegram, $chat_id, $GLOBALS['TOKEN']);
            break;
        case 'no':
            $db->handleCanceledCommand($telegram, $chat_id, $message_id);
            break;
    }
}

function handleTextCommands($text, $telegram, $chat_id, $update, $db) {
    switch ($text) {
        case strpos($text, '/start') === 0:
            $db->handleStartCommand($telegram, $chat_id, $update);
            break;
        case isTextMatchingButtons($text):
            $languageKey = getLanguageKeyFromButtons($text);
            if ($languageKey !== null) {
                $db->updateUserLanguage($chat_id, $languageKey);
            }
            $db->handleLanguage($telegram, $chat_id);
            break;
        case $db->getPhraseText("welcome_button", $chat_id):
            $db->handleMainMenu($telegram, $chat_id);
            break;
        case $db->getPhraseText("button_balance", $chat_id):
            $db->handleBalanceCommand($telegram, $chat_id, $GLOBALS['TOKEN']);
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
            $db->getChatIdByLink($telegram, $GLOBALS['TOKEN'], $chat_id);
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
                $telegram->sendMessage([
                    'chat_id' => $chat_id,
                    'text' => 'вошло'
                ]);
                $db->saveUserText($chat_id, $telegram, $text);
            }
            break;
        case $db->getPhraseText('download_button', $chat_id):
            $db->handleDwnloadCommand($telegram, $chat_id);
            break;
    }
}

function getLanguageKeyFromButtons($text) {
    foreach ($GLOBALS['buttons'] as $key => $values) {
        if (in_array($text, $values)) {
            return $key;
        }
    }
    return null;
}

function isTextMatchingButtons($text) {
    foreach ($GLOBALS['buttons'] as $buttonValues) {
        if (in_array($text, $buttonValues)) {
            return true;
        }
    }
    return false;
}
?>
