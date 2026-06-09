DELIMITER //

CREATE PROCEDURE sp_get_chats_of_user(
    IN p_current_user_id INT,
    IN p_direct_chat_type_id INT,
    IN p_group_chat_type_id INT
)
BEGIN
    SELECT *
    FROM (
        SELECT
            c.chat_id,
            u.user_id AS other_user_id,
            u.user_name AS chat_name,
            u.user_email,
            u.user_has_avatar,
            'direct' AS chat_type_name,
            (SELECT COUNT(*) FROM messages m 
             WHERE m.chat_id = c.chat_id 
             AND m.sent_from_id != p_current_user_id 
             AND (m.timestamp > cp_me.last_seen OR cp_me.last_seen IS NULL)
            ) AS unread_count
        FROM chats c
        INNER JOIN chat_participants cp_me
            ON cp_me.chat_id = c.chat_id
        INNER JOIN chat_participants cp_other
            ON cp_other.chat_id = c.chat_id
        INNER JOIN users u
            ON u.user_id = cp_other.user_id
        WHERE c.chat_type = p_direct_chat_type_id
        AND cp_me.user_id = p_current_user_id
        AND cp_other.user_id != p_current_user_id
        
        UNION ALL
        
        SELECT
            c.chat_id,
            NULL AS other_user_id,
            c.name AS chat_name,
            NULL AS user_email,
            0 AS user_has_avatar,
            'group' AS chat_type_name,
            (SELECT COUNT(*) FROM messages m 
             WHERE m.chat_id = c.chat_id 
             AND m.sent_from_id != p_current_user_id
             AND (m.timestamp > cp_me.last_seen OR cp_me.last_seen IS NULL)
            ) AS unread_count
        FROM chats c
        INNER JOIN chat_participants cp_me
            ON cp_me.chat_id = c.chat_id
        WHERE c.chat_type = p_group_chat_type_id
        AND cp_me.user_id = p_current_user_id
    ) chats_overview
    ORDER BY chat_id DESC;
END //

DELIMITER ;
