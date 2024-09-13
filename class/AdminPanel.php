<?php

class AdminPanel {
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
		$stmt = $this->conn->prepare("SELECT channel_url FROM channels");
		$stmt->execute();
		$result = $stmt->get_result();
		$channelList = '';
		while ($row = $result->fetch_assoc()) {
			$channelList .= $row['channel_url'] . "\n";
		}
		$message = "Ссылки на каналы:\n" . $channelList;
		$keyboard = [
			'inline_keyboard' => [
				[['text' => 'Добавить ссылку на канал', 'callback_data' => 'add_channel']],
				[['text' => 'Удалить ссылку на канал', 'callback_data' => 'remove_channel']]
			]
		];
		$content = [
			'chat_id'      => $chat_id,
			'text'         => $message,
			'reply_markup' => json_encode($keyboard)
		];
		$telegram->sendMessage($content);
	}
	
}
