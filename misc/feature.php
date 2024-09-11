/* public function handleSubscription($telegram, $chat_id) {
		// Получаем активные каналы из БД
		$channels = $this->getActiveChannels(); // Функция, которая возвращает активные каналы

		// Проверка текущего канала
		$current_channel_index = $GLOBALS['currentChannelIndex'] ?? 0;

		// Если нет активных каналов, уведомляем пользователя
		if (empty($channels)) {
			$telegram->sendMessage([
				'chat_id' => $chat_id,
				'text' => "Нет активных каналов для подписки."
			]);
			return;
		}

		// Получаем текущий канал
		$current_channel = $channels[$current_channel_index];

		// Формируем сообщение
		$message = "Подписка на канал: {$current_channel['channel_url']}\n";
		$message .= "Пожалуйста, нажмите кнопку для подписки или пропустите.";

		// Создаем клавиатуру
		$keyboard = [
			'inline_keyboard' => [
				[
					['text' => 'Подписаться', 'callback_data' => 'subscribe_' . $current_channel['id']],
					['text' => 'Пропустить', 'callback_data' => 'skip']
				],
				[
					['text' => 'Проверить подписку', 'callback_data' => 'check_' . $current_channel['id']]
				]
			]
		];

		$content = [
			'chat_id' => $chat_id,
			'text' => $message,
			'reply_markup' => json_encode($keyboard)
		];

		// Отправляем сообщение пользователю
		try {
			$telegram->sendMessage($content);
		} catch (Exception $e) {
			error_log('Ошибка при отправке сообщения: ' . $e->getMessage());
		}
	} */

// Функция для получения активных каналов
	/* private function getActiveChannels() {
		$sql = "SELECT * FROM channels WHERE status = 'active'";
		$result = $this->conn->query($sql);
		return $result->fetch_all(MYSQLI_ASSOC);
	}
	public function subscribeCallbackQuery($chat_id, $telegram){
			$GLOBALS['currentChannelIndex']++;
			$this->handleSubscription($telegram, $chat_id);}
	}
	public function handleCallbackQuery($telegram, $callback_query) {
		// Обработка действий пользователя
		if (strpos($data, 'subscribe_') === 0) {
			$channel_id = substr($data, 9);
			$this->subscribeUser($chat_id, $channel_id); // Функция для подписки
			$telegram->sendMessage([
				'chat_id' => $chat_id,
				'text' => "Вы подписаны на канал."
			]);
		} elseif ($data === 'skip') {
			// Логика пропуска канала
			$GLOBALS['currentChannelIndex']++;
			$this->handleSubscription($telegram, $chat_id);
		} elseif (strpos($data, 'check_') === 0) {
			$channel_id = substr($data, 6);
			$is_subscribed = $this->checkSubscription($chat_id, $channel_id); // Проверка подписки
			$message = $is_subscribed ? "Вы подписаны на этот канал." : "Вы не подписаны на этот канал.";
			$telegram->sendMessage([
				'chat_id' => $chat_id,
				'text' => $message
			]);
		}
	}

	// Функция для получения активных каналов
	private function getActiveChannels() {
		$sql = "SELECT * FROM channels WHERE status = 'active'";
		$result = $this->conn->query($sql);
		return $result->fetch_all(MYSQLI_ASSOC);
	} */

	/* // Функция для подписки пользователя
	private function subscribeUser($user_id, $channel_id) {
		$sql = "INSERT INTO subscriptions (user_id, channel_id, subscribed) VALUES (?, ?, 1)
				ON DUPLICATE KEY UPDATE subscribed = 1";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("si", $user_id, $channel_id);
		$stmt->execute();
	}

	// Функция для проверки подписки
	private function checkSubscription($user_id, $channel_id) {
		$sql = "SELECT subscribed FROM subscriptions WHERE user_id = ? AND channel_id = ?";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("si", $user_id, $channel_id);
		$stmt->execute();
		$result = $stmt->get_result();
		return $result->num_rows > 0 && $result->fetch_assoc()['subscribed'];
	} */

