<?php

class DatabaseConnection {
    private $host;
    private $database;
    private $username;
    private $password;
    private $conn;
	private $botToken;

    public function __construct($dbConfig) {
        if (!isset($dbConfig)) { die("Please provide db config"); }
        $this->host = $dbConfig['db']['host'];
        $this->database = $dbConfig['db']['database'];
        $this->username = $dbConfig['db']['username'];
        $this->password = $dbConfig['db']['password'];
		$this->botToken = $dbConfig['bot_token'];
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
        $this->conn->set_charset("utf8mb4");
        if ($this->conn->connect_error) { die("Database connection error: " . $this->conn->connect_error); }
    }

	// Get the phrase from the table
	public function getPhraseText($phrase_key, $chat_id) {
		$language = $this->getLanguage($chat_id);
		$sql = "SELECT phrase_text FROM phrases_{$language} WHERE phrase_key = ?";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("s", $phrase_key);
		$stmt->execute();
		$result = $stmt->get_result();
		$phrase_text = $result->fetch_assoc()["phrase_text"] ?? null;
		$stmt->close();
		return $phrase_text;
	}
	
	public function getLanguage($id_tg) {
		$sql  = "SELECT select_language FROM users WHERE id_tg = ?";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("s", $id_tg);
		$stmt->execute();
		$result = $stmt->get_result();
		$row    = $result->fetch_assoc();
		$stmt->close();
		return $row["select_language"];
	}

	public function getChatIdByLink($telegram, $chat_id) {
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text'    => 'Function started...'
        ]);
        $link = '@fgjhaksdlf'; // hardcode, fix it!
        $url = "https://api.telegram.org/bot{$this->botToken}/getChat?chat_id={$link}";
        $response = @file_get_contents($url);
        if ($response === false) {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text'    => 'Error when making a request to the Telegram API.'
            ]);
            return null; // Return early in case of error
        }
        $data = json_decode($response, true);
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text'    => 'After request: ' . ($data['result']['id'] ?? 'ID not found!')
        ]);
        // Check the response from the API
        if (isset($data['ok']) && $data['ok']) {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text'    => 'Chat data: ID = ' . $data['result']['id']
            ]);
            return $data['result']['id']; // Return the chat ID
        } else {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text'    => 'Err: ' . ($data['description'] ?? 'Unknown error')
            ]);
            return null; // Return null if unsuccessful
        }
    }

	public function getReferralsCount($chat_id) {
		$sql = "SELECT referals FROM users WHERE id_tg = ?";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("i", $chat_id);
		$stmt->execute();
		$result = $stmt->get_result();
		$referals = $result->fetch_assoc()['referals'] ?? 0.0;
		$stmt->close();
		return $referals;
	}

	public function getUserUsername($chat_id) {
		$sql = "SELECT usernameTg FROM users WHERE id_tg = ?";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("i", $chat_id);
		$stmt->execute();
		$result = $stmt->get_result();
		$username = $result->fetch_assoc()['usernameTg'] ?? null;
		$stmt->close();
		return $username;
	}
	

	public function getUserBalance($id_tg) {
		$sql  = "SELECT balance FROM users WHERE id_tg = ?";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("i", $id_tg);
		$stmt->execute();
		$result = $stmt->get_result();
		$balance = $result->fetch_assoc()['balance'] ?? 0.0;
		$stmt->close();
		return $balance . $GLOBALS['currency'];
	}
	// bot name should be given as param next time
	public function generateReferralLink($chat_id) {
		$bot_name = 'testest0001_bot';
		$referralLink = "https://t.me/{$bot_name}?start=" . urlencode($chat_id); // Aware
		return $referralLink;
	}
	
	public function registerUser($telegram, $chat_id, $id_referal, $balance = 0.0, $role = 'user') {
		$username = $GLOBALS['username1'];
		$status = 'def'; // Value for the status field
		$sql = "INSERT INTO users (usernameTg, role, id_tg, id_referal, balance, referals, status) VALUES (?, ?, ?, ?, ?, 0, ?)";
		$stmt = $this->conn->prepare($sql);
		// Send SQL debug message
		$telegram->sendMessage(['chat_id' => $chat_id, 'text' => 'New user sql: ' . $sql]);
		try {
			// Bind parameters
			$stmt->bind_param("ssiids", $username, $role, $chat_id, $id_referal, $balance, $status);
		} catch (\Exception $e) {
			$telegram->sendMessage(['chat_id' => $chat_id, 'text' => 'Error with new user stmt: ' . $e->getMessage()]);
			return null; // Early return in case of error
		}
		// Send message before execution
		$telegram->sendMessage(['chat_id' => $chat_id, 'text' => 'Before execute']);
		$stmt->execute();
		return $this->conn->insert_id;
	}

	// If there is already such a user
	public function userExists($id_tg) {
		$sql  = "SELECT COUNT(*) as count FROM users WHERE id_tg = ?";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("i", $id_tg);
		$stmt->execute();
		$result = $stmt->get_result();
		$row    = $result->fetch_assoc();
		return $row["count"] > 0;
	}

	// Whether the user is subscribed to the channel
	public function isUserSubscribed($chat_id, $telegram, $bot_token) {
		error_log("isUserSubscribed called for chat_id: $chat_id");
		$channelId = $GLOBALS['ChannelID'];
		if ($channelId === null) {
			$telegram->sendMessage([
				'chat_id' => $chat_id,
				'text'    => "Нет ID канала."
			]);
			return false; // Failed to get channel ID
		}
		$url = "https://api.telegram.org/bot$bot_token/getChatMember?chat_id=$channelId&user_id=$chat_id";
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		if (curl_errno($ch)) {
			error_log('cURL error: ' . curl_error($ch));
			return false; // CURL ERROR
		}
		curl_close($ch);
		$data = json_decode($response, true);
		error_log("API response: " . print_r($data, true));
		// signed or not
		return $data['ok'] && in_array($data['result']['status'], ['member', 'administrator', 'creator']);
	}

	public function incrementBalance($id_tg, $amount) {
		$sql  = "UPDATE users SET balance = balance + ? WHERE id_tg = ?";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("di", $amount, $id_tg);
		$stmt->execute();
		return $stmt->affected_rows > 0;
	}

	public function incrementReferrals($id_tg) {
		$sql  = "UPDATE users SET referals = referals + 1 WHERE id_tg = ?";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("i", $id_tg);
		$stmt->execute();
	}

	public function updateReferralId($id_tg, $id_referal) {
		$sql  = "UPDATE users SET id_referal = ? WHERE id_tg = ?";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("ii", $id_referal, $id_tg);
		$stmt->execute();
	}

	public function updateUserLanguage($userId, $key) {
		$sql  = "UPDATE users SET select_language = ? WHERE id_tg = ?";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("ss", $key, $userId);
		$stmt->execute();
		$stmt->close();
	}

	// Start
	public function handleStartCommand($telegram, $chat_id, $update) {
		// Get referral id from the link parameter
		$referral_id  = null;
		$message_text = $update['message']['text'];
		if (strpos($message_text, '/start') !== false) {
			$arguments = explode(' ', $message_text);
			if (count($arguments) > 1) {
				$referral_id = intval($arguments[1]); // Remove the call to parseRefLink()
			} else {
				// Extract id_referal from /start command arguments
				$matches = [];
				if (preg_match('/start=([0-9a-z]+)/i', $message_text, $matches)) {
					$referral_id = intval($matches[1]); // Remove the call to parseRefLink()
				}
			}
		} elseif (isset($update['message']['entities'])) {
			// Extract the URL from the message using a regular expression
			$url = null;
			foreach ($update['message']['entities'] as $entity) {
				if ($entity['type'] === 'text_link') {
					$url = $entity['url'];
					break;
				}
			}
			if ($url) {
				$matches = [];
				if (preg_match('/start=([0-9a-z]+)/i', $url, $matches)) {
					$referral_id = intval($matches[1]); // Remove the call to parseRefLink()
				}
			}
		}
		$telegram->sendMessage([
			'chat_id' => $chat_id,
			'text'    => 'Chat id: ' . $chat_id,
		]);
		if ($this->userExists($chat_id)) {
			if ($referral_id && $referral_id != $chat_id) {
				$this->updateReferralId($chat_id, $referral_id);
			}
		} else {
			$telegram->sendMessage([
				'chat_id' => $chat_id,
				'text'    => 'Start register new user',
			]);
			$new_user_id = $this->registerUser($telegram, $chat_id, $referral_id ?? 0);
			$telegram->sendMessage([
				'chat_id' => $chat_id,
				'text'    => 'New user id: ' . $new_user_id,
			]);
			if ($referral_id && $referral_id != $chat_id) {
				$message = $this->getPhraseText("bonus_text", $referral_id);
				$this->incrementReferrals($referral_id);
				$this->updateReferralId($referral_id, $new_user_id);
				$this->incrementBalance($referral_id, $GLOBALS['inviteSumValue']);
				$telegram->sendMessage([
					'chat_id' => $referral_id,
					'text'    => $message
				]);
			}
		}
		$reply_markup = $telegram->buildKeyboard(
			array_values($GLOBALS['buttons']),
			$oneTimeKeyboard = true,
			$resizeKeyboard = true
		);
		$content = [
			'chat_id'      => $chat_id,
			'text'         => 'Выберите язык / Select language',
			'reply_markup' => $reply_markup
		];
		$telegram->sendMessage($content);
	}
	// Start message after language selection
	public function handleLanguage($telegram, $chat_id) {
		$message1     = $this->getPhraseText('welcome_message', $chat_id);
		$message      = str_replace(
			[ '{cards}', '{summ}', '{currency}' ], [ $GLOBALS['cards'], $GLOBALS['summ'], $GLOBALS['currency'] ],
			$message1
		);
		$button_text  = $this->getPhraseText('welcome_button', $chat_id);
		$reply_markup = $telegram->buildKeyboard([
			[$button_text]
		], $oneTimeKeyboard = true, $resizeKeyboard = true);
		$content      = [
			'chat_id'      => $chat_id,
			'text'         => $message,
			'reply_markup' => $reply_markup

		];
		$telegram->sendMessage($content);
	}

	public function sendMessageToMultipleUsers($telegram, $chat_id) {
		$user_ids         = $this->TakeAllId($telegram, $chat_id);
		$successful_sends = 0;
		$failed_sends     = 0;
		foreach ($user_ids as $user_id) {
			$message = $this->getPhraseText('button_earn', $user_id);
			$result  = $telegram->sendMessage([
				'chat_id' => $user_id,
				'text'    => $message
			]);
			if (strpos($result, "Err.") !== false) {
				$failed_sends++;
			} else {
				$successful_sends++;
			}
		}
		return "Message successfully sent $successful_sends to users. Failed to send the message $failed_sends to users.";
	}

	public function takeAllId($telegram, $chat_id) {
		try {
			$result = $this->conn->query('SELECT id_tg FROM users');
			$ids = [];
			if ($result->num_rows > 0) {
				while ($row = $result->fetch_assoc()) {
					$id_tg = $row['id_tg'];
					if (strpos($id_tg, '-100') !== 0) {
						$ids[] = $id_tg;
					}
				}
				$message = count($ids) > 0 
					? "Telegram user IDs:\n\n" . implode(",\n", $ids) 
					: "There are no users in the database.";
			} else {
				$message = "There are no users in the database.";
			}
			$telegram->sendMessage(['chat_id' => $chat_id, 'text' => $message]);
			return $ids;
		} catch (mysqli_sql_exception $e) {
			$telegram->sendMessage(['chat_id' => $chat_id, 'text' => "Error while executing the query: " . $e->getMessage()]);
			return [];
		}
	}

	public function isAdmin($telegram, $chat_id) {
		$stmt = $this->conn->prepare("SELECT role FROM users WHERE id_tg = ?");
		$stmt->bind_param("s", $chat_id);
		$stmt->execute();
		$result = $stmt->get_result();
		return $result->fetch_assoc()['role'] === 'admin';
	}
	//Главное меню
	public function handleMainMenu($telegram, $chat_id) {
		$isAdmin = $this->isAdmin($telegram, $chat_id);
		$message = $isAdmin 
			? '👤Admin панель👤' . PHP_EOL . PHP_EOL . PHP_EOL . $this->getPhraseText('main_menu', $chat_id)
			: $this->getPhraseText('main_menu', $chat_id);
		$buttons = [
			[
				$this->getPhraseText('button_earn', $chat_id),
				$this->getPhraseText('button_partners', $chat_id),
				$this->getPhraseText('button_balance', $chat_id)
			],
			[
				$this->getPhraseText('button_help', $chat_id),
				$this->getPhraseText('button_changeLang', $chat_id)
			]
		];
		if ($isAdmin) {
			$buttons[] = [
				"Рассылка",
				"Админ кнопка",
				"Админ кнопка"
			];
		}
		$reply_markup = $telegram->buildKeyboard($buttons, false, true, true);
		$content = [
			'chat_id'      => $chat_id,
			'text'         => $message,
			'reply_markup' => $reply_markup
		];
		$telegram->sendMessage($content);
	}	

	public function checkTrue($telegram, $chat_id, $bot_token, $message_id) {
		$balance = $this->getUserBalance($chat_id);
		$referals = $this->getReferralsCount($chat_id);
		$joined = $this->isUserSubscribed($chat_id, $telegram, $bot_token);
		$conditions = $this->getPhraseText("conditions_text", $chat_id);
		$content = [
			'chat_id' => $chat_id,
			'message_id' => $message_id,
		];
		if (!$joined) {
			$notSub = $this->getPhraseText("doesntSub_text", $chat_id);
			$keyboard = [
				'inline_keyboard' => [
					[['text' => $this->getPhraseText('subscribe_button', $chat_id), 'url' => $GLOBALS['offTgChannel']]],
					[['text' => $this->getPhraseText('checkChannel_button', $chat_id), 'callback_data' => 'checkSub']]
				]
			];
			$content['text'] = $notSub;
			$content['reply_markup'] = json_encode($keyboard);
		} else {
			$joinedTG = $this->getPhraseText("joined_text", $chat_id);
			$message = str_replace(
				['{summ}', '{currency}', '{inviteSumValue}', '{referals}', '{joined}'],
				[$GLOBALS['summ'], $GLOBALS['currency'], $GLOBALS['inviteSumValue'], $referals, $joinedTG],
				$conditions
			);
			if ($referals >= $GLOBALS['inviteSumValue']) {
				$getGiftButton = str_replace(
					['{summ}', '{currency}'],
					[$GLOBALS['summ'], $GLOBALS['currency']],
					$this->getPhraseText("getGift_button", $chat_id)
				);
				$keyboard = [['text' => $getGiftButton, 'callback_data' => 'withdraw']];
			} else {
				$mustBe = $GLOBALS['inviteSumValue'] - $referals;
				$remindFriendBTN = str_replace(
					['{remained}'],
					[$mustBe],
					$this->getPhraseText("inviteFriends_button", $chat_id)
				);
				$keyboard = [['text' => $remindFriendBTN, 'callback_data' => 'invite_friend']];
			}
			$content['text'] = $message;
			$content['reply_markup'] = json_encode(['inline_keyboard' => [$keyboard]]);
		}
		$telegram->editMessageText($content);
	}

	// To invite a friend
	public function handlePartnerCommand($telegram, $chat_id) {
		$ref_link = $this->generateReferralLink($chat_id);
		$referal = $this->getReferralsCount($chat_id);
		$balance = $GLOBALS['bonus'] * $referal . $GLOBALS['currency'];
		$message = str_replace(
			['{$ref_link}', '{$bonus}', '{$referal}', '{$balance}'],
			[$ref_link, $GLOBALS['bonus'], $referal, $balance],
			$this->getPhraseText("partner_text", $chat_id)
		);
		$telegram->sendMessage([
			'chat_id' => $chat_id,
			'text'    => $message,
		]);
	}
	
	// Account
	public function handleBalanceCommand($telegram, $chat_id) {
		$balance = $this->getUserBalance($chat_id);
		$message = str_replace(
			['{$balance}', '{$minWithdraw}'],
			[$balance, $GLOBALS['minWithdraw']],
			$this->getPhraseText("balance_text", $chat_id)
		);
		$keyboard = json_encode([
			'inline_keyboard' => [[
				['text' => $this->getPhraseText("withdraw_text", $chat_id), 'callback_data' => 'withdraw']
			]]
		]);
		$telegram->sendMessage([
			'chat_id' => $chat_id,
			'text'    => $message,
			'reply_markup' => $keyboard,
		]);
	}	

	// Withdrawal
	public function handleWithdrawCommand($telegram, $chat_id, $message_id) {
		if ($this->getReferralsCount($chat_id) < 3) {
			$message = $this->getPhraseText('erWithdraw_text', $chat_id);
		} else {
			$keyboard = json_encode([
				'inline_keyboard' => [
					[['text' => "Kaspi", 'callback_data' => 'kspi']],
					[['text' => "Банковска карта", 'callback_data' => 'card']]
				]
			]);
			$message = 'Вывод доступен';
		}
		$telegram->editMessageText([
			'chat_id'    => $chat_id,
			'message_id' => $message_id,
			'text'       => $message,
			'reply_markup' => isset($keyboard) ? $keyboard : null
		]);
	}
	
	public function handleDwnloadCommand($telegram, $chat_id) {
		$lang = $this->getLanguage($chat_id);
		$message = $this->getPhraseText("download_button", $chat_id);
		$keyboard = json_encode([
			'inline_keyboard' => [
				[[
					'text' => $this->getPhraseText("dwnldApp_button", $chat_id),
					'url'  => "https://apps.apple.com/{$lang}/app/shein-shopping-online/id878577184"
				]],
				[[
					'text' => $this->getPhraseText("dwnldGoogle_button", $chat_id),
					'url'  => "https://play.google.com/store/apps/details?id=com.zzkko&hl={$lang}"
				]]
			]
		]);
		$telegram->sendMessage([
			'chat_id'      => $chat_id,
			'text'         => $message,
			'reply_markup' => $keyboard
		]);
	}	

	public function handleHelpCommand($telegram, $chat_id) {
		$message = str_replace(
			['{summ}', '{currency}'],
			[$GLOBALS['summ'], $GLOBALS['currency']],
			$this->getPhraseText("help_text", $chat_id)
		);
		$telegram->sendMessage([
			'chat_id'      => $chat_id,
			'text'         => $message,
			'reply_markup' => json_encode([
				'inline_keyboard' => [[
					['text' => $this->getPhraseText("button_report", $chat_id), 'callback_data' => 'rep_ru']
				]]
			])
		]);
	}
	
	// Earn
	public function handleEarnCommand($telegram, $chat_id) {
		$message = $this->getPhraseText("income_text", $chat_id);
		$options = [
			'invite_friend' => 'invite_friend',
			'join_channel'  => 'join_channel',
			'view_post'     => 'view_post'
		];
		$keyboard = [
			'inline_keyboard' => array_map(function($key) use ($chat_id) {
				return [[
					'text' => str_replace('{$' . $key . '}', $GLOBALS[$key . 'Value'], $this->getPhraseText($key, $chat_id)),
					'callback_data' => $key
				]];
			}, array_keys($options))
		];
		$telegram->sendMessage([
			'chat_id'      => $chat_id,
			'text'         => $message,
			'reply_markup' => json_encode($keyboard)
		]);
	}

	// Subscription check + charge to balance
	public function handleSubscribeCheckCommand($chat_id, $telegram, $bot_token, $message_id) {
		$subscribed = $this->isUserSubscribed($chat_id, $telegram, $bot_token);
		$tg_key = 'tg' . $GLOBALS['valueTg'];
		$channelURL = $this->getURL($tg_key);
		$skipButton = $this->getPhraseText("skipChannel_button", $chat_id);
		if ($subscribed) {
			$handleMessage = $this->getPhraseText("approveSubscribe_text", $chat_id);
			$message = str_replace('{$joinChannelPay}', $GLOBALS['joinChannelPay'], $handleMessage);
			$this->incrementBalance($chat_id, 9); // Update balance
			$keyboard = [['text' => $skipButton, 'callback_data' => 'skip']];
		} else {
			$handleMessage = $this->getPhraseText("notSunscribe_text", $chat_id);
			$message = str_replace('{$channelURL}', $channelURL, $handleMessage);
			$checkButton = $this->getPhraseText("checkChannel_button", $chat_id);
			$keyboard = [
				['text' => $checkButton, 'callback_data' => 'check'],
				[$skipButton, 'callback_data' => 'skip']
			];
		}
		$content = [
			'chat_id' => $chat_id,
			'message_id' => $message_id,
			'text' => $message,
			'reply_markup' => json_encode(['inline_keyboard' => [$keyboard]])
		];
		$telegram->editMessageText($content);
	}

	public function handleJoinChannelCommand($telegram, $chat_id, $message_id) {
		$tg_key = 'tg' . $GLOBALS['valueTg'];
		$channelURL = $this->getURL($tg_key);
		$handleMessage = $this->getPhraseText("join_text", $chat_id);
		$message = str_replace(
			['{$sum}', '{$chanURL}'],
			[$GLOBALS['joinChannelPay'], $channelURL],
			$handleMessage
		);
		$keyboard = json_encode([
			'inline_keyboard' => [
				[
					['text' => $this->getPhraseText("checkChannel_button", $chat_id), 'callback_data' => 'check']
				],
				[
					['text' => $this->getPhraseText("skipChannel_button", $chat_id), 'callback_data' => 'skip']
				]
			]
		]);
		$content = [
			'chat_id' => $chat_id,
			'message_id' => $message_id,
			'text' => $message,
			'reply_markup' => $keyboard
		];
		try {
			$telegram->editMessageText($content);
		} catch (Exception $e) {
			error_log('Ошибка при редактировании сообщения: ' . $e->getMessage());
		}
	}

	public function handleReportCommand($telegram, $chat_id, $message_id) {
		$message = $this->getPhraseText("rep_text", $chat_id);
		$buttons = ['spam', 'fraud', 'violence', 'copyright', 'other'];
		$keyboard = [
			'inline_keyboard' => array_map(function($type) use ($chat_id) {
				return [[
					'text' => $this->getPhraseText("{$type}_button", $chat_id),
					'callback_data' => $type
				]];
			}, $buttons)
		];
		$content = [
			'chat_id' => $chat_id,
			'message_id' => $message_id,
			'text' => $message,
			'reply_markup' => json_encode($keyboard)
		];
		$telegram->editMessageText($content);
	}
	// Spam
	public function handleSpamCommand($telegram, $chat_id, $message_id) {
		$message = $this->getPhraseText("report_text", $chat_id);
		$keyboard = json_encode([
			'inline_keyboard' => [[
				['text' => $this->getPhraseText("yes_button", $chat_id), 'callback_data' => '(spam)'],
				['text' => $this->getPhraseText("no_button", $chat_id), 'callback_data' => 'no']
			]]
		]);
		$telegram->editMessageText([
			'chat_id' => $chat_id,
			'message_id' => $message_id,
			'text' => $message,
			'reply_markup' => $keyboard
		]);
	}

	public function handleFraudCommand($telegram, $chat_id, $message_id) {
		$message = $this->getPhraseText("report_text", $chat_id);
		$keyboard = json_encode([
			'inline_keyboard' => [[
				['text' => $this->getPhraseText("yes_button", $chat_id), 'callback_data' => '(fraud)'],
				['text' => $this->getPhraseText("no_button", $chat_id), 'callback_data' => 'no']
			]]
		]);
		$telegram->editMessageText([
			'chat_id' => $chat_id,
			'message_id' => $message_id,
			'text' => $message,
			'reply_markup' => $keyboard
		]);
	}
	
	public function handleViolenceCommand($telegram, $chat_id, $message_id) {
		$message = $this->getPhraseText("report_text", $chat_id);
		$keyboard = json_encode([
			'inline_keyboard' => [[
				['text' => $this->getPhraseText("yes_button", $chat_id), 'callback_data' => '(violence)'],
				['text' => $this->getPhraseText("no_button", $chat_id), 'callback_data' => 'no']
			]]
		]);
	
		$telegram->editMessageText([
			'chat_id' => $chat_id,
			'message_id' => $message_id,
			'text' => $message,
			'reply_markup' => $keyboard
		]);
	}

	public function handleCopyrightCommand($telegram, $chat_id, $message_id) {
		$message = $this->getPhraseText("report_text", $chat_id);
		$keyboard = json_encode([
			'inline_keyboard' => [[
				['text' => $this->getPhraseText("yes_button", $chat_id), 'callback_data' => '(copyright)'],
				['text' => $this->getPhraseText("no_button", $chat_id), 'callback_data' => 'no']
			]]
		]);
		$telegram->editMessageText([
			'chat_id' => $chat_id,
			'message_id' => $message_id,
			'text' => $message,
			'reply_markup' => $keyboard
		]);
	}
	// Other
	public function handleOtherCommand($telegram, $chat_id, $message_id) {
		$message = $this->getPhraseText("report_text", $chat_id);
		$keyboard = json_encode([
			'inline_keyboard' => [[
				['text' => $this->getPhraseText("yes_button", $chat_id), 'callback_data' => '(other)'],
				['text' => $this->getPhraseText("no_button", $chat_id), 'callback_data' => 'no']
			]]
		]);
		$telegram->editMessageText([
			'chat_id' => $chat_id,
			'message_id' => $message_id,
			'text' => $message,
			'reply_markup' => $keyboard
		]);
	}
	// Accept
	public function handleApprovCommand($telegram, $chat_id, $message_id, $callback_data) {
		$rep = $this->getPhraseText("approved_text", $chat_id);
		$username = $this->getUserUsername($chat_id);
		$telegram->editMessageText([
			'chat_id' => $chat_id,
			'message_id' => $message_id,
			'text' => $rep,
			'reply_markup' => json_encode([
				'inline_keyboard' => [[
					['text' => $this->getPhraseText("writeAdmin_text", $chat_id), 'url' => $GLOBALS['adminHREF']]
				]]
			])
		]);
		
		$telegram->sendMessage([
			'chat_id' => $GLOBALS['adminID'],
			'text' => 'Жалоба пользователя @' . $username . ' ' . $callback_data
		]);
	}
	// Decline
	public function handleCanceledCommand($telegram, $chat_id, $message_id) {
		$telegram->editMessageText([
			'chat_id' => $chat_id,
			'message_id' => $message_id,
			'text' => $this->getPhraseText("canceled_text", $chat_id)
		]);
	}

	public function getURL($tg_key) {
		$sql = "SELECT tg_url FROM channel_tg WHERE tg_key = ?";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("s", $tg_key);
		$stmt->execute();
		$stmt->store_result();
		if ($stmt->num_rows > 0) {
			$stmt->bind_result($tg_url);
			$stmt->fetch();
			$stmt->close();
			return urldecode($tg_url);
		}
		$stmt->close();
		return null;
	}
	
	public function adminModeRas($chat_id, $telegram) {
		$telegram->sendMessage([
			'chat_id'      => $chat_id,
			'text'         => 'Вы вошли в рассылку',
			'reply_markup' => $telegram->buildKeyboard([
				[['Добавить текст', 'Обзор', 'Главное меню']]
			], false, true, true)
		]);
	}

	public function isInputMode($chat_id) {
		$stmt = $this->conn->prepare('SELECT status FROM users WHERE id_tg = ?');
		if (!$stmt) return 'error';
		$stmt->bind_param("i", $chat_id);
		if (!$stmt->execute()) return 'error';
		$result = $stmt->get_result();
		return $result->num_rows > 0 ? $result->fetch_assoc()['status'] : 'def';
	}

	public function setInputMode($chat_id, $mode) {
		$sql  = "UPDATE users SET status = ? WHERE id_tg = ?"; // id_tg
		$stmt = $this->conn->prepare($sql);
		if ($stmt === false) return false;
		$stmt->bind_param("si", $mode, $chat_id);
		if (!$stmt->execute()) return false;
		return true;
	}

	public function saveUserText($chat_id, $telegram, $text) {
		$telegram->sendMessage(['chat_id' => $chat_id, 'text' => "Вы ввели: " . $text]);
		$stmt = $this->conn->prepare("INSERT INTO mailing (message_text) VALUES (?)");
		return $stmt && $stmt->bind_param("s", $text) && $stmt->execute();
	}
	
	public function handleUserInput($chatId, $telegram) {
		$telegram->sendMessage([
			'chat_id' => $chatId,
			'text'    => "Вы вошли в режим ввода текста!\nПожалуйста, введите ваш текст."
		]);
		$this->setInputMode($chatId, 'input_mode');
	}
	
	public function __destruct() {
		$this->conn && $this->conn->close();
	}
	
}