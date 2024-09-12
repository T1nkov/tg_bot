<?php

function processCallback($db, $telegram, $chat_id, $message_id, $callback_data) {
    switch ($callback_data) {
        case 'withdraw':
            $db->handleWithdrawCommand($telegram, $chat_id, $message_id);
            break;
        case 'rep_ru':
            $db->handleReportCommand($telegram, $chat_id, $message_id);
            break;
        case 'fraud':
        case '(fraud)':
            $db->handleFraudCommand($telegram, $chat_id, $message_id);
            break;
        case 'spam':
        case '(spam)':
            $db->handleSpamCommand($telegram, $chat_id, $message_id);
            break;
        case 'violence':
        case '(violence)':
            $db->handleViolenceCommand($telegram, $chat_id, $message_id);
            break;
        case 'copyright':
        case '(copyright)':
            $db->handleCopyrightCommand($telegram, $chat_id, $message_id);
            break;
        case 'other':
        case '(other)':
            $db->handleOtherCommand($telegram, $chat_id, $message_id);
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
        case 'view_post':
            $db->count_to_ten($telegram, $chat_id, $message_id);
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

function processTextCommands($db, $telegram, $chat_id, $text, $update) {
    $commandMapping = [
        '/start' => function() use ($db, $telegram, $chat_id, $update) {
            $db->handleStartCommand($telegram, $chat_id, $update);
        },
        'welcome_button' => function() use ($db, $telegram, $chat_id) {
            $db->handleMainMenu($telegram, $chat_id);
        },
        'button_balance' => function() use ($db, $telegram, $chat_id) {
            $db->handleBalanceCommand($telegram, $chat_id, $GLOBALS['TOKEN']);
        },
        'button_partners' => function() use ($db, $telegram, $chat_id) {
            $db->handlePartnerCommand($telegram, $chat_id);
        },
        'button_changeLang' => function() use ($db, $telegram, $chat_id, $update) {
            $db->handleStartCommand($telegram, $chat_id, $update);
        },
        'button_Help' => function() use ($db, $telegram, $chat_id) {
            $db->handleHelpCommand($telegram, $chat_id);
        },
        'button_earn' => function() use ($db, $telegram, $chat_id) {
            $db->handleEarnCommand($telegram, $chat_id);
        },
        'download_button' => function() use ($db, $telegram, $chat_id) {
            $db->handleDwnloadCommand($telegram, $chat_id);
        },
        'Главное меню' => function() use ($db, $telegram, $chat_id) {
            $db->setInputMode($chat_id, 'def');
            $db->handleMainMenu($telegram, $chat_id);
        },
    ];

    foreach ($GLOBALS['buttons'] as $key => $values) {
        if (in_array($text, $values)) {
            $db->updateUserLanguage($chat_id, $key);
            $db->handleLanguage($telegram, $chat_id);
            break;
        }
    }

    if ($text !== null && $db->isInputMode($chat_id) === 'input_mode') {
        $telegram->sendMessage(['chat_id' => $chat_id, 'text' => 'вошло']);
        $db->saveUserText($chat_id, $telegram, $text);
    }

    switch ($text) {
        case $db->getPhraseText("button_balance", $chat_id):
        case $db->getPhraseText("button_partners", $chat_id):
        case $db->getPhraseText("button_changeLang", $chat_id):
        case $db->getPhraseText("button_Help", $chat_id):
        case $db->getPhraseText("button_earn", $chat_id):
        case $db->getPhraseText("download_button", $chat_id):
            $commandMapping[$text]();
            break;
        case 'Админ кнопка':
            $db->getChatIdByLink($telegram, $GLOBALS['TOKEN'], $chat_id);
            break;
        case 'Рассылка':
            if ($db->isAdmin($telegram, $chat_id) === 'admin') {
                $db->takeAllId($telegram, $chat_id);
                $db->adminModeRas($chat_id, $telegram);
            }
            break;
        case 'Добавить текст':
            if ($db->isAdmin($telegram, $chat_id) === 'admin') {
                $db->handleUserInput($chat_id, $telegram);
            }
            break;
    }
}
