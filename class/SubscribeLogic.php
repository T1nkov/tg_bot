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
            error_log("Ошибка: " . $this->conn->error);
            return null;
        }
    }
    
    public function handleJoinChannelCommand($telegram, $chat_id, $message_id) {
        $channel = $this->getNextChannel($chat_id);
        if ($channel) {
            $tg_key = $channel['tg_key'];
            $channelURL = $channel['tg_url'];
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
        } else {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => "Err.",
            ]);
        }
    }

}