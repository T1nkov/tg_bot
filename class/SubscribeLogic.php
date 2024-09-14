<?php

trait SubscribeLogic {

    public function handleJoinChannelCommand($telegram, $chat_id, $message_id) {
		$tg_key = 'tg' . $GLOBALS['valueTg'];
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
    
}