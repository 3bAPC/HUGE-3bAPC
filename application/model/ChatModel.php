<?php

class ChatModel {

    /**
     * Fetches all direct chats for a user.
     * Includes a subquery to count unread messages: 
     * Messages NOT sent by current user and created AFTER the user last saw the chat.
     * 
     * Get all direct chats of the user existing
     * @param mixed $currentUserID
     * @return array
     */
    public static function getDirectChatsOfUser($currentUserID) {
        $database = DatabaseFactory::getFactory()->getConnection();
        $directChatTypeID = self::getDirectChatTypeID();

        if (empty($directChatTypeID)) {
            return array();
        }

        $sql = "SELECT
                    c.chat_id,
                    u.user_id AS other_user_id,
                    u.user_name AS chat_name,
                    u.user_email,
                    u.user_has_avatar,
                    (SELECT COUNT(*) FROM messages m 
                     WHERE m.chat_id = c.chat_id 
                     AND m.sent_from_id != :current_user_id_unread 
                     AND (m.timestamp > cp_me.last_seen OR cp_me.last_seen IS NULL)
                    ) AS unread_count
                FROM chats c
                INNER JOIN chat_participants cp_me
                    ON cp_me.chat_id = c.chat_id
                INNER JOIN chat_participants cp_other
                    ON cp_other.chat_id = c.chat_id
                INNER JOIN users u
                    ON u.user_id = cp_other.user_id
                WHERE c.chat_type = :direct_chat_type_id
                AND cp_me.user_id = :current_user_id
                AND cp_other.user_id != :not_current_user_id
                ORDER BY c.chat_id DESC";

        $query = $database->prepare($sql);
        $query->execute(array(
            ':direct_chat_type_id' => $directChatTypeID,
            ':current_user_id' => $currentUserID,
            ':not_current_user_id' => $currentUserID,
            ':current_user_id_unread' => $currentUserID
        ));

        $chats = $query->fetchAll();

        foreach ($chats as $chat) {
            $chat->chat_avatar_link = Config::get('USE_GRAVATAR')
                ? AvatarModel::getGravatarLinkByEmail($chat->user_email)
                : AvatarModel::getPublicAvatarFilePathOfUser($chat->user_has_avatar, $chat->other_user_id);
        }

        return $chats;
    }

    /**
     * Updates the last_seen timestamp for a specific user in a specific chat to the current time.
     * 
     * @param int $chatID
     * @param int $userID
     */
    public static function updateLastSeen($chatID, $userID) {
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "UPDATE chat_participants SET last_seen = NOW() 
                WHERE chat_id = :chat_id AND user_id = :user_id";
        $query = $database->prepare($sql);
        $query->execute(array(':chat_id' => $chatID, ':user_id' => $userID));
    }

    /**
     * Get all messages with chatID
     * @param mixed $chatID
     * @param mixed $currentUserID
     * @return array
     */
    public static function getChatMessages($chatID, $currentUserID) {
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "SELECT m.*
                FROM messages m
                WHERE m.chat_id = :chat_id
                    AND EXISTS (
                        SELECT 1
                        FROM chat_participants cp
                        WHERE cp.chat_id = m.chat_id
                        AND cp.user_id = :current_user_id
                    )
                ORDER BY m.timestamp ASC, m.message_id ASC";

        $query = $database->prepare($sql);
        $query->execute(array(
            ':chat_id' => $chatID,
            ':current_user_id' => $currentUserID
        ));

        return $query->fetchAll();
    }

    public static function getOrCreateDirectChat($currentUserID, $otherUserID) {
        $existingChatID = self::getExistingDirectChatID($currentUserID, $otherUserID);

        if (!empty($existingChatID)) {
            return $existingChatID;
        }

        return self::createDirectChat($currentUserID, $otherUserID);
    }

    /**
     * Get existing direct chat id from two participants
     * @param mixed $currentUserID
     * @param mixed $otherUserID
     * @return int|null
     */
    private static function getExistingDirectChatID($currentUserID, $otherUserID) {
        $database = DatabaseFactory::getFactory()->getConnection();
        $directChatTypeID = self::getChatTypeID('direct');

        if (empty($directChatTypeID)) {
            return null;
        }

        $sql = "SELECT c.chat_id
                FROM chats c
                INNER JOIN chat_participants cp
                    ON cp.chat_id = c.chat_id
                WHERE c.chat_type = :direct_chat_type_id
                GROUP BY c.chat_id
                HAVING COUNT(DISTINCT cp.user_id) = 2
                AND SUM(cp.user_id = :current_user_id) = 1
                AND SUM(cp.user_id = :other_user_id) = 1
                LIMIT 1";

        $query = $database->prepare($sql);
        $query->execute(array(
            ':direct_chat_type_id' => $directChatTypeID,
            ':current_user_id' => $currentUserID,
            ':other_user_id' => $otherUserID
        ));

        $chat = $query->fetch();

        return $chat ? (int) $chat->chat_id : null;
    }

    private static function createDirectChat($currentUserID, $otherUserID) {
        $database = DatabaseFactory::getFactory()->getConnection();

        $otherUser = UserModel::getPublicProfileOfUser($otherUserID);
        if (!$otherUser) {
            return null;
        }

        $chatTypeID = self::getChatTypeID('direct');
        if (empty($chatTypeID)) {
            return null;
        }

        $database->beginTransaction();

        try {
            $sql = "INSERT INTO chats (chat_type, name)
                    VALUES (:chat_type, :chat_name)";

            $query = $database->prepare($sql);
            $chatWasCreated = $query->execute(array(
                ':chat_type' => $chatTypeID,
                ':chat_name' => $otherUser->user_name
            ));

            if (!$chatWasCreated || $query->rowCount() !== 1) {
                throw new RuntimeException('Could not create direct chat.');
            }

            $chatID = (int) $database->lastInsertId();

            $sql = "INSERT INTO chat_participants (chat_id, user_id, last_seen)
                    VALUES (:chat_id_current, :current_user_id, NOW()),
                           (:chat_id_other, :other_user_id, :initial_last_seen)";

            $query = $database->prepare($sql);
            $participantsWereCreated = $query->execute(array(
                ':chat_id_current' => $chatID,
                ':current_user_id' => $currentUserID,
                ':chat_id_other' => $chatID,
                ':other_user_id' => $otherUserID,
                ':initial_last_seen' => '2000-01-01 00:00:00'
            ));

            if (!$participantsWereCreated || $query->rowCount() !== 2) {
                throw new RuntimeException('Could not add chat participants.');
            }

            $database->commit();

            return $chatID;
        } catch (Exception $exception) {
            $database->rollBack();
            return null;
        }
    }

    public static function sendMessage($chatID, $currentUserID, $message) {
        $messageContent = trim((string) $message);
        
        if (empty($chatID) || $messageContent === '') {
            Session::add('feedback_negative', 'Message must not be empty!');
            return false;
        }

        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "SELECT 1
                FROM chat_participants
                WHERE chat_id = :chat_id
                    AND user_id = :user_id
                LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute(array(
            ':chat_id' => $chatID,
            ':user_id' => $currentUserID
        ));

        if (!$query->fetch()) {
            Session::add('feedback_negative', Text::get('FEEDBACK_UNKNOWN_ERROR'));
            return false;
        }

        $sql = "INSERT INTO messages (chat_id, sent_from_id, content, timestamp)
            VALUES (:chat_id, :sent_from_id, :content, NOW())";
        $query = $database->prepare($sql);
        $query->execute(array(
            ':chat_id' => $chatID,
            ':sent_from_id' => $currentUserID,
            ':content' => $messageContent,
        ));

        if ($query->rowCount() === 1) return true;

        Session::add('feedback_negative', Text::get('FEEDBACK_UNKNOWN_ERROR'));
        return false;
    }

    private static function getChatTypeID($typeName) {
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "SELECT type_id
                FROM chat_types
                WHERE type = :type_name
                LIMIT 1";

        $query = $database->prepare($sql);
        $query->execute(array(
            ':type_name' => $typeName
        ));

        $chatType = $query->fetch();

        return $chatType ? (int) $chatType->type_id : null;
    }

    private static function getDirectChatTypeID() {
        return self::getChatTypeID('direct');
    }
}