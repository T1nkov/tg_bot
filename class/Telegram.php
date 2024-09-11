<?php

if (file_exists('Telegram_Error_Logger.php')) { require_once 'Telegram_Error_Logger.php'; }

class Telegram {
    const INLINE_QUERY = 'inline_query';
    const CALLBACK_QUERY = 'callback_query';
    const EDITED_MESSAGE = 'edited_message';
    const REPLY = 'reply';
    const MESSAGE = 'message';
    const PHOTO = 'photo';
    const VIDEO = 'video';
    const AUDIO = 'audio';
    const VOICE = 'voice';
    const ANIMATION = 'animation';
    const STICKER = 'sticker';
    const DOCUMENT = 'document';
    const LOCATION = 'location';
    const CONTACT = 'contact';
    const CHANNEL_POST = 'channel_post';
    const NEW_CHAT_MEMBER = 'new_chat_member';
    const LEFT_CHAT_MEMBER = 'left_chat_member';
    const MY_CHAT_MEMBER = 'my_chat_member';

    private $bot_token = '';
    private $data = [];
    private $updates = [];
    private $log_errors;
    private $proxy;
    private $update_type;

    public function __construct($bot_token, $log_errors = true, array $proxy = []) { // Telegram instance from bot token
        $this->bot_token = $bot_token;
        $this->data = $this->getData();
        $this->log_errors = $log_errors;
        $this->proxy = $proxy;
    }

    public function endpoint($api, array $content = [], $post = true) { // requests to Telegram Bot API
        $url = 'https://api.telegram.org/bot' . $this->bot_token . '/' . $api;
        $reply = $this->sendAPIRequest($url, $post ? $content : [], !$post);
        return json_decode($reply, true);
    }

    public function respondSuccess() { // Responds with a success HTTP status to Telegram.
        http_response_code(200);
        return json_encode(['status' => 'success']); // @return string JSON-encoded response.
    }
 
    public function getMe() { return $this->endpoint('getMe', [], false); } //for testing bot - https://core.telegram.org/bots/api#getme

    public function logOut() { return $this->endpoint('logOut', [], false); } // https://core.telegram.org/bots/api#logout

    public function close() { return $this->endpoint('close', [], false); } // https://core.telegram.org/bots/api#close

    public function sendMessage(array $content) { return $this->endpoint('sendMessage', $content); } // https://core.telegram.org/bots/api#sendmessage

    public function copyMessage(array $content) { return $this->endpoint('copyMessage', $content); } // https://core.telegram.org/bots/api#copymessage

    public function forwardMessage(array $content) { return $this->endpoint('forwardMessage', $content); } // https://core.telegram.org/bots/api#forwardmessage

    public function sendPhoto(array $content) { return $this->endpoint('sendPhoto', $content); } // https://core.telegram.org/bots/api#sendphoto

    public function sendAudio(array $content) { return $this->endpoint('sendAudio', $content); } // https://core.telegram.org/bots/api#sendaudio

    public function sendDocument(array $content) { return $this->endpoint('sendDocument', $content); } // https://core.telegram.org/bots/api#senddocument

    public function sendAnimation(array $content) { return $this->endpoint('sendAnimation', $content); } // https://core.telegram.org/bots/api#sendanimation

    public function sendSticker(array $content) { return $this->endpoint('sendSticker', $content); } // https://core.telegram.org/bots/api#sendsticker

    public function sendVideo(array $content) { return $this->endpoint('sendVideo', $content); } // https://core.telegram.org/bots/api#sendvideo

    public function sendVoice(array $content) { return $this->endpoint('sendVoice', $content); } // https://core.telegram.org/bots/api#sendvoice

    public function sendLocation(array $content) { return $this->endpoint('sendLocation', $content); } // https://core.telegram.org/bots/api#sendlocation

    // https://core.telegram.org/bots/api#editmessageliveLocation
    public function editMessageLiveLocation(array $content) { return $this->endpoint('editMessageLiveLocation', $content); }

    // https://core.telegram.org/bots/api#stopmessagelivelocation
    public function stopMessageLiveLocation(array $content) { return $this->endpoint('stopMessageLiveLocation', $content); }

    // https://core.telegram.org/bots/api#setchatstickerset
    public function setChatStickerSet(array $content) { return $this->endpoint('setChatStickerSet', $content); }

    // https://core.telegram.org/bots/api#deletechatstickerset
    public function deleteChatStickerSet(array $content) { return $this->endpoint('deleteChatStickerSet', $content); }

    // https://core.telegram.org/bots/api#sendmediagroup
    public function sendMediaGroup(array $content) { return $this->endpoint('sendMediaGroup', $content); }

    // https://core.telegram.org/bots/api#sendvenue
    public function sendVenue(array $content) { return $this->endpoint('sendVenue', $content); }

    // https://core.telegram.org/bots/api#sendcontact
    public function sendContact(array $content) { return $this->endpoint('sendContact', $content); }

    // https://core.telegram.org/bots/api#sendpoll
    public function sendPoll(array $content) { return $this->endpoint('sendPoll', $content); }

    // https://core.telegram.org/bots/api#senddice
    public function sendDice(array $content) { return $this->endpoint('sendDice', $content); }

    // https://core.telegram.org/bots/api#sendchataction
    public function sendChatAction(array $content) { return $this->endpoint('sendChatAction', $content); }

    // https://core.telegram.org/bots/api#getuserprofilephotos
    public function getUserProfilePhotos(array $content) { return $this->endpoint('getUserProfilePhotos', $content); }

    // https://api.telegram.org/file/bot<token>/<file_path>
    public function getFile(string $file_id) { return $this->endpoint('getFile', ['file_id' => $file_id]); }

    /// Kick Chat Member
    //  * * * Deprecated * * *
    public function kickChatMember(array $content) { return $this->endpoint('kickChatMember', $content); }

    // https://core.telegram.org/bots/api#leavechat
    public function leaveChat(array $content) { return $this->endpoint('leaveChat', $content); }

    // https://core.telegram.org/bots/api#banchatmember
    public function banChatMember(array $content) { return $this->endpoint('banChatMember', $content); }

    // https://core.telegram.org/bots/api#unbanchatmember
    public function unbanChatMember(array $content) { return $this->endpoint('unbanChatMember', $content); }

    // https://core.telegram.org/bots/api#getchat
    public function getChat(array $content) { return $this->endpoint('getChat', $content); }

    // https://core.telegram.org/bots/api#getchatadministrators
    public function getChatAdministrators(array $content) { return $this->endpoint('getChatAdministrators', $content); }

    // https://core.telegram.org/bots/api#getchatmembercount
    public function getChatMemberCount(array $content) { return $this->endpoint('getChatMemberCount', $content); }

    /**
     * For retrocompatibility
     * \param $content the request parameters as array
     * \return the JSON Telegram's reply.
     */
    public function getChatMembersCount(array $content) { return $this->getChatMemberCount($content); }

    // https://core.telegram.org/bots/api#getchatmember
    public function getChatMember(array $content) { return $this->endpoint('getChatMember', $content); }

    // https://core.telegram.org/bots/api#answerinlinequery
    public function answerInlineQuery(array $content) { return $this->endpoint('answerInlineQuery', $content); }

    // https://core.telegram.org/bots/api#setgamescore
    public function setGameScore(array $content) { return $this->endpoint('setGameScore', $content); }

    // https://core.telegram.org/bots/api#getgamehighscores
    public function getGameHighScores(array $content) { return $this->endpoint('getGameHighScores', $content); }

    // https://core.telegram.org/bots/api#answercallbackquery
    public function answerCallbackQuery(array $content) { return $this->endpoint('answerCallbackQuery', $content); }

    // https://core.telegram.org/bots/api#setmycommands
    public function setMyCommands(array $content) { return $this->endpoint('setMyCommands', $content); }

    // https://core.telegram.org/bots/api#deletemycommands
    public function deleteMyCommands(array $content) { return $this->endpoint('deleteMyCommands', $content); }

    // https://core.telegram.org/bots/api#getmycommands
    public function getMyCommands(array $content) { return $this->endpoint('getMyCommands', $content); }

    // https://core.telegram.org/bots/api#setchatmenubutton
    public function setChatMenuButton(array $content) { return $this->endpoint('setChatMenuButton', $content); }

    // https://core.telegram.org/bots/api#getchatmenubutton
    public function getChatMenuButton(array $content) { return $this->endpoint('getChatMenuButton', $content); }

    // https://core.telegram.org/bots/api#setmydefaultadministratorrights
    public function setMyDefaultAdministratorRights(array $content) { return $this->endpoint('setMyDefaultAdministratorRights', $content); }

    // https://core.telegram.org/bots/api#getmydefaultadministratorrights
    public function getMyDefaultAdministratorRights(array $content) { return $this->endpoint('getMyDefaultAdministratorRights', $content); }

    // https://core.telegram.org/bots/api#editmessagetext
    public function editMessageText(array $content) { return $this->endpoint('editMessageText', $content); }

    // https://core.telegram.org/bots/api#editmessagecaption
    public function editMessageCaption(array $content) { return $this->endpoint('editMessageCaption', $content); }

    // https://core.telegram.org/bots/api#editmessagemedia
    public function editMessageMedia(array $content) { return $this->endpoint('editMessageMedia', $content); }

    // https://core.telegram.org/bots/api#editmessagereplymarkup
    public function editMessageReplyMarkup(array $content) { return $this->endpoint('editMessageReplyMarkup', $content); }

    // https://core.telegram.org/bots/api#stoppoll
    public function stopPoll(array $content) { return $this->endpoint('stopPoll', $content); }

    /**
     * Use this method to download a file from the Telegram servers.
     * @param string $telegram_file_path File path on Telegram servers
     * @param string $local_file_path File path where to save the file.
     * @throws Exception if the file cannot be opened
     */
    public function downloadFile(string $telegram_file_path, string $local_file_path) {
        $file_url = 'https://api.telegram.org/file/bot'.$this->bot_token.'/'.$telegram_file_path;
        if (!$in = fopen($file_url, 'rb')) { throw new Exception("Could not open file: $file_url"); }
        if (!$out = fopen($local_file_path, 'wb')) {
            fclose($in);
            throw new Exception("Could not open file for writing: $local_file_path");
        }
        while ($chunk = fread($in, 8192)) { fwrite($out, $chunk); }
        fclose($in);
        fclose($out);
    }

    /**
     * Set a WebHook for the bot.
     * @param string $url HTTPS URL to send updates to. Use an empty string to remove webhook integration.
     * @param string|InputFile $certificate Upload your public key certificate so that the root certificate can be checked.
     * @return array The JSON Telegram's reply.
     * @throws Exception If the URL is invalid.
     */
    public function setWebhook(string $url, $certificate = null) {
        if (!filter_var($url, FILTER_VALIDATE_URL) && $url !== '') { throw new Exception('Invalid URL provided.'); }
        $requestBody = ['url' => $url];
        if ($certificate) { $requestBody['certificate'] = is_string($certificate) ? '@' . $certificate : $certificate; }
        return $this->endpoint('setWebhook', $requestBody, true);
    }

    /**
     *  Use this method to remove webhook integration if you decide to switch back to <a href="https://core.telegram.org/bots/api#getupdates">getUpdates</a>. Returns True on success. Requires no parameters.
     * \return the JSON Telegram's reply.
     */
    public function deleteWebhook() { return $this->endpoint('deleteWebhook', [], false); }

    /**
     * Get the data of the current message.
     * Retrieve the POST request of a user in a Webhook or the message processed
     * in a getUpdates() environment.
     * @return array The JSON user's message.
     */
    public function getData() {
        if (!empty($this->data)) { return $this->data; }
        $rawData = file_get_contents('php://input');
        return $rawData ? json_decode($rawData, true) : [];
    }

    /// Set the data currently used
    public function setData(array $data) { $this->data = $data; }

    /// Get the text of the current message
    public function Text() { // return the String users's text.
        $types = [
            self::CALLBACK_QUERY => 'callback_query',
            self::CHANNEL_POST => 'channel_post',
            self::EDITED_MESSAGE => 'edited_message',
            'default' => 'message'
        ];
        $type = $this->getUpdateType();
        $key = isset($types[$type]) ? $types[$type] : $types['default'];
        return @$this->data[$key]['text'] ?? null;
    }

    public function Caption() {
        $type = $this->getUpdateType();
        if ($type == self::CHANNEL_POST) { return @$this->data['channel_post']['caption']; }
        return @$this->data['message']['caption'];
    }

    public function ChatID() { return $this->Chat()['id'] ?? null; } // return null if chat_id doesn't exist

    public function Chat() { // return the Array chat.
        $type = $this->getUpdateType();
        $keys = [
            self::CALLBACK_QUERY => 'callback_query.message.chat',
            self::CHANNEL_POST => 'channel_post.chat',
            self::EDITED_MESSAGE => 'edited_message.chat',
            self::INLINE_QUERY => 'inline_query.from',
            self::MY_CHAT_MEMBER => 'my_chat_member.chat',
        ];
        if (!isset($keys[$type])) {
            return isset($this->data['message']['chat']) ? $this->data['message']['chat'] : null;
        } else {
            $keyPath = $keys[$type];
            $chat = $this->data;
            foreach (explode('.', $keyPath) as $key) { 
                if (!isset($chat[$key])) { return null; }
                $chat = $chat[$key];
            }
            return $chat;
        }
    }

    public function MessageID() { // Get the message_id of the current message
        $type = $this->getUpdateType();
        $keys = [
            self::CALLBACK_QUERY => 'callback_query.message.message_id',
            self::CHANNEL_POST => 'channel_post.message_id',
            self::EDITED_MESSAGE => 'edited_message.message_id',
        ];
        return isset($keys[$type]) ? @$this->data[$keys[$type]] : $this->data['message']['message_id']; // String
    }

    /// Get the reply_to_message message_id of the current message
    public function ReplyToMessageID() { return $this->data['message']['reply_to_message']['message_id']; }

    /// Get the reply_to_message forward_from user_id of the current message
    public function ReplyToMessageFromUserID() { return $this->data['message']['reply_to_message']['forward_from']['id']; }

    /// Get the inline_query of the current update
    public function Inline_Query() { return $this->data['inline_query']; } // Array

    /// Get the callback_query of the current update
    public function Callback_Query() { return $this->data['callback_query']; }

    /// Get the callback_query id of the current update
    public function Callback_ID() { return $this->data['callback_query']['id']; }

    /// Get the Get the data of the current callback
    public function Callback_Data() { return $this->data['callback_query']['data']; }

    /// Get the Get the message of the current callback
    public function Callback_Message() { return $this->data['callback_query']['message']; }

    /// Get the Get the chat_id of the current callback
    public function Callback_ChatID() { return $this->data['callback_query']['message']['chat']['id']; }

    /// Get the Get the from_id of the current callback
    public function Callback_FromID() { return $this->data['callback_query']['from']['id']; }

    /// Get the date of the current message
    public function Date() { return $this->data['message']['date']; }

    /// Get user data by the specified key
    private function getUserData($key) {
        $type = $this->getUpdateType();
        $keys = [
            self::CALLBACK_QUERY => 'callback_query.from.' . $key,
            self::CHANNEL_POST => 'channel_post.from.' . $key,
            self::EDITED_MESSAGE => 'edited_message.from.' . $key,
            self::MESSAGE => 'message.from.' . $key,
        ];
        return isset($keys[$type]) ? @$this->data[$keys[$type]] : '';
    }

    public function FirstName() { return $this->getUserData('first_name'); } // Get the first name of the user

    public function LastName() { return $this->getUserData('last_name'); } // Get the last name of the user

    public function Username() { return $this->getUserData('username'); } // Get the username of the user

    public function Location() { return $this->data['message']['location']; } // Get the location in the message

    public function UpdateID() { return $this->data['update_id']; } // Get the update_id of the message

    public function UpdateCount() { return count($this->updates['result']); } // Get the number of updates

    /// Get user's id of current message
    public function UserID() {
        $type = $this->getUpdateType();
        $keys = [
            self::CALLBACK_QUERY => 'callback_query.from.id',
            self::CHANNEL_POST => 'channel_post.from.id',
            self::EDITED_MESSAGE => 'edited_message.from.id',
            self::INLINE_QUERY => 'inline_query.from.id',
        ];
        return isset($keys[$type]) ? $this->data[$keys[$type]] : $this->data['message']['from']['id'];
    }

    /// Get user's id of current forwarded message
    public function FromID() { return $this->data['message']['forward_from']['id']; }

    /// Get chat's id where current message forwarded from
    public function FromChatID() { return $this->data['message']['forward_from_chat']['id']; }

    /// Check if a message is from a group chat and get additional info
    // @return BOOLEAN true if the message is from a Group chat, false otherwise.
    public function messageFromGroup() { return $this->data['message']['chat']['type'] != 'private'; }

    // @return String of the contact phone number or an empty string if not available.
    public function getContactPhoneNumber() { return $this->getUpdateType() == self::CONTACT ? $this->data['message']['contact']['phone_number'] : ''; }

    // * @return String of the title chat or an empty string if it not a group chat.
    public function messageFromGroupTitle() { return $this->messageFromGroup() ? $this->data['message']['chat']['title'] : ''; }


    /// Set a custom keyboard
    public function buildKeyBoard(array $options, $onetime = false, $resize = false, $selective = true): string {
        return json_encode([
            'keyboard'          => $options,
            'one_time_keyboard' => $onetime,
            'resize_keyboard'   => $resize,
            'selective'         => $selective,
        ]);
    }

    public function buildInlineKeyBoard(array $options): string {
        return json_encode(['inline_keyboard' => $options]);
    }

    public function buildInlineKeyboardButton(
        string $text,
        ?string $url = null,
        ?string $callback_data = null,
        ?string $switch_inline_query = null,
        ?string $switch_inline_query_current_chat = null,
        ?string $callback_game = null,
        ?string $pay = null
    ): array {
        return array_filter([
            'text' => $text,
            'url' => $url,
            'callback_data' => $callback_data,
            'switch_inline_query' => $switch_inline_query,
            'switch_inline_query_current_chat' => $switch_inline_query_current_chat,
            'callback_game' => $callback_game,
            'pay' => $pay,
        ]);
    }

    public function buildKeyboardButton(string $text, bool $request_contact = false, bool $request_location = false): array {
        return [
            'text'             => $text,
            'request_contact'  => $request_contact,
            'request_location' => $request_location,
        ];
    }

    public function buildKeyBoardHide($selective = true): string {
        return json_encode([
            'remove_keyboard' => true,
            'selective'       => $selective,
        ]);
    }

    public function buildForceReply($selective = true): string {
        return json_encode([
            'force_reply' => true,
            'selective'   => $selective,
        ]);
    }

    /// Payments

    // https://core.telegram.org/bots/api#sendinvoice
    public function sendInvoice(array $content) { return $this->endpoint('sendInvoice', $content); }

    // https://core.telegram.org/bots/api#answershippingquery
    public function answerShippingQuery(array $content) { return $this->endpoint('answerShippingQuery', $content); }

    // https://core.telegram.org/bots/api#answerprecheckoutquery
    public function answerPreCheckoutQuery(array $content) { return $this->endpoint('answerPreCheckoutQuery', $content); }

    // https://core.telegram.org/bots/api#setpassportdataerrors
    public function setPassportDataErrors(array $content) { return $this->endpoint('setPassportDataErrors', $content); }

    // https://core.telegram.org/bots/api#sendgame
    public function sendGame(array $content) { return $this->endpoint('sendGame', $content); }

    // https://core.telegram.org/bots/api#sendvideonote
    public function sendVideoNote(array $content) { return $this->endpoint('sendVideoNote', $content); }

    // https://core.telegram.org/bots/api#restrictchatmember
    public function restrictChatMember(array $content) { return $this->endpoint('restrictChatMember', $content); }

    // https://core.telegram.org/bots/api#promotechatmember
    public function promoteChatMember(array $content) { return $this->endpoint('promoteChatMember', $content); }

    // https://core.telegram.org/bots/api#setchatadministratorcustomtitle
    public function setChatAdministratorCustomTitle(array $content) { return $this->endpoint('setChatAdministratorCustomTitle', $content); }

    /// Ban a channel chat in a super group or channel
    // https://core.telegram.org/bots/api#banchatsenderchat
    public function banChatSenderChat(array $content) { return $this->endpoint('banChatSenderChat', $content); }

    /// Unban a channel chat in a super group or channel
    // https://core.telegram.org/bots/api#unbanchatsenderchat
    public function unbanChatSenderChat(array $content) { return $this->endpoint('unbanChatSenderChat', $content); }

    /// Set default chat permission for all members
    // https://core.telegram.org/bots/api#setchatpermissions
    public function setChatPermissions(array $content) { return $this->endpoint('setChatPermissions', $content); }

    // https://core.telegram.org/bots/api#exportchatinvitelink
    public function exportChatInviteLink(array $content) { return $this->endpoint('exportChatInviteLink', $content); }

    // https://core.telegram.org/bots/api#createchatinvitelink
    public function createChatInviteLink(array $content) { return $this->endpoint('createChatInviteLink', $content); }

    // https://core.telegram.org/bots/api#editchatinvitelink
    public function editChatInviteLink(array $content) { return $this->endpoint('editChatInviteLink', $content); }

    // https://core.telegram.org/bots/api#revokechatinvitelink
    public function revokeChatInviteLink(array $content) { return $this->endpoint('revokeChatInviteLink', $content); }

    // https://core.telegram.org/bots/api#approvechatjoinrequest
    public function approveChatJoinRequest(array $content) { return $this->endpoint('approveChatJoinRequest', $content); }

    // https://core.telegram.org/bots/api#declinechatjoinrequest
    public function declineChatJoinRequest(array $content) { return $this->endpoint('declineChatJoinRequest', $content); }

    // https://core.telegram.org/bots/api#setchatphoto
    public function setChatPhoto(array $content) { return $this->endpoint('setChatPhoto', $content); }

    // https://core.telegram.org/bots/api#deletechatphoto
    public function deleteChatPhoto(array $content) { return $this->endpoint('deleteChatPhoto', $content); }
    
    // https://core.telegram.org/bots/api#setchattitle
    public function setChatTitle(array $content) { return $this->endpoint('setChatTitle', $content); }

    // https://core.telegram.org/bots/api#setchatdescription
    public function setChatDescription(array $content) { return $this->endpoint('setChatDescription', $content); }

    // https://core.telegram.org/bots/api#pinchatmessage
    public function pinChatMessage(array $content) { return $this->endpoint('pinChatMessage', $content); }

    // https://core.telegram.org/bots/api#unpinchatmessage
    public function unpinChatMessage(array $content) { return $this->endpoint('unpinChatMessage', $content); }

    // https://core.telegram.org/bots/api#unpinallchatmessages
    public function unpinAllChatMessages(array $content) { return $this->endpoint('unpinAllChatMessages', $content); }

    // https://core.telegram.org/bots/api#getstickerset
    public function getStickerSet(array $content) { return $this->endpoint('getStickerSet', $content); }

    // https://core.telegram.org/bots/api#uploadstickerfile
    public function uploadStickerFile(array $content) { return $this->endpoint('uploadStickerFile', $content); }

    // https://core.telegram.org/bots/api#createnewstickerset
    public function createNewStickerSet(array $content) { return $this->endpoint('createNewStickerSet', $content); }

    // https://core.telegram.org/bots/api#addstickertoset
    public function addStickerToSet(array $content) { return $this->endpoint('addStickerToSet', $content); }

    // https://core.telegram.org/bots/api#setstickerpositioninset
    public function setStickerPositionInSet(array $content) { return $this->endpoint('setStickerPositionInSet', $content); }

    // https://core.telegram.org/bots/api#deletestickerfromset
    public function deleteStickerFromSet(array $content) { return $this->endpoint('deleteStickerFromSet', $content); }

    // https://core.telegram.org/bots/api#setstickersetthumb
    public function setStickerSetThumb(array $content) { return $this->endpoint('setStickerSetThumb', $content); }

    // https://core.telegram.org/bots/api#deletemessage
    public function deleteMessage(array $content) { return $this->endpoint('deleteMessage', $content); }

    /// Receive incoming messages using polling
    /**
     * Use this method to receive incoming updates using long polling.
     * @param int $offset Identifier of the first update to be returned.
     * @param int $limit Limits the number of updates to be retrieved (1-100).
     * @param int $timeout Timeout in seconds for long polling.
     * @param bool $update If true, updates the pending message list.
     * @return array The updates.
     */
    public function getUpdates(int $offset = 0, int $limit = 100, int $timeout = 0, bool $update = true): array {
        $content = compact('offset', 'limit', 'timeout');
        $this->updates = $this->endpoint('getUpdates', $content);
        if ($update && !empty($this->updates['result'])) {
            $lastUpdateId = end($this->updates['result'])['update_id'] + 1;
            $this->endpoint('getUpdates', compact('lastUpdateId', 'timeout'));
        }
        return $this->updates;
    }

    /// Serve an update
    //  Use this method to use the bultin function like Text() or Username() on a specific update.
    public function serveUpdate($update) { $this->data = $this->updates['result'][$update]; } // param $update Integer The index of the update in the updates array.

    public function getUpdateType() { // 
        if ($this->update_type) return $this->update_type;
        $update = $this->data;
        $updateTypes = [
            'inline_query' => self::INLINE_QUERY,
            'callback_query' => self::CALLBACK_QUERY,
            'edited_message' => self::EDITED_MESSAGE,
            'message' => [
                'text' => self::MESSAGE,
                'photo' => self::PHOTO,
                'video' => self::VIDEO,
                'audio' => self::AUDIO,
                'voice' => self::VOICE,
                'contact' => self::CONTACT,
                'location' => self::LOCATION,
                'reply_to_message' => self::REPLY,
                'animation' => self::ANIMATION,
                'sticker' => self::STICKER,
                'document' => self::DOCUMENT,
                'new_chat_member' => self::NEW_CHAT_MEMBER,
                'left_chat_member' => self::LEFT_CHAT_MEMBER,
            ],
            'my_chat_member' => self::MY_CHAT_MEMBER,
            'channel_post' => self::CHANNEL_POST,
        ];
        foreach ($updateTypes as $key => $type) {
            if (isset($update[$key])) {
                $this->update_type = is_array($type) ? $type[array_keys(array_filter($update[$key]))[0]] : $type;
                return $this->update_type;
            }
        }
        return false; // `false` on failure.
    }

    private function sendAPIRequest($url, array $content, $post = true) {
        $url .= isset($content['chat_id']) ? '?chat_id=' . urlencode($content['chat_id']) : '';
        unset($content['chat_id']);
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST => $post,
            CURLOPT_POSTFIELDS => $post ? $content : null,
        ]);
        if (!empty($this->proxy)) {
            foreach (['type' => CURLOPT_PROXYTYPE, 'auth' => CURLOPT_PROXYUSERPWD, 'url' => CURLOPT_PROXY, 'port' => CURLOPT_PROXYPORT] as $key => $option) {
                if (isset($this->proxy[$key])) { curl_setopt($ch, $option, $this->proxy[$key]); }
            }
        }
        $result = curl_exec($ch) ?: json_encode([
            'ok' => false,
            'curl_error_code' => curl_errno($ch),
            'curl_error' => curl_error($ch),
        ]);
        curl_close($ch);
        if ($this->log_errors && class_exists('TelegramErrorLogger')) {
            $loggerArray = [$this->getData() ?: $content];
            TelegramErrorLogger::log(json_decode($result, true), $loggerArray);
        }
        return $result;
    }

}

// Helper for Uploading file using CURL
if (!function_exists('curl_file_create')) {
    function curl_file_create($filename, $mimetype = '', $postname = '') {
        return "@$filename;filename="
            .($postname ?: basename($filename))
            .($mimetype ? ";type=$mimetype" : '');
    }
}