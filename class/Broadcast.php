<?php

trait Broadcast {
    
    public function initiateBroadcast() {

    }

    public function displayPosts($telegram, $chat_id) {
        $message = "Ваши посты:\n" . $channelList;
        $keyboard = [
            'inline_keyboard' => [
                [['text' => 'Начать Рассылку', 'callback_data' => 'init_cast']],
                [['text' => 'Создать пост', 'callback_data' => 'create_post']]
            ]
        ];
        $content = [
            'chat_id' => $chat_id,
            'text' => $message,
            'reply_markup' => json_encode($keyboard)
        ];
        $telegram->sendMessage($content);
    }

    public function handleNewPost($telegram, $chat_id) {
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => 'Отправьте мне весь пост одним сообщением:'
        ]);
        $this->setInputMode($chat_id, 'post_edit');
    }

    public function addBroadcastMessageToDB($telegram, $chat_id, $url) {
        $stmt = $this->conn->prepare("SELECT MAX(id) as max_id FROM channel_tg");
        $stmt->execute();
        $result = $stmt->get_result();
        $maxId = $result->fetch_assoc()['max_id'];
        $newId = $maxId ? $maxId + 1 : 1;
        $stmt = $this->conn->prepare("INSERT INTO channel_tg (id, tg_key, tg_url) VALUES (?, ?, ?)");
        $tgKey = $this->getChatId($telegram, $url);
        $stmt->bind_param("iss", $newId, $tgKey, $url);
        if ($stmt->execute()) {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => "Канал $url добавлен с tg_key $tgKey"
            ]);
        } else {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => "Ошибка при добавлении канала."
            ]);
        }
        $this->displayChannels($telegram, $chat_id);
    }
}