<?php
trait SubscribeLogic {

    private function getNextChannel($chat_id) {
        $user_id = $this->getUserId($chat_id);
        $query = "
            SELECT ct.tg_key, ct.tg_url
            FROM channel_tg ct
            LEFT JOIN user_subscriptions us ON ct.tg_key = us.tg_key AND us.id_tg = ?
            WHERE us.id_tg IS NULL LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function handleJoinChannelCommand($telegram, $chat_id, $user_id) {
        $nextChannel = $this->getNextChannel($chat_id);
        if (!$nextChannel) {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => "🥳 Вы подписались на все каналы!",
            ]);
            return;
        }
        $message = "Подпишись на канал и получи " . $GLOBALS['subscribeSumValue'];
        $keyboard = [
            'inline_keyboard' => [
                [['text' => 'Проверить', 'callback_data' => 'check_subscription']],
                [['text' => 'Пропустить', 'callback_data' => 'skip']]
            ]
        ];
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $message,
            'reply_markup' => json_encode($keyboard)
        ]);
    }

    public function handleCheckSubscription($telegram, $chat_id, $user_id) {
        $nextChannel = $this->getNextChannel($chat_id);
        if ($nextChannel) {
            $channel_id = $nextChannel['tg_key'];
            $isSubscribed = $this->checkSubscription($telegram, $user_id, $channel_id);
            if ($isSubscribed) {
                $this->insertSubscription($user_id, $channel_id);
                $this->incrementBalance($chat_id, $GLOBALS['subscribeSumValue']);
                $successMessage = "✅ Проверка прошла! Вам зачислено " . $GLOBALS['subscribeSumValue'];
                $this->editMessage($telegram, $chat_id, $successMessage);
                $this->handleJoinChannelCommand($telegram, $chat_id, $user_id);
            } else {
                $this->showFailedSubscriptionMessage($telegram, $chat_id, $nextChannel['tg_url']);
            }
        }
    }

    private function showFailedSubscriptionMessage($telegram, $chat_id, $channel_url) {
        $failedMessage = "❌ Проверить не удалось! Подпишитесь на канал: " . $channel_url;
        $keyboard = [
            'inline_keyboard' => [
                [['text' => 'Проверить', 'callback_data' => 'check_subscription']],
                [['text' => 'Пропустить', 'callback_data' => 'skip']]
            ]
        ];

        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $failedMessage,
            'reply_markup' => json_encode($keyboard)
        ]);
    }

    private function insertSubscription($user_id, $channel_id) {
        $stmt = $this->conn->prepare("INSERT INTO user_subscriptions (id_tg, tg_key) VALUES (?, ?)
                                        ON DUPLICATE KEY UPDATE subscribed_at = CURRENT_TIMESTAMP");
        $stmt->bind_param("ii", $user_id, $channel_id);
        $stmt->execute();
    }

    public function checkSubscription($telegram, $user_id, $channel_id) {
        try {
            $response = $telegram->getChatMember(['chat_id' => $channel_id, 'user_id' => $user_id]);
            if (isset($response['result']['status'])) {
                return in_array($response['result']['status'], ['member', 'administrator', 'creator']);
            }
        } catch (Exception $e) {
            error_log('Ошибка при проверке подписки: ' . $e->getMessage());
        }
        return false;
    }

    private function editMessage($telegram, $chat_id, $text) {
        $telegram->editMessageText([
            'chat_id' => $chat_id,
            'text'    => $text,
            'reply_markup' => json_encode($this->getInitialKeyboard()),
        ]);
    }

    private function getInitialKeyboard() { return []; }
}
