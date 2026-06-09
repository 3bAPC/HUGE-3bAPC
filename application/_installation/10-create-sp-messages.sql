DELIMITER //

-- sp_get_chat_messages
DROP PROCEDURE IF EXISTS sp_get_chat_messages//
CREATE PROCEDURE sp_get_chat_messages(IN p_chat_id INT, IN p_current_user_id INT)
BEGIN
    SELECT m.*
    FROM messages m
    WHERE m.chat_id = p_chat_id
        AND EXISTS (
            SELECT 1
            FROM chat_participants cp
            WHERE cp.chat_id = m.chat_id
            AND cp.user_id = p_current_user_id
        )
    ORDER BY m.timestamp ASC, m.message_id ASC;
END //

-- sp_insert_message
DROP PROCEDURE IF EXISTS sp_insert_message//
CREATE PROCEDURE sp_insert_message(IN p_chat_id INT, IN p_sent_from_id INT, IN p_content TEXT)
BEGIN
    INSERT INTO messages (chat_id, sent_from_id, content, timestamp)
    VALUES (p_chat_id, p_sent_from_id, p_content, NOW());
END //

-- sp_update_last_seen
DROP PROCEDURE IF EXISTS sp_update_last_seen//
CREATE PROCEDURE sp_update_last_seen(IN p_chat_id INT, IN p_user_id INT)
BEGIN
    UPDATE chat_participants SET last_seen = NOW() 
    WHERE chat_id = p_chat_id AND user_id = p_user_id;
END //
DELIMITER ;