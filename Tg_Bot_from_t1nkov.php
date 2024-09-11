<?php
include 'Telegram_Class_Hand.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = require 'config.php';

$bot_token        = $config['bot_token'];
$telegram         = new Telegram($bot_token);
$GLOBALS['TOKEN'] = $bot_token;
$text                         = $telegram->Text();
$chat_id                      = $telegram->ChatID();
$data                         = $telegram->getData();
$GLOBALS['adminHREF']         = 'https://t.me/t1nkov';
$GLOBALS['summ']              = 500; // gift card amount
$GLOBALS['inviteSumValue']    = 200; // how many people to invite to get the card
$GLOBALS['offTgChannel']      = 'https://t.me/fgjhaksdlf';
$GLOBALS['cards']             = 10; // cards amount
$GLOBALS['ChannelID']         = 2248476665;
$GLOBALS['subscribeSumValue'] = 1000 . $GLOBALS['currency'];
$GLOBALS['watchSumValue']     = 8;
$GLOBALS['valueTg']           = 1;
$GLOBALS['currency']            = 'INR';
$GLOBALS['joinChannelPay']    = 1000 . $GLOBALS['currency'];
$GLOBALS['minWithdraw']       = number_format(10, 2, '.', '') . $GLOBALS['currency'];
$GLOBALS['bonus']             = 10 . $GLOBALS['currency'];
$GLOBALS['buttons']           = [
    "ru" => ["Русский"],
    "en" => ["English"],
    "kz" => ["Қазақша"]
];

$update               = json_decode(file_get_contents('php://input'), true);
$callback_data        = $update['callback_query']['data'];
$message_id           = $update['callback_query']['message']['message_id'];
$GLOBALS['username1'] = $data['message']['from']['username'];

class DatabaseConnection {
    private $host;
    private $database;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        global $config;
        $this->host = $config['db']['host'];
        $this->database = $config['db']['database'];
        $this->username = $config['db']['username'];
        $this->password = $config['db']['password'];
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
        $this->conn->set_charset("utf8mb4");
        if ($this->conn->connect_error) {
            die("Database connection error: " . $this->conn->connect_error);
        }
    }

	//Получение фразы из таблицы
	public function getPhraseText($phrase_key, $chat_id) {
		$language = $this->getLanguage($chat_id);
		$sql      = "SELECT phrase_text FROM phrases_{$language} WHERE phrase_key = ?";
		$stmt     = $this->conn->prepare($sql);
		$stmt->bind_param("s", $phrase_key);
		$stmt->execute();
		$result = $stmt->get_result();

		if ($result->num_rows > 0) {
			$row         = $result->fetch_assoc();
			$phrase_text = $row["phrase_text"];
		} else {
			$phrase_text = null; // Если фраза не найдена, возвращаем null
		}

		$stmt->close();
		return $phrase_text;
	}

//Получение языка из таблицы пользователя
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

//Получение chatId канала
	public function getChatIdByLink($telegram, $bot_token, $chat_id) {
		$telegram->sendMessage([
			'chat_id' => $chat_id,
			'text'    => 'функция началась'
		]);
		/* 	$link = $GLOBALS['offTgChannel'];
			 $chatLink = str_replace("https://t.me/", "@", $link); */
		$url = "https://api.telegram.org/bot7281054427:AAEKER8d_p6LHtCZNamVIsehbAZnHI2KF_M/getChat?chat_id=@fgjhaksdlf";


		$response = @file_get_contents($url);
		$data     = json_decode($response, true);

		$telegram->sendMessage([
			'chat_id' => $chat_id,
			'text'    => 'после запроса ' . $data['result']['id']
		]);
		if ($data['ok']) {
			$telegram->sendMessage([
				'chat_id' => $chat_id,
				'text'    => 'блок иф ' . $data['result']['id']
			]);
			$chatId = $data['result']['id'];

			return $chatId;
		} else {

			return null;
		}
	}

//Получение кол-ва приглашенных пользователей
	public function getReferralsCount($chat_id) {
		$sql  = "SELECT referals FROM users WHERE id_tg = ?";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("i", $chat_id);
		$stmt->execute();
		$result = $stmt->get_result();
		if ($result->num_rows > 0) {
			$row      = $result->fetch_assoc();
			$referals = $row["referals"];
		} else {
			$referals = 0.0;
		}

		$stmt->close();
		return $referals;
	}

//Получение тг username
	public function getUserUsername($chat_id) {
		$sql  = "SELECT usernameTg FROM users WHERE id_tg = ?";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("i", $chat_id);
		$stmt->execute();
		$result = $stmt->get_result();

		if ($result->num_rows > 0) {
			$row      = $result->fetch_assoc();
			$username = $row["usernameTg"];
		} else {
			$username = null;
		}

		$stmt->close();
		return $username;
	}

//Получение 	а
	public function getUserBalance($id_tg) {
		$sql  = "SELECT balance FROM users WHERE id_tg = ?";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("i", $id_tg);
		$stmt->execute();
		$result = $stmt->get_result();
		if ($result->num_rows > 0) {
			$row     = $result->fetch_assoc();
			$balance = $row["balance"];
		} else {
			$balance = 0.0;
		}

		$stmt->close();
		return $balance . $GLOBALS['currency'];
	}

//Создание реф ссылки
	public function generateReferralLink($chat_id) {
		$referralLink = "https://t.me/testest0001_bot?start=" . urlencode($chat_id);
		return $referralLink;
	}

//Регистрация 
	public function registerUser($telegram, $chat_id, $id_referal, $balance = 0.0, $role = 'user') {
		$username = $GLOBALS['username1'];

		// Измените SQL-запрос, чтобы включить поле status
		$sql  = "INSERT INTO users (usernameTg, role, id_tg, id_referal, balance, referals, status) VALUES (?, ?, ?, ?, ?, 0, ?)";
		$stmt = $this->conn->prepare($sql);

		$telegram->sendMessage([
			'chat_id' => $chat_id,
			'text'    => 'New user sql: ' . $sql,
		]);

		try {
			// Привязываем параметры, добавляя status
			$status = 'def'; // Значение для поля status
			$stmt->bind_param("ssiids", $username, $role, $chat_id, $id_referal, $balance, $status);
		} catch (\Exception $e) {
			$telegram->sendMessage([
				'chat_id' => $chat_id,
				'text'    => 'Error with new user stmt: ' . json_encode($stmt),
			]);
		}

		$telegram->sendMessage([
			'chat_id' => $chat_id,
			'text'    => 'Before execute',
		]);

		$stmt->execute();

		return $this->conn->insert_id;
	}

//Есть ли уже такой пользователь
	public function userExists($id_tg) {
		$sql  = "SELECT COUNT(*) as count FROM users WHERE id_tg = ?";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("i", $id_tg);
		$stmt->execute();
		$result = $stmt->get_result();
		$row    = $result->fetch_assoc();

		return $row["count"] > 0;
	}

// Подписан ли пользователь на канал
	public function isUserSubscribed($chat_id, $telegram, $bot_token) {
		error_log("isUserSubscribed called for chat_id: $chat_id");

		$channelId = $GLOBALS['ChannelID'];
		if ($channelId === null) {
			$telegram->sendMessage([
				'chat_id' => $chat_id,
				'text'    => "Нет ID канала."
			]);
			return false; // Не удалось получить ID канала
		}

		$url = "https://api.telegram.org/bot$bot_token/getChatMember?chat_id=$channelId&user_id=$chat_id";

		// Используем cURL
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);

		if (curl_errno($ch)) {
			error_log('cURL error: ' . curl_error($ch));
			return false; // Ошибка cURL
		}

		curl_close($ch);
		$data = json_decode($response, true);

		// Логирование ответа
		error_log("API response: " . print_r($data, true));

		if ($data['ok'] && in_array($data['result']['status'], [
				'member',
				'administrator',
				'creator'
			])) {
			return true; // Пользователь подписан
		} else {
			return false; // Пользователь не подписан
		}
	}

//Инкремент баланса	
	public function incrementBalance($id_tg, $amount) {
		$sql  = "UPDATE users SET balance = balance + ? WHERE id_tg = ?";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("di", $amount, $id_tg);
		$stmt->execute();

		if ($stmt->affected_rows > 0) {
			return true;
		} else {
			return false;
		}
	}

//Инкремент рефералов	
	public function incrementReferrals($id_tg) {
		$sql  = "UPDATE users SET referals = referals + 1 WHERE id_tg = ?";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("i", $id_tg);
		$stmt->execute();
	}

//Обеовление реф ссылки
	public function updateReferralId($id_tg, $id_referal) {
		$sql  = "UPDATE users SET id_referal = ? WHERE id_tg = ?";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("ii", $id_referal, $id_tg);
		$stmt->execute();
	}

//Обновление языка
	public function updateUserLanguage($userId, $key) {
		$sql  = "UPDATE users SET select_language = ? WHERE id_tg = ?";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("ss", $key, $userId);
		$stmt->execute();
		$stmt->close();
	}

//Старт
	public function handleStartCommand($telegram, $chat_id, $update) {

		// Получаем id реферала из параметра ссылки
		$referral_id  = null;
		$message_text = $update['message']['text'];
		if (strpos($message_text, '/start') !== false) {
			$arguments = explode(' ', $message_text);
			if (count($arguments) > 1) {
				$referral_id = intval($arguments[1]); // Удаляем вызов parseRefLink()
			} else {
				// Извлекаем id_referal из аргументов команды /start
				$matches = [];
				if (preg_match('/start=([0-9a-z]+)/i', $message_text, $matches)) {
					$referral_id = intval($matches[1]); // Удаляем вызов parseRefLink()
				}
			}
		} elseif (isset($update['message']['entities'])) {
			// Извлекаем URL из сообщения с помощью регулярного выражения
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
					$referral_id = intval($matches[1]); // Удаляем вызов parseRefLink()
				}
			}
		}
		$telegram->sendMessage([
			'chat_id' => $chat_id,
			'text'    => 'Chat id: ' . $chat_id,
		]);
		// Проверяем, если пользователь уже существует
		if ($this->userExists($chat_id)) {
			// Обновляем id_referal, если он был передан
			if ($referral_id && $referral_id != $chat_id) {
				$this->updateReferralId($chat_id, $referral_id);
			}
		} else {
			$telegram->sendMessage([
				'chat_id' => $chat_id,
				'text'    => 'Start register new user',
			]);

			// Регистрируем нового пользователя, передавая id_referal, если он был передан
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

//Стартовое сообщение после выбора языка
	public function handleLanguage($telegram, $chat_id) {


		$message1     = $this->getPhraseText('welcome_message', $chat_id);
		$message      = str_replace(
			[
				'{cards}',
				'{summ}',
				'{currency}'
			],
			[
				$GLOBALS['cards'],
				$GLOBALS['summ'],
				$GLOBALS['currency']
			],
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
			if (strpos($result, "Ошибка") !== false) {
				$failed_sends++;
			} else {
				$successful_sends++;
			}
		}

		return "Сообщение успешно отправлено $successful_sends пользователям. Не удалось отправить сообщение $failed_sends пользователям.";
	}

	public function takeAllId($telegram, $chat_id) {
		try {
			$sql    = 'SELECT id_tg FROM users';
			$result = $this->conn->query($sql);

			$ids = [];
			if ($result->num_rows > 0) {
				while ($row = $result->fetch_assoc()) {
					$id_tg = $row['id_tg'];
					if (strpos($id_tg, '-100') !== 0) {
						$ids[] = $id_tg;
					}
				}

				if (count($ids) > 0) {
					$message = "ID пользователей Telegram:\n\n" . implode(",\n", $ids);
					$telegram->sendMessage([
						'chat_id' => $chat_id,
						'text'    => $message
					]);
				} else {
					$telegram->sendMessage([
						'chat_id' => $chat_id,
						'text'    => "Нет пользователей в базе данных."
					]);
				}
			} else {
				$telegram->sendMessage([
					'chat_id' => $chat_id,
					'text'    => "Нет пользователей в базе данных."
				]);
			}
			return $ids;
		} catch (mysqli_sql_exception $e) {
			$telegram->sendMessage([
				'chat_id' => $chat_id,
				'text'    => "Ошибка при выполнении запроса: " . $e->getMessage()
			]);
			return [];
		}
	}

	public function isAdmin($telegram, $chat_id) {
		$sql  = "SELECT role FROM users WHERE id_tg = ?";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("s", $chat_id);
		$stmt->execute();
		$result = $stmt->get_result();
		$row    = $result->fetch_assoc();

		if ($row['role'] === 'admin') {
			return true;
		} else {
			return false;
		}
	}


//Главное меню
	public function handleMainMenu($telegram, $chat_id) {
		$role = $this->isAdmin($telegram, $chat_id);

		if ($role) {
			$message = '👤Admin панель👤'
			           . PHP_EOL
			           . PHP_EOL
			           . PHP_EOL
			           . $this->getPhraseText('main_menu', $chat_id);

			$reply_markup = $telegram->buildKeyboard([
				[
					$this->getPhraseText('button_earn', $chat_id),
					$this->getPhraseText('button_partners', $chat_id),
					$this->getPhraseText('download_button', $chat_id)
				],
				[
					$this->getPhraseText('button_help', $chat_id),
					$this->getPhraseText('button_changeLang', $chat_id),
					$this->getPhraseText('button_balance', $chat_id)
				],
				[
					"Рассылка",
					"Админ кнопка",
					"Админ кнопка"
				],
			], $oneTimeKeyboard = false, $resizeKeyboard = true, $selective = true);
		} else {
			$message = $this->getPhraseText('main_menu', $chat_id);

			$reply_markup = $telegram->buildKeyboard([
				[
					$this->getPhraseText('button_earn', $chat_id),
					$this->getPhraseText('button_partners', $chat_id),
					$this->getPhraseText('button_balance', $chat_id)
				],
				[
					$this->getPhraseText('button_help', $chat_id),
					$this->getPhraseText('button_changeLang', $chat_id)
				],
			], $oneTimeKeyboard = false, $resizeKeyboard = true, $selective = true);
		}


		$content = [
			'chat_id'      => $chat_id,
			'text'         => $message,
			'reply_markup' => $reply_markup
		];

		$telegram->sendMessage($content);
	}

	public function checkTrue($telegram, $chat_id, $bot_token, $message_id) {
		$balance    = $this->getUserBalance($chat_id);
		$referals   = $this->getReferralsCount($chat_id);
		$joined     = $this->isUserSubscribed($chat_id, $telegram, $bot_token);
		$conditions = $this->getPhraseText("conditions_text", $chat_id);

		if (!$joined) {
			$notSub       = $this->getPhraseText("doesntSub_text", $chat_id);
			$checkChannel = $this->getPhraseText('checkChannel_button', $chat_id);

			$subscribe = $this->getPhraseText('subscribe_button', $chat_id);


			$keyboard = [
				'inline_keyboard' => [
					[
						[
							'text' => $subscribe,
							'url'  => $GLOBALS['offTgChannel']
						]
					],
					[
						[
							'text'          => $checkChannel,
							'callback_data' => 'checkSub'
						]
					]
				]
			];
			$content  = [
				'chat_id'      => $chat_id,
				'message_id'   => $message_id,
				'text'         => $notSub,
				'reply_markup' => json_encode($keyboard)
			];
			$telegram->editMessageText($content);
		} elseif ($joined) {

			if ($referals > $GLOBALS['inviteSumValue'] - 1) {

				$joinedTG      = $this->getPhraseText("joined_text ", $chat_id);
				$message       = str_replace(
					[
						'{summ}',
						'{currency}',
						'{inviteSumValue}',
						'{referals}',
						'{joined}'
					],
					[
						$GLOBALS['summ'],
						$GLOBALS['currency'],
						$GLOBALS['inviteSumValue'],
						$referals,
						$joinedTG
					],
					$conditions
				);
				$getGift       = $this->getPhraseText("getGift_button", $chat_id);
				$getGiftButton = str_replace(
					[
						'{summ}',
						'{currency}'
					],
					[
						$GLOBALS['summ'],
						$GLOBALS['currency']
					],
					$getGift
				);
				$keyboard      = [
					'inline_keyboard' => [
						[
							[
								'text'          => $getGiftButton,
								'callback_data' => 'withdraw'
							]
						]
					]
				];
				$content       = [
					'chat_id'      => $chat_id,
					'text'         => $message,
					'message_id'   => $message_id,
					'reply_markup' => json_encode($keyboard)
				];
			} else {
				$joinedTG        = $this->getPhraseText("joined_text ", $chat_id);
				$message         = str_replace(
					[
						'{summ}',
						'{currency}',
						'{inviteSumValue}',
						'{referals}',
						'{joined}'
					],
					[
						$GLOBALS['summ'],
						$GLOBALS['currency'],
						$GLOBALS['inviteSumValue'],
						$referals,
						$joinedTG
					],
					$conditions
				);
				$mustBe          = $GLOBALS['inviteSumValue'] - $referals;
				$remindFriend    = $this->getPhraseText("inviteFriends_button", $chat_id);
				$remindFriendBTN = str_replace(
					['{remained}'],
					[$mustBe],
					$remindFriend
				);
				$keyboard        = [
					'inline_keyboard' => [
						[
							[
								'text'          => $remindFriendBTN,
								'callback_data' => 'invite_friend'
							]
						]
					]
				];
				$content         = [
					'chat_id'      => $chat_id,
					'message_id'   => $message_id,
					'text'         => $message,
					'reply_markup' => json_encode($keyboard)
				];
			}


			$telegram->editMessageText($content);

		}
	}


//Пригласить друга
	public function handlePartnerCommand($telegram, $chat_id) {
		$ref_link = $this->generateReferralLink($chat_id);

		$referal        = $this->getReferralsCount($chat_id);
		$balance        = $GLOBALS['bonus'] * $referal . $GLOBALS['currency'];
		$partnerMessage = $this->getPhraseText("partner_text", $chat_id);
		$message        = str_replace(
			[
				'{$ref_link}',
				'{$bonus}',
				'{$referal}',
				'{$balance}'
			],
			[
				$ref_link,
				$GLOBALS['bonus'],
				$referal,
				$balance
			],
			$partnerMessage
		);


		$content = [
			'chat_id' => $chat_id,
			'text'    => $message,
		];
		$telegram->sendMessage($content);
	}

//Баланс
	public function handleBalanceCommand($telegram, $chat_id) {
		$balance = $this->getUserBalance($chat_id);

		$balanceMessage = $this->getPhraseText("balance_text", $chat_id);
		$message        = str_replace(
			[
				'{$balance}',
				'{$minWithdraw}'
			],
			[
				$balance,
				$GLOBALS['minWithdraw']
			],
			$balanceMessage
		);
		$withdraw       = $this->getPhraseText("withdraw_text", $chat_id);

		$keyboard = [
			'inline_keyboard' => [
				[
					[
						'text'          => $withdraw,
						'callback_data' => 'withdraw'
					]
				]
			]
		];

		$content = [
			'chat_id'      => $chat_id,
			'text'         => $message,
			'reply_markup' => json_encode($keyboard)
		];
		$telegram->sendMessage($content);
		/* $content2 = [
			'chat_id' => 5487112108,
			'text' => $callback_data,
		];
		$telegram->sendMessage($content2); */
	}

//Вывод денег
	public function handleWithdrawCommand($telegram, $chat_id, $message_id) {
		$referal = $this->getReferralsCount($chat_id);
		if ($referal < 3) {
			$message = $this->getPhraseText('erWithdraw_text', $chat_id);
			$telegram->editMessageText([
				'chat_id'    => $chat_id,
				'message_id' => $message_id,
				'text'       => $message,
			]);
			return;
		} else {

			$keyboard = [
				'inline_keyboard' => [
					[
						[
							'text'          => "Kaspi",
							'callback_data' => 'kspi'
						]
					],
					[
						[
							'text'          => "Банковска карта",
							'callback_data' => 'card'
						]
					],

				]
			];
			$content  = [
				'chat_id'      => $chat_id,
				'message_id'   => $message_id,
				'text'         => 'Вывод доступен',
				'reply_markup' => json_encode($keyboard)
			];
			$telegram->editMessageText($content);


		}
	}

// Скачать приложение
	public function handleDwnloadCommand($telegram, $chat_id) {
		$lang     = $this->getLanguage($chat_id);
		$message  = $this->getPhraseText("download_button", $chat_id);
		$appStore = $this->getPhraseText("dwnldApp_button", $chat_id);
		$gPlay    = $this->getPhraseText("dwnldGoogle_button", $chat_id);
		$appURL   = 'https://apps.apple.com/{$lang}/app/shein-shopping-online/id878577184';
		$appUPD   = str_replace(
			['{$lang}'],
			[$lang],
			$appURL
		);
		$gURL     = 'https://play.google.com/store/apps/details?id=com.zzkko&hl={$lang}';
		$gUPD     = str_replace(
			['{$lang}'],
			[$lang],
			$gURL
		);
		$keyboard = [
			'inline_keyboard' => [
				[
					[
						'text' => $appStore,
						'url'  => $appUPD
					]

				],
				[
					[
						'text' => $gPlay,
						'url'  => $gUPD
					]
				]
			]
		];
		$content  = [
			'chat_id'      => $chat_id,
			'text'         => $message,
			'reply_markup' => json_encode($keyboard)
		];
		$telegram->sendMessage($content);
	}

//Помощь
	public function handleHelpCommand($telegram, $chat_id) {

		$report   = $this->getPhraseText("button_report", $chat_id);
		$message1 = $this->getPhraseText("help_text", $chat_id);
		$message  = str_replace(
			[
				'{summ}',
				'{currency}'
			],
			[
				$GLOBALS['summ'],
				$GLOBALS['currency']
			],
			$message1
		);
		$keyboard = [
			'inline_keyboard' => [
				[
					[
						'text'          => $report,
						'callback_data' => 'rep_ru'
					]
				]
			]
		];
		$content  = [
			'chat_id'      => $chat_id,
			'text'         => $message,
			'reply_markup' => json_encode($keyboard)
		];
		$telegram->sendMessage($content);
	}

//Заработать
	public function handleEarnCommand($telegram, $chat_id) {
		$message      = $this->getPhraseText("income_text", $chat_id);
		$inviteSum    = str_replace('{$inviteSum}', $GLOBALS['inviteSumValue'], $this->getPhraseText("invite_friend", $chat_id));
		$subscribeSum = str_replace('{$subscribeSum}', $GLOBALS['subscribeSumValue'], $this->getPhraseText("join_channel", $chat_id));
		$watchSum     = str_replace('{$wachSum}', $GLOBALS['watchSumValue'], $this->getPhraseText("view_post", $chat_id));

		$keyboard = [
			'inline_keyboard' => [
				[
					[
						'text'          => $inviteSum,
						'callback_data' => 'invite_friend'
					]
				],
				[
					[
						'text'          => $subscribeSum,
						'callback_data' => 'join_channel'
					]
				],
				[
					[
						'text'          => $watchSum,
						'callback_data' => 'view_post'
					]
				]
			]
		];
		$content  = [
			'chat_id'      => $chat_id,
			'text'         => $message,
			'reply_markup' => json_encode($keyboard)
		];
		$telegram->sendMessage($content);
	}

//Проверка подписки + начисление к балансу
	public function handleSubscribeCheckCommand($chat_id, $telegram, $bot_token, $message_id) {
		$flag = $this->isUserSubscribed($chat_id, $telegram, $bot_token);
		if ($flag) {
			$tg_key     = 'tg' . $GLOBALS['valueTg'];
			$channelURL = $this->getURL($tg_key);

			$handleMessage = $this->getPhraseText("approveSubscribe_text", $chat_id);
			$message       = str_replace(
				['{$joinChannelPay}'],
				[$GLOBALS['joinChannelPay']],
				$handleMessage
			);

			$skip     = $this->getPhraseText("skipChannel_button", $chat_id);
			$keyboard = [
				'inline_keyboard' => [
					[
						[
							'text'          => $skip,
							'callback_data' => 'skip'
						]
					]
				]
			];

			$content = [
				'chat_id'      => $chat_id,
				'message_id'   => $message_id,
				'text'         => $message,
				'reply_markup' => json_encode($keyboard)
			];
			$telegram->editMessageText($content);
			$this->incrementBalance($chat_id, 9); /// апдейт баланса
		} else {
			$tg_key     = 'tg' . $GLOBALS['valueTg'];
			$channelURL = $this->getURL($tg_key);

			$handleMessage = $this->getPhraseText("notSunscribe_text", $chat_id);
			$message       = str_replace(
				['{$channelURL}'],
				[$channelURL],
				$handleMessage
			);

			$skip     = $this->getPhraseText("skipChannel_button", $chat_id);
			$check    = $this->getPhraseText("checkChannel_button", $chat_id);
			$keyboard = [
				'inline_keyboard' => [
					[
						[
							'text'          => $check,
							'callback_data' => 'check'
						]
					],
					[
						[
							'text'          => $skip,
							'callback_data' => 'skip'
						]
					]
				]
			];


			$content = [
				'chat_id'      => $chat_id,
				'message_id'   => $message_id,
				'text'         => $message,
				'reply_markup' => json_encode($keyboard)
			];
			$telegram->editMessageText($content);
		}
	}

//Действие если не подписан
	public function handleJoinChannelCommand($telegram, $chat_id, $message_id) {
		$tg_key     = 'tg' . $GLOBALS['valueTg'];
		$channelURL = $this->getURL($tg_key);

		$handleMessage = $this->getPhraseText("join_text", $chat_id);
		$message       = str_replace(
			[
				'{$sum}',
				'{$chanURL}'
			],
			[
				$GLOBALS['joinChannelPay'],
				$channelURL
			],
			$handleMessage
		);

		$skip  = $this->getPhraseText("skipChannel_button", $chat_id);
		$check = $this->getPhraseText("checkChannel_button", $chat_id);

		$keyboard = [
			'inline_keyboard' => [
				[
					[
						'text'          => $check,
						'callback_data' => 'check'
					]
				],
				[
					[
						'text'          => $skip,
						'callback_data' => 'skip'
					]
				]
			]
		];

		$content = [
			'chat_id'      => $chat_id,
			'message_id'   => $message_id,
			'text'         => $message,
			'reply_markup' => json_encode($keyboard)
		];

		// Попробуйте обработать ошибки
		try {
			$telegram->editMessageText($content);
		} catch (Exception $e) {
			error_log('Ошибка при редактировании сообщения: ' . $e->getMessage());
		}
	}

//Блок  жалоб
	public function handleReportCommand($telegram, $chat_id, $message_id) {
		$message  = $this->getPhraseText("rep_text", $chat_id);
		$keyboard = [
			'inline_keyboard' => [
				[
					[
						'text'          => $this->getPhraseText("spam_button", $chat_id),
						'callback_data' => 'spam'
					]
				],
				[
					[
						'text'          => $this->getPhraseText("fraud_button", $chat_id),
						'callback_data' => 'fraud'
					]
				],
				[
					[
						'text'          => $this->getPhraseText("violence_button", $chat_id),
						'callback_data' => 'violence'
					]
				],
				[
					[
						'text'          => $this->getPhraseText("copyright_button", $chat_id),
						'callback_data' => 'copyright'
					]
				],
				[
					[
						'text'          => $this->getPhraseText("other_button", $chat_id),
						'callback_data' => 'other'
					]
				]
			]
		];
		$content  = [
			'chat_id'      => $chat_id,
			'message_id'   => $message_id,
			'text'         => $message,
			'reply_markup' => json_encode($keyboard)
		];
		$telegram->editMessageText($content);
	}


//Спам
	public function handleSpamCommand($telegram, $chat_id, $message_id) {
		$message  = $this->getPhraseText("report_text", $chat_id);
		$keyboard = [
			'inline_keyboard' => [
				[
					[
						'text'          => $this->getPhraseText("yes_button", $chat_id),
						'callback_data' => '(spam)'
					],
					[
						'text'          => $this->getPhraseText("no_button", $chat_id),
						'callback_data' => 'no'
					]
				]
			]
		];
		$content  = [
			'chat_id'      => $chat_id,
			'message_id'   => $message_id,
			'text'         => $message,
			'reply_markup' => json_encode($keyboard)
		];
		$telegram->editMessageText($content);
	}

//Мошенничество
	public function handleFraudCommand($telegram, $chat_id, $message_id) {
		$message  = $this->getPhraseText("report_text", $chat_id);
		$keyboard = [
			'inline_keyboard' => [
				[
					[
						'text'          => $this->getPhraseText("yes_button", $chat_id),
						'callback_data' => '(fraud)'
					],
					[
						'text'          => $this->getPhraseText("no_button", $chat_id),
						'callback_data' => 'no'
					]
				]
			]
		];
		$content  = [
			'chat_id'      => $chat_id,
			'message_id'   => $message_id,
			'text'         => $message,
			'reply_markup' => json_encode($keyboard)
		];
		$telegram->editMessageText($content);
	}

//Насилие
	public function handleViolenceCommand($telegram, $chat_id, $message_id) {
		$message  = $this->getPhraseText("report_text", $chat_id);
		$keyboard = [
			'inline_keyboard' => [
				[
					[
						'text'          => $this->getPhraseText("yes_button", $chat_id),
						'callback_data' => '(violence)'
					],
					[
						'text'          => $this->getPhraseText("no_button", $chat_id),
						'callback_data' => 'no'
					]
				]
			]
		];
		$content  = [
			'chat_id'      => $chat_id,
			'message_id'   => $message_id,
			'text'         => $message,
			'reply_markup' => json_encode($keyboard)
		];
		$telegram->editMessageText($content);
	}

//Авторские права
	public function handleCopyrightCommand($telegram, $chat_id, $message_id) {
		$message  = $this->getPhraseText("report_text", $chat_id);
		$keyboard = [
			'inline_keyboard' => [
				[
					[
						'text'          => $this->getPhraseText("yes_button", $chat_id),
						'callback_data' => '(copyright)'
					],
					[
						'text'          => $this->getPhraseText("no_button", $chat_id),
						'callback_data' => 'no'
					]
				]
			]
		];
		$content  = [
			'chat_id'      => $chat_id,
			'message_id'   => $message_id,
			'text'         => $message,
			'reply_markup' => json_encode($keyboard)
		];
		$telegram->editMessageText($content);
	}

//Другое
	public function handleOtherCommand($telegram, $chat_id, $message_id) {
		$message  = $this->getPhraseText("report_text", $chat_id);
		$keyboard = [
			'inline_keyboard' => [
				[
					[
						'text'          => $this->getPhraseText("yes_button", $chat_id),
						'callback_data' => '(other)'
					],
					[
						'text'          => $this->getPhraseText("no_button", $chat_id),
						'callback_data' => 'no'
					]
				]
			]
		];
		$content  = [
			'chat_id'      => $chat_id,
			'message_id'   => $message_id,
			'text'         => $message,
			'reply_markup' => json_encode($keyboard)
		];
		$telegram->editMessageText($content);
	}

//Подтверждение жалобы
	public function handleApprovCommand($telegram, $chat_id, $message_id, $callback_data) {
		$rep      = $this->getPhraseText("approved_text", $chat_id);
		$keyboard = [
			'inline_keyboard' => [
				[
					[
						'text' => $this->getPhraseText("writeAdmin_text", $chat_id),
						'url'  => $GLOBALS['adminHREF']
					],
				]
			]
		];
		$username = $this->getUserUsername($chat_id);

		$message = 'Жалоба пользоватлея';

		$content      = [
			'chat_id'      => $chat_id,
			'message_id'   => $message_id,
			'text'         => $rep,
			'reply_markup' => json_encode($keyboard)
		];
		$contentAdmin = [
			'chat_id' => $GLOBALS['adminID'],
			'text'    => $message . ' @' . $username . ' ' . $callback_data,

		];
		$telegram->editMessageText($content);
		$telegram->sendMessage($contentAdmin);
	}

//Отмена жалобы
	public function handleCanceledCommand($telegram, $chat_id, $message_id) {
		$message = $this->getPhraseText("canceled_text", $chat_id);
		$content = [
			'chat_id'    => $chat_id,
			'message_id' => $message_id,
			'text'       => $message

		];
		$telegram->editMessageText($content);
	}


	public function getURL($tg_key) {
		$sql  = "SELECT * FROM channel_tg WHERE tg_key = ?";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("s", $tg_key);
		$stmt->execute();
		$result = $stmt->get_result();

		if ($result->num_rows > 0) {
			$row = $result->fetch_assoc();
			$stmt->close();
			return urldecode($row['tg_url']);
		} else {
			$stmt->close();
			return null;
		}
	}
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


	public function adminModeRas($chat_id, $telegram) {
		$message      = 'Вы вошли в рассылку';
		$reply_markup = $telegram->buildKeyboard([
			[
				'Добавить текст',
				'Обзор',
				'Главное меню'
			],
		], $oneTimeKeyboard = false, $resizeKeyboard = true, $selective = true);


		$content = [
			'chat_id'      => $chat_id,
			'text'         => $message,
			'reply_markup' => $reply_markup
		];
		$telegram->sendMessage($content);
	}

	public function isInputMode($chat_id) {
		$sql  = 'SELECT status FROM users WHERE id_tg = ?';
		$stmt = $this->conn->prepare($sql);

		if ($stmt === false) {
			// Обработка ошибки подготовки запроса
			return 'error'; // Или выбросьте исключение
		}

		// Привязка параметра
		$stmt->bind_param("i", $chat_id);

		// Выполнение запроса
		if ($stmt->execute()) {
			$result = $stmt->get_result();

			// Проверка наличия результата
			if ($result->num_rows > 0) {
				$row = $result->fetch_assoc();
				return $row['status']; // Возвращаем статус (например, 'input_mode' или 'def')
			} else {
				return 'def'; // Если пользователь не найден, возвращаем статус по умолчанию
			}
		} else {
			// Обработка ошибки выполнения запроса
			return 'error'; // Или выбросьте исключение
		}
	}

	public function setInputMode($chat_id, $mode) {
		$sql  = "UPDATE users SET status = ? WHERE id_tg = ?"; // Изменено на id_tg
		$stmt = $this->conn->prepare($sql);

		if ($stmt === false) {
			// Обработка ошибки подготовки запроса
			return false; // Или выбросьте исключение
		}

		// Привязка параметра
		$stmt->bind_param("si", $mode, $chat_id);

		// Выполнение запроса
		if (!$stmt->execute()) {
			// Обработка ошибки выполнения запроса
			return false; // Или выбросьте исключение
		}

		return true; // Возвращаем успех
	}

	public function saveUserText($chat_id, $telegram, $text) {
		$telegram->sendMessage([
			'chat_id' => $chat_id,
			'text'    => "Вы ввели: " . $text
		]);
		$sql  = "INSERT INTO mailing (message_text) VALUES (?)";
		$stmt = $this->conn->prepare($sql);


		// Привязка параметра (используем "s" для строки)
		$stmt->bind_param("s", $text);

		// Выполнение запроса
		if (!$stmt->execute()) {
			// Обработка ошибки выполнения запроса
			return false; // Или выбросьте исключение
		}

		return true; // Возвращаем успех
	}

	public function handleUserInput($chat_id, $telegram) {
		$telegram->sendMessage([
			'chat_id' => $chat_id,
			'text'    => 'Вошли в режим ввода текста!
Введите ваш текст'
		]);
		$this->setInputMode($chat_id, 'input_mode');
	}

	public function __destruct() {
		$this->conn->close();
	}

}

function isTextMatchingButtons($text) {
	foreach ($GLOBALS['buttons'] as $buttonValues) {
		if (in_array($text, $buttonValues)) {
			return true;
		}
	}
	return false;
}

/* function sendMessageToChat($telegram, $chat_id) {
    $tgUrls = $db->getTgUrl();
	foreach ($tgUrls as $url) {
        $message .= "- " . $this->escapeString($url) . "\n";
    }
    $this->sendChatMessage($message);	
    $params = [
        'chat_id' => $chat_id,
        'text' => $message
    ];
    $telegram->sendMessage($params);
	
}
sendMessageToChat($telegram, $chat_id); */

$db = new DatabaseConnection();


/* $telegram->sendMessage([
    'chat_id' => $chat_id,
    'text' => $GLOBALS['valueTg']
]);
 */

$telegram->sendMessage([
	'chat_id' => $chat_id,
	'text'    => 'Callback data: ' . $callback_data,
]);

switch ($callback_data) {
	case 'withdraw':
		$db->handleWithdrawCommand($telegram, $chat_id, $message_id);
		break;
	case 'rep_ru':
		$db->handleReportCommand($telegram, $chat_id, $message_id);
		break;
	case'fraud':
		$db->handleFraudCommand($telegram, $chat_id, $message_id);
		break;
	case'(fraud)':
		$db->handleApprovCommand($telegram, $chat_id, $message_id, $callback_data);
		break;
	case 'spam':
		$db->handleSpamCommand($telegram, $chat_id, $message_id);
		break;
	case '(spam)':
		$db->handleApprovCommand($telegram, $chat_id, $message_id, $callback_data);
		break;
	case'violence':
		$db->handleViolenceCommand($telegram, $chat_id, $message_id);
		break;
	case'(violence)':
		$db->handleApprovCommand($telegram, $chat_id, $message_id, $callback_data);
		break;
	case'copyright':
		$db->handleCopyrightCommand($telegram, $chat_id, $message_id);
		break;
	case'(copyright)':
		$db->handleApprovCommand($telegram, $chat_id, $message_id, $callback_data);
		break;
	case'other':
		$db->handleOtherCommand($telegram, $chat_id, $message_id);
		break;
	case'(other)':
		$db->handleApprovCommand($telegram, $chat_id, $message_id, $callback_data);
		break;
	case 'yes':
		$db->handleApprovCommand($telegram, $chat_id, $message_id, $callback_data);
		break;
	case 'invite_friend':
		$db->handlePartnerCommand($telegram, $chat_id);
		break;
	case 'join_channel':
		$db->handleJoinChannelCommand($telegram, $chat_id, $message_id);
		break;
	case 'skip':
		$db->handleJoinChannelCommand($telegram, $chat_id, $message_id);
		break;
	case 'view_post':
		$db->count_to_ten($telegram, $chat_id, $message_id);
		break;
	case 'check':
		$db->handleSubscribeCheckCommand($chat_id, $telegram, $bot_token, $message_id);

		break;
	case 'checkSub':
		$db->handleBalanceCommand($telegram, $chat_id, $bot_token);
		break;
	case 'no':
		$db->handleCanceledCommand($telegram, $chat_id, $message_id);
		break;
	default:
		// Handle any other cases or provide a default response
		break;
}

$telegram->sendMessage([
	'chat_id' => $chat_id,
	'text'    => 'Text: ' . $text,
]);

switch ($text) {
	case strpos($text, '/start') === 0:
		$db->handleStartCommand($telegram, $chat_id, $update);
		break;
	case isTextMatchingButtons($text):
		$hhh = null;
		foreach ($GLOBALS['buttons'] as $key => $values) {
			if (in_array($text, $values)) {
				$hhh = $key;
				break;
			}
		}
		if ($hhh !== null) {
			$db->updateUserLanguage($chat_id, $hhh);
		}
		$db->handleLanguage($telegram, $chat_id);
		break;
	case $db->getPhraseText("welcome_button", $chat_id):
		$db->handleMainMenu($telegram, $chat_id);

		break;
	case $db->getPhraseText("button_balance", $chat_id):
		$db->handleBalanceCommand($telegram, $chat_id, $bot_token);
		break;
	case $db->getPhraseText("button_partners", $chat_id):
		$db->handlePartnerCommand($telegram, $chat_id);
		break;
	case $db->getPhraseText("button_changeLang", $chat_id):
		$db->handleStartCommand($telegram, $chat_id, $update);
		break;
	case $db->getPhraseText("button_Help", $chat_id):
		$db->handleHelpCommand($telegram, $chat_id);
		break;
	case 'Админ кнопка':
		$db->getChatIdByLink($telegram, $bot_token, $chat_id);
		break;
	case $db->getPhraseText("button_earn", $chat_id):


		$db->handleEarnCommand($telegram, $chat_id);

		break;
	case 'Рассылка':
		if ($db->isAdmin($telegram, $chat_id) == 'admin') {
			$db->takeAllId($telegram, $chat_id);
			$db->adminModeRas($chat_id, $telegram);
		}
		break;
	case 'Добавить текст':
		if ($db->isAdmin($telegram, $chat_id) == 'admin') {

			$db->handleUserInput($chat_id, $telegram);
		}
		break;
	case 'Главное меню':
		$db->setInputMode($chat_id, 'def');
		$db->handleMainMenu($telegram, $chat_id);
		break;
	case ($text != null): // Проверяем, что текст не null

		if ($db->isInputMode($chat_id) == 'input_mode') {
			$params = [
				'chat_id' => $chat_id,
				'text'    => 'вошло'
			];
			$telegram->sendMessage($params);
			$db->saveUserText($chat_id, $telegram, $text);
		}
		break;

	case $db->getPhraseText('download_button', $chat_id):
		$db->handleDwnloadCommand($telegram, $chat_id);

		break;
	default:
		// Handle any other cases or provide a default response
		break;
}


?>


	