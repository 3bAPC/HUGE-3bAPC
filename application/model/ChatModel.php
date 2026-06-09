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
    public static function getChatsOfUser($currentUserID) {
        $database = DatabaseFactory::getFactory()->getConnection();
        $directChatTypeID = self::getDirectChatTypeID();
        $groupChatTypeID = self::getGroupChatTypeID();

        if (empty($directChatTypeID) || empty($groupChatTypeID)) {
            return array();
        }

        $sql = "CALL sp_get_chats_of_user(:current_user_id, :direct_chat_type_id, :group_chat_type_id)";

        $query = $database->prepare($sql);
        $query->execute(array(
            ':current_user_id' => $currentUserID,
            ':direct_chat_type_id' => $directChatTypeID,
            ':group_chat_type_id' => $groupChatTypeID
        ));

        $chats = $query->fetchAll();

        foreach ($chats as $chat) {
            $chat->chat_avatar_link = null;

            if ($chat->chat_type_name === 'direct') {
                $chat->chat_avatar_link = Config::get('USE_GRAVATAR')
                    ? AvatarModel::getGravatarLinkByEmail($chat->user_email)
                    : AvatarModel::getPublicAvatarFilePathOfUser($chat->user_has_avatar, $chat->other_user_id);
            }
        }

        return $chats;
    }

    public static function createGroupChat($currentUserID, $groupName, array $participantIDs) {
        $groupName = trim((string) $groupName);

        if ($groupName === '') {
            Session::add('feedback_negative', 'Group name must not be empty.');
            return null;
        }

        $participantIDs = array_values(array_unique(array_filter(array_map('intval', $participantIDs))));
        $participantIDs = array_values(array_filter($participantIDs, function ($participantID) use ($currentUserID) {
            return $participantID > 0 && $participantID !== (int) $currentUserID;
        }));

        if (empty($participantIDs)) {
            Session::add('feedback_negative', 'Please select at least one user for the group chat.');
            return null;
        }

        $database = DatabaseFactory::getFactory()->getConnection();
        $groupChatTypeID = self::getGroupChatTypeID();

        if (empty($groupChatTypeID)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_UNKNOWN_ERROR'));
            return null;
        }

        $validParticipantIDs = self::getExistingUserIDs($participantIDs);

        if (count($validParticipantIDs) !== count($participantIDs)) {
            Session::add('feedback_negative', 'One or more selected users could not be added to the group chat.');
            return null;
        }

        $database->beginTransaction();

        try {
            $sql = "CALL sp_create_chat(:chat_type, :chat_name)";

            $query = $database->prepare($sql);
            $chatWasCreated = $query->execute(array(
                ':chat_type' => $groupChatTypeID,
                ':chat_name' => $groupName
            ));

            $result = $query->fetch();

            if (!$chatWasCreated || !$result || empty($result->chat_id)) {
                throw new RuntimeException('Could not create group chat.');
            }

            $chatID = (int) $result->chat_id;
            $query->closeCursor();

            $participantRows = array_merge(array((int) $currentUserID), $validParticipantIDs);

            $valuePlaceholders = array();
            $parameters = array();

            foreach ($participantRows as $index => $participantID) {
                $valuePlaceholders[] = "(:chat_id_{$index}, :user_id_{$index}, :last_seen_{$index})";
                $parameters[":chat_id_{$index}"] = $chatID;
                $parameters[":user_id_{$index}"] = $participantID;
                $parameters[":last_seen_{$index}"] = ($participantID === (int) $currentUserID)
                    ? date('Y-m-d H:i:s')
                    : '2000-01-01 00:00:00';
            }

            // Note: Kept dynamic multi-insert as standard SQL since SP arrays are not natively supported
            $sql = "INSERT INTO chat_participants (chat_id, user_id, last_seen)
                    VALUES " . implode(', ', $valuePlaceholders);
            $query = $database->prepare($sql);
            $participantsWereCreated = $query->execute($parameters);

            if (!$participantsWereCreated || $query->rowCount() !== count($participantRows)) {
                throw new RuntimeException('Could not add group chat participants.');
            }

            $database->commit();
            Session::add('feedback_positive', 'Group chat created successfully.');

            return $chatID;
        } catch (Exception $exception) {
            $database->rollBack();
            Session::add('feedback_negative', Text::get('FEEDBACK_UNKNOWN_ERROR'));
            return null;
        }
    }

    /**
     * Updates the last_seen timestamp for a specific user in a specific chat to the current time.
     * 
     * @param int $chatID
     * @param int $userID
     */
    public static function updateLastSeen($chatID, $userID) {
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "CALL sp_update_last_seen(:chat_id, :user_id)";
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

        $sql = "CALL sp_get_chat_messages(:chat_id, :current_user_id)";

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

        $sql = "CALL sp_get_existing_direct_chat_id(:direct_chat_type_id, :current_user_id, :other_user_id)";

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

        try {
            $sql = "CALL sp_create_direct_chat(:chat_type, :chat_name, :current_user_id, :other_user_id)";
            $query = $database->prepare($sql);
            $query->execute(array(
                ':chat_type' => $chatTypeID,
                ':chat_name' => $otherUser->user_name,
                ':current_user_id' => $currentUserID,
                ':other_user_id' => $otherUserID
            ));

            $result = $query->fetch();
            if ($result && isset($result->chat_id)) {
                return (int) $result->chat_id;
            }

            return null;
        } catch (Exception $exception) {
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

        $sql = "CALL sp_check_chat_participant(:chat_id, :user_id)";
        $query = $database->prepare($sql);
        $query->execute(array(
            ':chat_id' => $chatID,
            ':user_id' => $currentUserID
        ));

        if (!$query->fetch()) {
            Session::add('feedback_negative', Text::get('FEEDBACK_UNKNOWN_ERROR'));
            return false;
        }
        $query->closeCursor();

        $sql = "CALL sp_insert_message(:chat_id, :sent_from_id, :content)";
        $query = $database->prepare($sql);
        $success = $query->execute(array(
            ':chat_id' => $chatID,
            ':sent_from_id' => $currentUserID,
            ':content' => $messageContent,
        ));

        if ($success) return true;

        Session::add('feedback_negative', Text::get('FEEDBACK_UNKNOWN_ERROR'));
        return false;
    }

    private static function getChatTypeID($typeName) {
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "CALL sp_get_chat_type_id(:type_name)";

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

    private static function getGroupChatTypeID() {
        return self::getChatTypeID('group');
    }

    private static function getExistingUserIDs(array $userIDs) {
        if (empty($userIDs)) {
            return array();
        }

        $database = DatabaseFactory::getFactory()->getConnection();
        $placeholders = array();
        $parameters = array();

        foreach ($userIDs as $index => $userID) {
            $placeholder = ':user_id_' . $index;
            $placeholders[] = $placeholder;
            $parameters[$placeholder] = $userID;
        }

        // Kept as dynamic standard SQL as FIND_IN_SET in SP performs poorly with indexes
        $sql = "SELECT user_id
                FROM users
                WHERE user_id IN (" . implode(', ', $placeholders) . ")";

        $query = $database->prepare($sql);
        $query->execute($parameters);

        return array_map('intval', $query->fetchAll(PDO::FETCH_COLUMN));
    }
}
