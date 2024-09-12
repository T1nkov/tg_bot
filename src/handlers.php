<?php

function handleCallbackQuery($callback_data, $telegram, $chat_id, $message_id, $db) {
    $commands = [
        'withdraw' => 'handleWithdrawCommand',
        'rep_ru' => 'handleReportCommand',
        'fraud' => 'handleFraudCommand',
        '(fraud)' => 'handleApprovCommand',
        'spam' => 'handleSpamCommand',
        '(spam)' => 'handleApprovCommand',
        'violence' => 'handleViolenceCommand',
        '(violence)' => 'handleApprovCommand',
        'copyright' => 'handleCopyrightCommand',
        '(copyright)' => 'handleApprovCommand',
        'other' => 'handleOtherCommand',
        '(other)' => 'handleApprovCommand',
        'yes' => 'handleApprovCommand',
        'invite_friend' => 'handlePartnerCommand',
        'join_channel' => 'handleJoinChannelCommand',
        'skip' => 'handleJoinChannelCommand',
        'view_post' => 'count_to_ten',
        'check' => 'handleSubscribeCheckCommand',
        'checkSub' => 'handleBalanceCommand',
        'no' => 'handleCanceledCommand'
    ];

    if (isset($commands[$callback_data])) {
        $db->{$commands[$callback_data]}($telegram, $chat_id, $message_id, $callback_data);
    }
}

function handleTextInput($text, $telegram, $chat_id, $db, $update) {
    if (is_null($text)) {
        error_log("Received null text input.");
        return;
    }
    if (strpos($text, '/start') === 0) {
        $db->handleStartCommand($telegram, $chat_id, $update);
    } elseif (isTextMatchingButtons($text)) {
        foreach ($GLOBALS['config']['buttons'] as $key => $values) {
            if (in_array($text, $values)) {
                $db->updateUserLanguage($chat_id, $key);
                break;
            }
        }
        $db->handleLanguage($telegram, $chat_id);
    } else {
        $phrases = [
            "welcome_button" => 'handleMainMenu',
            "button_balance" => 'handleBalanceCommand',
            "button_partners" => 'handlePartnerCommand',
            "button_changeLang" => 'handleStartCommand',
            "button_Help" => 'handleHelpCommand',
            "button_earn" => 'handleEarnCommand',
            "download_button" => 'handleDwnloadCommand'
        ];

        foreach ($phrases as $phraseKey => $command) {
            $phraseText = $db->getPhraseText($phraseKey, $chat_id);
            if ($text == $phraseText) {
                $db->{$command}($telegram, $chat_id);
                break;
            }
        }
        handleAdminCommands($text, $telegram, $chat_id, $db);
    }
}

function handleAdminCommands($text, $telegram, $chat_id, $db) {
    if ($text === 'Главное меню') {
        $db->setInputMode($chat_id, 'def');
        $db->handleMainMenu($telegram, $chat_id);
    } elseif ($text !== null && $db->isInputMode($chat_id) === 'input_mode') {
        $telegram->sendMessage(['chat_id' => $chat_id, 'text' => 'вошло']);
        $db->saveUserText($chat_id, $telegram, $text);
    } elseif ($text === 'Админ кнопка' && $db->isAdmin($telegram, $chat_id) === 'admin') {
        $db->getChatIdByLink($telegram, $GLOBALS['TOKEN'], $chat_id);
    } elseif ($text === 'Рассылка' && $db->isAdmin($telegram, $chat_id) === 'admin') {
        $db->takeAllId($telegram, $chat_id);
        $db->adminModeRas($chat_id, $telegram);
    } elseif ($text === 'Добавить текст' && $db->isAdmin($telegram, $chat_id) === 'admin') {
        $db->handleUserInput($chat_id, $telegram);
    }
}

?>
