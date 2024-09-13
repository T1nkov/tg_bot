<?php

trait AdminPanel {
	protected $conn;

	public function __construct(DatabaseConnection $dbConnection) {
        $this->conn = $dbConnection->getConnection();
    }

	public function isAdmin($telegram, $chat_id) {
		$stmt = $this->conn->prepare("SELECT role FROM users WHERE id_tg = ?");
		$stmt->bind_param("s", $chat_id);
		$stmt->execute();
		$result = $stmt->get_result();
		return $result->fetch_assoc()['role'] === 'admin';
	}
    
    public function adminModeBroadCast($chat_id, $telegram) {
		$telegram->sendMessage([
			'chat_id'      => $chat_id,
			'text'         => 'Вы вошли в рассылку',
			'reply_markup' => $telegram->buildKeyboard([
				[['Добавить текст', 'Обзор', 'Главное меню']]
			], false, true, true)
		]);
	}

	public function handleAdminPanel($telegram, $chat_id) {
		$message = 'Welcome to admin menu, /exit';
		$buttons = [
			[
				'Рассылка',
				'Каналы', // add to DB
				'Выход' // send same command /exit
			]
		];
		$telegram->sendMessage([
			'chat_id'      => $chat_id,
			'text'         => $message,
			'reply_markup' => $telegram->buildKeyboard($buttons, false, true, true)
		]);
	}
	
	public function displayChannels($telegram, $chat_id) {
        $stmt = $this->conn->prepare("SELECT channel_url FROM channel_tg");
        $stmt->execute();
        $result = $stmt->get_result();
        $channelList = '';
        while ($row = $result->fetch_assoc()) { $channelList .= $row['channel_url'] . "\n"; }
        $message = "Ссылки на каналы:\n" . $channelList;
        $keyboard = [
            'inline_keyboard' => [
                [['text' => 'Добавить ссылку на канал', 'callback_data' => 'add_channel']],
                [['text' => 'Удалить ссылку на канал', 'callback_data' => 'remove_channel']]
            ]
        ];
        $content = [
            'chat_id' => $chat_id,
            'text' => $message,
            'reply_markup' => json_encode($keyboard)
        ];
        $telegram->sendMessage($content);
    }
    
    public function promptAddChannel($telegram, $chat_id) {
        $message = "Введите ссылку для добавления:";
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $message
        ]);
    }

    public function addChannelURL($telegram, $chat_id, $url) {
        $stmt = $this->conn->prepare("SELECT MAX(id) as max_id FROM channel_tg");
        $stmt->execute();
        $result = $stmt->get_result();
        $maxId = $result->fetch_assoc()['max_id'];
        $newId = $maxId ? $maxId + 1 : 1;
        $stmt = $this->conn->prepare("INSERT INTO channel_tg (id, tg_key, tg_url) VALUES (?, ?, ?)");
        $tgKey = "tg$newId";
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
	
	public function promptRemoveChannel($telegram, $chat_id) {
        $stmt = $this->conn->prepare("SELECT channel_url FROM channel_tg");
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text'    => "Сначала добавьте каналы!"
            ]);
            return;
        }
        $keyboard = ['inline_keyboard' => []];
        while ($row = $result->fetch_assoc()) {
            $keyboard['inline_keyboard'][] = [[
                'text' => $row['channel_url'], 
                'callback_data' => 'remove_' . $row['channel_url']
            ]];
        }
        $keyboard['inline_keyboard'][] = [[
            'text' => 'Отмена', 
            'callback_data' => 'cancel_remove'
        ]];
        $telegram->sendMessage([
            'chat_id'      => $chat_id,
            'text'         => "Выберите ссылку для удаления:",
            'reply_markup' => json_encode($keyboard)
        ]);
    }

    public function removeChannelURL($telegram, $chat_id, $url) {
        $stmt = $this->conn->prepare("DELETE FROM channel_tg WHERE tg_url = ?");
        $stmt->bind_param("s", $url);
        if ($stmt->execute()) {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => "Канал $url удалён"
            ]);
            $this->reorderChannels();
        } else {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => "Ошибка при удалении канала."
            ]);
        }
    }

    private function reorderChannels() {
        $stmt = $this->conn->query("SELECT id FROM channel_tg ORDER BY id ASC");
        $counter = 1;
        while ($row = $stmt->fetch_assoc()) {
            $updateStmt = $this->conn->prepare("UPDATE channel_tg SET id = ? WHERE id = ?");
            $updateStmt->bind_param("ii", $counter, $row['id']);
            $updateStmt->execute();
            $counter++;
        }
    }
}
