<?php

class AdminPanel {
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
}
