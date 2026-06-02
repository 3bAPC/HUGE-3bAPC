<?php

class ChatModel {

    /**
     * Get all direct chats of the user existing
     * @param mixed $currentUserID
     * @return array
     */
    public static function getDirectChatsOfUser($currentUserID) {
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "SELECT
                    c.chat_id,
                    u.user_id AS other_user_id,
                    u.user_name AS chat_name,
                    u.user_email,
                    u.user_has_avatar
                FROM chats c
                INNER JOIN chat_participants cp_me
                    ON cp_me.chat_id = c.chat_id
                INNER JOIN chat_participants cp_other
                    ON cp_other.chat_id = c.chat_id
                INNER JOIN users u
                    ON u.user_id = cp_other.user_id
                WHERE c.chat_type = 1
                AND cp_me.user_id = :current_user_id
                AND cp_other.user_id != :not_current_user_id
                ORDER BY c.chat_id DESC";

        $query = $database->prepare($sql);
        $query->execute(array(
            ':current_user_id' => $currentUserID,
            ':not_current_user_id' => $currentUserID
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

        $sql = "SELECT c.chat_id
                FROM chats c
                INNER JOIN chat_participants cp1
                    ON cp1.chat_id = c.chat_id
                INNER JOIN chat_participants cp2
                    ON cp2.chat_id = c.chat_id
                WHERE c.chat_type = 1
                AND cp1.user_id = :current_user_id
                AND cp2.user_id = :other_user_id
                LIMIT 1";

        $query = $database->prepare($sql);
        $query->execute(array(
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
            $query->execute(array(
                ':chat_type' => $chatTypeID,
                ':chat_name' => $otherUser->user_name
            ));

            $chatID = (int) $database->lastInsertId();

            $sql = "INSERT INTO chat_participants (chat_id, user_id)
                    VALUES (:chat_id_current, :current_user_id),
                           (:chat_id_other, :other_user_id)";

            $query = $database->prepare($sql);
            $query->execute(array(
                ':chat_id_current' => $chatID,
                ':current_user_id' => $currentUserID,
                ':chat_id_other' => $chatID,
                ':other_user_id' => $otherUserID
            ));

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

        $sql = "INSERT INTO messages (chat_id, sent_from_id, content)
                VALUES (:chat_id, :sent_from_id, :content)";
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
}