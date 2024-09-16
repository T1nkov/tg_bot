<?php

trait Broadcast {
    
    private function sendPostwithTypeDetect($telegram, $row, $userID) {
        $message_sent = false;
        if (!is_null($row['video_id'])) {
            $content = ['chat_id' => $userID, 'video' => $row['video_id']];
            if ($row['message_text'] !== '') { $content['caption'] = $row['message_text']; }
            $telegram->sendVideo($content);
            $message_sent = true;
        }
        if (!is_null($row['photo_id'])) {
            $content = ['chat_id' => $userID, 'photo' => $row['photo_id']];
            if ($row['message_text'] !== '') { $content['caption'] = $row['message_text']; }
            $telegram->sendPhoto($content);
            $message_sent = true;
        }
        if (!is_null($row['audio_id'])) {
            $content = ['chat_id' => $userID, 'audio' => $row['audio_id']];
            if ($row['message_text'] !== '') { $content['caption'] = $row['message_text']; }
            $telegram->sendAudio($content);
            $message_sent = true;
        }
        if (!$message_sent && trim($row['message_text']) !== '') {
            $telegram->sendMessage([
                'chat_id' => $userID,
                'text'    => $row['message_text']
            ]);
        }
    }

    private function rowOfPost($postId) {
        $stmt = $this->conn->prepare("SELECT photo_id, video_id, audio_id, message_text FROM broadcast_posts WHERE id = ?");
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function initiateBroadcast($telegram, $chat_id) {
        $stmt = $this->conn->prepare("SELECT id, post_name FROM broadcast_posts");
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text'    => "Нет доступных постов для рассылки."
            ]);
            return;
        }
        $keyboard = ['inline_keyboard' => []];
        while ($row = $result->fetch_assoc()) {
            $keyboard['inline_keyboard'][] = [[
                'text' => $row['post_name'],
                'callback_data' => 'send_post_' . $row['id']
            ]];
        }
        $keyboard['inline_keyboard'][] = [[
            'text' => 'Отмена',
            'callback_data' => 'cancel_broadcast'
        ]];    
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text'    => "Выберите пост для рассылки:",
            'reply_markup' => json_encode($keyboard)
        ]);
    }
    
    public function handleSendPost($telegram, $postId) {
        $post = $this->rowOfPost($postId);
        $users = $this->getAllUsers();
        foreach ($users as $userId) {
            try {
                $this->sendPostwithTypeDetect($telegram, $post, $userId);
                usleep(100000); // small timeout
            } catch (Exception $e) {
                continue;
            }
        }
    }
    
    private function getAllUsers() {
        $stmt = $this->conn->prepare("SELECT id_tg FROM users");
        $stmt->execute();
        $result = $stmt->get_result();
        $users = [];
        while ($row = $result->fetch_assoc()) { $users[] = $row['id_tg']; }
        return $users;
    }
    
    public function broadcastView($telegram, $chat_id) {
        $stmt = $this->conn->prepare("SELECT id, post_name FROM broadcast_posts");
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text'    => "Нет доступных постов для рассылки."
            ]);
            return;
        }
        $keyboard = ['inline_keyboard' => []];
        while ($row = $result->fetch_assoc()) {
            $keyboard['inline_keyboard'][] = [[
                'text' => $row['post_name'],
                'callback_data' => 'view_post_' . $row['id']
            ]];
        }
        $keyboard['inline_keyboard'][] = [[
            'text' => 'Отмена',
            'callback_data' => 'cancel_broadcast'
        ]];    
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text'    => "Какой пост хотите посмотреть?",
            'reply_markup' => json_encode($keyboard)
        ]);
    }
    
    public function sendPostById($telegram, $chat_id, $postId) {
        $row = $this->rowOfPost($postId);
        $this->sendPostwithTypeDetect($telegram, $row, $chat_id);
    }

    public function displayPosts($telegram, $chat_id) {
        $stmt = $this->conn->prepare("SELECT id, post_name FROM broadcast_posts");
        $stmt->execute();
        $result = $stmt->get_result();
        $message = "Ваши посты:\n";
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $message .= $row['id'] . " - " . $row['post_name'] . "\n";
            }
        } else {
            $message .= "Нет доступных постов : /";
        }
        $keyboard = [
            'inline_keyboard' => [
                [['text' => 'Начать Рассылку', 'callback_data' => 'init_cast']],
                [['text' => 'Посмотреть пост', 'callback_data' => 'view_cast']],
                [['text' => 'Создать пост', 'callback_data' => 'create_post']],
                [['text' => 'Удалить пост', 'callback_data' => 'remove_post']]
            ]
        ];
        $content = [
            'chat_id' => $chat_id,
            'text' => $message,
            'reply_markup' => json_encode($keyboard)
        ];
        $telegram->sendMessage($content);
    }
    
    public function handlePostName($telegram, $chat_id) {
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => 'Дайте имя вашему посту: '
        ]);
        $this->setInputMode($chat_id, 'post_name');
    }

    public function handleNewPost($telegram, $chat_id) {
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => 'Отправьте мне весь пост одним сообщением:'
        ]);
        $this->setInputMode($chat_id, 'post_set');
    }

    public function uploadNameforPost($telegram, $chat_id, $postName) {
        $stmt = $this->conn->prepare("SELECT MAX(id) as max_id FROM broadcast_posts");
        $stmt->execute();
        $result = $stmt->get_result();
        $maxId = $result->fetch_assoc()['max_id'];
        $newId = $maxId ? $maxId + 1 : 1;
        $stmt = $this->conn->prepare("INSERT INTO broadcast_posts (id, post_name) VALUES (?, ?)");
        $stmt->bind_param("is", $newId, $postName);
        if ($stmt->execute()) {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => "Название поста '$postName' успешно добавлено."
            ]);
        } else {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => "Ошибка при добавлении названия поста."
            ]);
        }
    }

    public function uploadPostDetails($telegram, $chat_id, $data) {
        $stmt = $this->conn->prepare("
            SELECT post_name FROM broadcast_posts 
            WHERE photo_id IS NULL AND video_id IS NULL AND audio_id IS NULL AND message_text = '' 
            LIMIT 1
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $postName = $row['post_name'];
        $photoId = '';
        $videoId = '';
        $audioId = '';
        $messageText = '';
        if (!empty($data['message']['text'])) {
            $messageText = $data['message']['text'];
        } elseif (!empty($data['message']['photo'])) {
            $photoId = $data['message']['photo'][0]['file_id'];
            $messageText = !empty($data['message']['caption']) ? $data['message']['caption'] : '';
        } elseif (!empty($data['message']['video'])) {
            $videoId = $data['message']['video']['file_id'];
            $messageText = !empty($data['message']['caption']) ? $data['message']['caption'] : '';
        } elseif (!empty($data['message']['audio'])) {
            $audioId = $data['message']['audio']['file_id'];
            $messageText = !empty($data['message']['caption']) ? $data['message']['caption'] : '';
        }
        $updateStmt = $this->conn->prepare("
            UPDATE broadcast_posts 
            SET photo_id = ?, video_id = ?, audio_id = ?, message_text = ? 
            WHERE post_name = ?
        ");
        $updateStmt->bind_param("sssss", $photoId, $videoId, $audioId, $messageText, $postName);
        if ($updateStmt->execute()) {
            if ($updateStmt->affected_rows > 0) {
                $telegram->sendMessage([
                    'chat_id' => $chat_id,
                    'text' => "Пост'$postName' успешно добавлен."
                ]);
            } else {
                $telegram->sendMessage([
                    'chat_id' => $chat_id,
                    'text' => "Запись '$postName' не обновлена."
                ]);
            }
        } else {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => "Ошибка при обновлении поста '$postName'."
            ]);
        }
    }
    
    public function promptRemovePost($telegram, $chat_id) { // same AdminPanel
        $stmt = $this->conn->prepare("SELECT id, post_name FROM broadcast_posts");
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text'    => "Сначала добавьте посты!"
            ]);
            return;
        }
        $keyboard = ['inline_keyboard' => []];
        while ($row = $result->fetch_assoc()) {
            $keyboard['inline_keyboard'][] = [[
                'text' => $row['post_name'], 
                'callback_data' => 'remove_post_' . $row['id']
            ]];
        }
        $keyboard['inline_keyboard'][] = [[
            'text' => 'Отмена', 
            'callback_data' => 'cancel_remove_post'
        ]];
        $telegram->sendMessage([
            'chat_id'      => $chat_id,
            'text'         => "Выберите пост для удаления:",
            'reply_markup' => json_encode($keyboard)
        ]);
    }

    public function removePostById($telegram, $chat_id, $postId) {
        $stmt = $this->conn->prepare("DELETE FROM broadcast_posts WHERE id = ?");
        $stmt->bind_param("i", $postId);
        if ($stmt->execute()) {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => "Пост с ID $postId удалён"
            ]);
            $this->reorderPosts();
            $this->displayPosts($telegram, $chat_id);
        } else {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => "Ошибка при удалении поста."
            ]);
        }
    }
    
    private function reorderPosts() {
        $stmt = $this->conn->query("SELECT id FROM broadcast_posts ORDER BY id ASC");
        $counter = 1;
        while ($row = $stmt->fetch_assoc()) {
            $updateStmt = $this->conn->prepare("UPDATE broadcast_posts SET id = ? WHERE id = ?");
            $updateStmt->bind_param("ii", $counter, $row['id']);
            $updateStmt->execute();
            $counter++;
        }
    }

    public function broadcastToAllByCron($telegram) {
        $stmt = $this->conn->prepare("SELECT id FROM broadcast_posts WHERE status = 'pending'");
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $stmt = $this->conn->prepare("SELECT id FROM broadcast_posts ORDER BY id LIMIT 1");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $firstPost = $result->fetch_assoc();
                $this->handleSendPost($telegram, $firstPost['id']);
                $stmt = $this->conn->prepare("SELECT id FROM broadcast_posts WHERE id > ? ORDER BY id LIMIT 1");
                $stmt->bind_param("i", $firstPost['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $nextPost = $result->fetch_assoc();
                    $updateStmt = $this->conn->prepare("UPDATE broadcast_posts SET status = 'pending' WHERE id = ?");
                    $updateStmt->bind_param("i", $nextPost['id']);
                    $updateStmt->execute();
                } else {
                    $updateStmt = $this->conn->prepare("UPDATE broadcast_posts SET status = '' WHERE id = ?");
                    $updateStmt->bind_param("i", $firstPost['id']);
                    $updateStmt->execute();
                }
            } else {
                echo "No post added!";
            }
        } else {
            $pendingPost = $result->fetch_assoc();
            $this->handleSendPost($telegram, $pendingPost['id']);
            $updateStmt = $this->conn->prepare("UPDATE broadcast_posts SET status = '' WHERE id = ?");
            $updateStmt->bind_param("i", $pendingPost['id']);
            $updateStmt->execute();
            $stmt = $this->conn->prepare("SELECT id FROM broadcast_posts WHERE id > ? ORDER BY id LIMIT 1");
            $stmt->bind_param("i", $pendingPost['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $nextPost = $result->fetch_assoc();
                $updateStmt = $this->conn->prepare("UPDATE broadcast_posts SET status = 'pending' WHERE id = ?");
                $updateStmt->bind_param("i", $nextPost['id']);
                $updateStmt->execute();
            } else {
                $updateStmt = $this->conn->prepare("UPDATE broadcast_posts SET status = '' WHERE id = ?");
                $updateStmt->bind_param("i", $pendingPost['id']);
                $updateStmt->execute();
            }
        }
    }
    
    
}
