<?php

trait SubscribeLogic {

    public function handleJoinChannelCommand($telegram, $chat_id, $message_id, $tg_key = null) {
        $tg_key = $this->getAvailableChannelKey($chat_id);
        if ($tg_key === false) {
            $message = "🥳 Вы подписались на все каналы!";
            $keyboard = json_encode([]);
            $telegram->editMessageText([
                'chat_id' => $chat_id,
                'message_id' => $message_id,
                'text' => $message,
                'reply_markup' => $keyboard
            ]);
            return;
        }
        $channelURL = $this->getURL($tg_key);
        $handleMessage = $this->getPhraseText("join_text", $chat_id);
        $message = str_replace(
            ['{$sum}', '{$chanURL}'],
            [$GLOBALS['joinChannelPay'], $channelURL],
            $handleMessage
        );
        $keyboard = json_encode([
            'inline_keyboard' => [
                [['text' => $this->getPhraseText("checkChannel_button", $chat_id), 'callback_data' => 'check']],
                [['text' => $this->getPhraseText("skipChannel_button", $chat_id), 'callback_data' => 'skip']]
            ]
        ]);
        $content = [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => $message,
            'reply_markup' => $keyboard
        ];
        $telegram->editMessageText($content);
    }

    public function handleSubscribeCommand($telegram, $chat_id, $message_id) {
        $tg_key = $this->getAvailableChannelKey($chat_id);
        $response = $telegram->getChatMember(['chat_id' => $tg_key, 'user_id' => $chat_id]);
        $subscriptionStatus = $response['result']['status'];
        if ($subscriptionStatus === 'member' || $subscriptionStatus === 'administrator' || $subscriptionStatus === 'creator') {
            $message = "✅ Проверка прошла! {$GLOBALS['subscribeSumValue']}\nОставайтесь активными и не отписывайтесь от канала в течение 5 дней. Если вы отпишетесь, деньги вернутся.";
            $keyboard = json_encode(['inline_keyboard' => [[['text' => 'Next', 'callback_data' => 'next']]]]);
            $this->incrementBalance($chat_id, $GLOBALS['subscribeSumValue']);
        } else {
            $channelURL = $this->getURL($tg_key);
            $message = "❌ Проверить не удалось! Подпишитесь на канал: {$channelURL}";
            $keyboard = json_encode([
                'inline_keyboard' => [
                    [['text' => $this->getPhraseText("checkChannel_button", $chat_id), 'callback_data' => 'check']],
                    [['text' => $this->getPhraseText("skipChannel_button", $chat_id), 'callback_data' => 'skip']]
                ]
            ]);
        }
        $telegram->editMessageText([
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => $message,
            'reply_markup' => $keyboard
        ]);
    }

    private function getAvailableChannelKey($user_id) {
        $subscribedChannels = $this->getSubscribedChannels($user_id);
        $allChannels = $this->getAllChannels();
        foreach ($allChannels as $channel) {
            if (!in_array($channel['tg_key'], $subscribedChannels)) {
                return $channel['tg_key'];
            }
        }
        return false;
    }

    private function getAllChannels() {
        $sql = "SELECT tg_key, tg_url FROM channel_tg";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $channels = [];
        while ($row = $result->fetch_assoc()) {
            $channels[] = [
                'tg_key' => $row['tg_key'],
                'tg_url' => $row['tg_url']
            ];
        }
        return $channels;
    }

    private function addSubscription($user_id, $tg_key) {
        $sql = "INSERT INTO user_subscriptions (id_tg, tg_key, subscribed_at) VALUES (?, ?, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('is', $user_id, $tg_key);
        $stmt->execute();
    }

    private function getSubscribedChannels($user_id) {
        $sql = "SELECT tg_key FROM user_subscriptions WHERE id_tg = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $subscribedChannels = [];
        while ($row = $result->fetch_assoc()) {
            $subscribedChannels[] = $row['tg_key'];
        }
        return $subscribedChannels;
    }
}
