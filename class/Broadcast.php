<?php

trait Broadcast {
    
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

    public function handleNewPost() {

    }

    public function initiateBroadcast() {

    }

}