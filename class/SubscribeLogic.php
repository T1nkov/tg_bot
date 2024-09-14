<?php
trait SubscribeLogic {

    private function getFirstTgKey() {
        $query = "SELECT tg_key FROM channel_tg LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return $row['tg_key'];
        }
        return null;
    }
    
    public function handleJoinChannelCommand($telegram, $chat_id, $message_id) {
		$tg_key = this->getFirstTgKey();
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
			error_log('Err.' . $e->getMessage());
		}
	}

}