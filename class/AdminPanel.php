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
		$isAdmin = $this->isAdmin($telegram, $chat_id);
		$message = $isAdmin
			? 'Welcome to admin menu, /exit'
			: 'Sorry, you\'re not an admin, contact with the owner if you really are.', return;
		$buttons = [
			[
				'Рассылка',
				'Добавить ссылку на канал', // add it to DB
				'Выход' // send same /exit command
			]
		];
		$telegram->sendMessage([
			'chat_id'      => $chat_id,
			'text'         => $message,
			'reply_markup' => $telegram->buildKeyboard($buttons, false, true, true)
		]);
	}
}
