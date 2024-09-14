<?php
trait SubscribeLogic {

    private function getNextChannel($chat_id) {
        $user_id = $this->getUserId($chat_id);
        $query = "
            SELECT ct.tg_key, ct.tg_url
            FROM channel_tg ct
            LEFT JOIN user_subscriptions us ON ct.tg_key = us.tg_key AND us.id_tg = ?
            WHERE us.id_tg IS NULL
            LIMIT 1";
        if ($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $channel = $result->fetch_assoc();
            $stmt->close();
            return $channel;
        } else {
            error_log("Err. " . $this->conn->error);
            return null;
        }
    }

    public function handleJoinChannelCommand($telegram, $chat_id, $message_id) {
		$tg_key = this->getNextChannel($chat_id);
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
		try {
			$telegram->editMessageText($content);
		} catch (Exception $e) {
			error_log('Ошибка при редактировании сообщения: ' . $e->getMessage());
		}
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
