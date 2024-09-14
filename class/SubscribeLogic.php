<?php
trait SubscribeLogic {

    public function handleJoinChannelCommand($telegram, $chat_id, $message_id) {
		$tg_key = getKey();
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

    public function getKey() {
        $sql = "SELECT tg_key FROM channel_tg LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($tg_key);
            $stmt->fetch();
            $stmt->close();
            return $tg_key;
        }
        $stmt->close();
        return null;
    }
    
}