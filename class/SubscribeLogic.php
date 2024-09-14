<?php

trait SubscribeLogic {

    public function handleJoinChannelCommand($telegram, $chat_id, $message_id, $tg_key = null) {
        $tg_key = $this->getAvailableChannelKey($chat_id);
        if ($tg_key === false) {
            $message = $this->getPhraseText("all_subscribed", $chat_id);
            $telegram->editMessageText([
                'chat_id' => $chat_id,
                'message_id' => $message_id,
                'text' => $message,
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
            $message = strtr($this->getPhraseText("verified_sub", $chat_id), [
                '{sub_sum_val}' => $GLOBALS['subscribeSumValue'],
            ]);
            $keyboard = json_encode(['inline_keyboard' => [[['text' => 'Next', 'callback_data' => 'next']]]]);
            $this->incrementBalance($chat_id, $GLOBALS['subscribeSumValue']);
            $this->addSubscription($chat_id, $tg_key);
        } else {
            $channelURL = $this->getURL($tg_key);
            $message = strtr($this->getPhraseText("canceled_sub", $chat_id), [
                '{channel_url}' => $channelURL,
            ]);
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
        $allChannelKeys = array_column($allChannels, 'tg_key');
        if (empty(array_diff($allChannelKeys, $subscribedChannels))) { return false; }
        foreach ($allChannels as $channel) { if (!in_array($channel['tg_key'], $subscribedChannels)) { return $channel['tg_key']; } }
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
