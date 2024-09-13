<?php

trait ViewTgPost {
    
    public function handleViewPost($telegram, $chat_id) {
        $nextAvailableTime = $this->getNextAvailableViewTime($chat_id); 
        if ($nextAvailableTime > time()) {
            $remainingTime = $nextAvailableTime - time();
            $formattedTime = $this->formatTime($remainingTime);
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text'    => "Просмотр постов будет доступен через $formattedTime"
            ]);
            return;
        }
        $posts = $this->getPosts();
        $totalPosts = count($posts);
        $currentIndex = 0;
        $messageId = $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text'    => "1/$totalPosts Просмотр поста: " . $posts[$currentIndex]['post_url']
        ])['result']['message_id'];
        while ($currentIndex < $totalPosts) {
            sleep(1.5);
            $currentIndex++;
            if ($currentIndex >= $totalPosts) {
                $this->incrementBalance($chat_id, $GLOBALS['watchSumValue']);
                $telegram->editMessageText([
                    'chat_id' => $chat_id,
                    'text'    => "Просмотр постов завершён вам зачислили - " . $GLOBALS['watchSumValue'],
                    'message_id' => $messageId
                ]);
                $this->setNextAvailableViewTime($chat_id); 
                return;
            }
            $telegram->editMessageText([
                'chat_id' => $chat_id,
                'text'    => ($currentIndex + 1) . "/$totalPosts Просмотр поста: " . $posts[$currentIndex]['post_url'],
                'message_id' => $messageId
            ]);
        }
    }
    
    private function getNextAvailableViewTime($chat_id) {
        $stmt = $this->conn->prepare("SELECT next_available_time FROM users WHERE id_tg = ?");
        $stmt->bind_param("s", $chat_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) { return $row['next_available_time']; }
        return 0;
    }
    
    private function formatTime($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;
        return "{$hours}h {$minutes}min {$seconds}sec";
    }
    
    private function setNextAvailableViewTime($chat_id) {
        $nextTime = time() + (12 * 3600);
        $stmt = $this->conn->prepare("UPDATE users SET next_available_time = ? WHERE id_tg = ?");
        $stmt->bind_param("is", $nextTime, $chat_id);
        $stmt->execute();
    }
    
    private function getPosts() {
        $result = $this->conn->query("SELECT post_url FROM posts");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // the functionality seems to be ready, but stupid limitations of telegram do not allow to use it
    private function getUniquePostUrl($chat_id) {
        $seenPostUrls = $this->getSeenPosts($chat_id);
        $seenPostUrlList = empty($seenPostUrls) ? "NULL" : "'" . implode("','", $seenPostUrls) . "'";
        $query = "SELECT post_url FROM posts WHERE post_url NOT IN ($seenPostUrlList) LIMIT 1";
        $result = $this->conn->query($query);
        if ($result && $result->num_rows > 0) {
            $post = $result->fetch_assoc();
            return $post['post_url'];
        }
        return null;
    }

    private function markPostAsSeen($chat_id, $post_url) {
        $stmt = $this->conn->prepare("INSERT INTO post_views (user_id, post_id) VALUES (?, ?)");
        $stmt->bind_param("is", $chat_id, $post_url);
        $stmt->execute();
    }

    private function getSeenPosts($chat_id) {
        $seenPostUrls = [];
        $stmt = $this->conn->prepare("SELECT post_url FROM post_views WHERE user_id = ?");
        $stmt->bind_param("i", $chat_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) { $seenPostUrls[] = $row['post_id']; }
        return $seenPostUrls;

    }

}
