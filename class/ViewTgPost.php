<?php

trait ViewTgPost {
    public function getUniquePostUrl($chat_id) {
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

    public function markPostAsSeen($chat_id, $post_url) {
        $stmt = $this->conn->prepare("INSERT INTO post_views (user_id, post_id) VALUES (?, ?)");
        $stmt->bind_param("is", $chat_id, $post_url);
        $stmt->execute();
    }

    public function getSeenPosts($chat_id) {
        $seenPostUrls = [];
        $stmt = $this->conn->prepare("SELECT post_url FROM post_views WHERE user_id = ?");
        $stmt->bind_param("i", $chat_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) { $seenPostUrls[] = $row['post_id']; }
        return $seenPostUrls;
    }
}

// the functionality seems to be ready, but stupid limitations of telegram do not allow to use it