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
            'callback_data' => 'cancel_post_job'
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
                usleep(50000); // small timeout
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
            'callback_data' => 'cancel_post_job'
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
        $stmt = $this->conn->prepare("SELECT id, post_name, switch FROM broadcast_posts");
        $stmt->execute();
        $result = $stmt->get_result();
        $message = "Ваши посты:\n";
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $switch = ($row['switch'] === 'enabled') ? '✅' : '❌';
                $message .= $row['id'] . " - " . $switch . " " . $row['post_name'] . " \n";
            }
        } else {
            $message .= "Нет доступных постов : /";
        }
        $keyboard = [
            'inline_keyboard' => [
                [['text' => 'Начать Рассылку', 'callback_data' => 'init_cast']],
                [['text' => 'Посмотреть пост', 'callback_data' => 'view_cast']],
                [['text' => 'Изменить статус поста', 'callback_data' => 'sw_post_st']],
                [['text' => 'Создать пост', 'callback_data' => 'create_post']],
                [['text' => 'Удалить пост', 'callback_data' => 'remove_post']],
                [
                    ['text' => 'Возобновить Рассылку', 'callback_data' => 'resume_bc'],
                    ['text' => 'Остановить Рассылку', 'callback_data' => 'brake_bc']
                ]
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
            'callback_data' => 'cancel_post_job'
        ]];
        $telegram->sendMessage([
            'chat_id'      => $chat_id,
            'text'         => "Выберите пост для удаления:",
            'reply_markup' => json_encode($keyboard)
        ]);
    }

    public function promptSwitchStatusPost($telegram, $chat_id) {
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
                'callback_data' => 'switch_post_' . $row['id']
            ]];
        }
        $keyboard['inline_keyboard'][] = [[
            'text' => 'Отмена', 
            'callback_data' => 'cancel_post_job'
        ]];
        $telegram->sendMessage([
            'chat_id'      => $chat_id,
            'text'         => "Выберите пост для изменения его состояния:",
            'reply_markup' => json_encode($keyboard)
        ]);
    }

    public function switchStatusPostById($telegram, $postId) {
        $stmt = $this->conn->prepare("SELECT switch FROM broadcast_posts WHERE id = ?");
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $currentStatus = $row['switch'];
        $newStatus = ($currentStatus === 'enabled') ? 'disabled' : 'enabled';
        $stmt = $this->conn->prepare("UPDATE broadcast_posts SET switch = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $postId);
        $stmt->execute();
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
        $stmt = $this->conn->prepare("SELECT id FROM broadcast_posts WHERE status != 'halted'");
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) { return; }
        $stmt = $this->conn->prepare("SELECT id FROM broadcast_posts WHERE status = 'pending'");
        $stmt->execute();
        $resultPending = $stmt->get_result();
        if ($resultPending->num_rows === 0) {
            $stmt = $this->conn->prepare("SELECT id FROM broadcast_posts WHERE switch = 'enabled' ORDER BY id LIMIT 1");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $firstPost = $result->fetch_assoc();
                $this->handleSendPost($telegram, $firstPost['id']);
                $updateStmt = $this->conn->prepare("UPDATE broadcast_posts SET status = '' WHERE id = ?");
                $updateStmt->bind_param("i", $firstPost['id']);
                $updateStmt->execute();
                $stmt = $this->conn->prepare("SELECT id FROM broadcast_posts WHERE id > ? AND switch = 'enabled' ORDER BY id LIMIT 1");
                $stmt->bind_param("i", $firstPost['id']);
                $stmt->execute();
                $resultNext = $stmt->get_result();
                if ($resultNext->num_rows > 0) {
                    $nextPost = $resultNext->fetch_assoc();
                    $updateStmt = $this->conn->prepare("UPDATE broadcast_posts SET status = 'pending' WHERE id = ?");
                    $updateStmt->bind_param("i", $nextPost['id']);
                    $updateStmt->execute();
                } else {
                    $updateStmt = $this->conn->prepare("UPDATE broadcast_posts SET status = 'halted'");
                    $updateStmt->execute();
                }
            } else {
                echo "No post added!";
            }
        } else {
            $pendingPost = $resultPending->fetch_assoc();
            $stmt = $this->conn->prepare("SELECT switch FROM broadcast_posts WHERE id = ?");
            $stmt->bind_param("i", $pendingPost['id']);
            $stmt->execute();
            $checkResult = $stmt->get_result();
            if ($checkResult->num_rows > 0) {
                $checkRow = $checkResult->fetch_assoc();
                if ($checkRow['switch'] === 'enabled') {
                    $this->handleSendPost($telegram, $pendingPost['id']);
                    $updateStmt = $this->conn->prepare("UPDATE broadcast_posts SET status = '' WHERE id = ?");
                    $updateStmt->bind_param("i", $pendingPost['id']);
                    $updateStmt->execute();
                    $stmt = $this->conn->prepare("SELECT id FROM broadcast_posts WHERE id > ? AND switch = 'enabled' ORDER BY id LIMIT 1");
                    $stmt->bind_param("i", $pendingPost['id']);
                    $stmt->execute();
                    $resultNext = $stmt->get_result();
                    if ($resultNext->num_rows > 0) {
                        $nextPost = $resultNext->fetch_assoc();
                        $updateStmt = $this->conn->prepare("UPDATE broadcast_posts SET status = 'pending' WHERE id = ?");
                        $updateStmt->bind_param("i", $nextPost['id']);
                        $updateStmt->execute();
                    } else {
                        $updateStmt = $this->conn->prepare("UPDATE broadcast_posts SET status = 'halted'");
                        $updateStmt->execute();
                    }
                } else {
                    $stmt = $this->conn->prepare("SELECT id FROM broadcast_posts WHERE id > ? AND switch = 'enabled' ORDER BY id LIMIT 1");
                    $stmt->bind_param("i", $pendingPost['id']);
                    $stmt->execute();
                    $resultNext = $stmt->get_result();
                    if ($resultNext->num_rows > 0) {
                        $nextPost = $resultNext->fetch_assoc();
                        $this->handleSendPost($telegram, $nextPost['id']);
                        $updateStmt = $this->conn->prepare("UPDATE broadcast_posts SET status = '' WHERE id = ?");
                        $updateStmt->bind_param("i", $pendingPost['id']);
                        $updateStmt->execute();
                        $stmt = $this->conn->prepare("SELECT id FROM broadcast_posts WHERE id > ? AND switch = 'enabled' ORDER BY id LIMIT 1");
                        $stmt->bind_param("i", $nextPost['id']);
                        $stmt->execute();
                        $resultNext = $stmt->get_result();
                        if ($resultNext->num_rows > 0) {
                            $nextPendingPost = $resultNext->fetch_assoc();
                            $updateStmt = $this->conn->prepare("UPDATE broadcast_posts SET status = 'pending' WHERE id = ?");
                            $updateStmt->bind_param("i", $nextPendingPost['id']);
                            $updateStmt->execute();
                        } else {
                            $updateStmt = $this->conn->prepare("UPDATE broadcast_posts SET status = 'halted'");
                            $updateStmt->execute();
                        }
                    } else {
                        $updateStmt = $this->conn->prepare("UPDATE broadcast_posts SET status = 'halted'");
                        $updateStmt->execute();
                    }
                }
            }
        }
    }
    
    public function startBC($telegram, $chat_id) {
        $updateStmt = $this->conn->prepare("UPDATE broadcast_posts SET status = '' WHERE status = 'halted'");
        $updateStmt->execute();
        $telegram->sendMessage(['chat_id' => $chat_id, 'text' => 'Рассылка начата.']);
    }
    
    
    public function stopBC($telegram, $chat_id) {
        $updateStmt = $this->conn->prepare("UPDATE broadcast_posts SET status = 'halted'");
        $updateStmt->execute();
        $telegram->sendMessage(['chat_id' => $chat_id, 'text' => 'Рассылка остановлена.']);
    }
}
