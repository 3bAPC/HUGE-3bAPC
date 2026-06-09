DELIMITER //

-- sp_create_chat
DROP PROCEDURE IF EXISTS sp_create_chat//
CREATE PROCEDURE sp_create_chat(IN p_chat_type INT, IN p_chat_name VARCHAR(255))
BEGIN
    INSERT INTO chats (chat_type, name)
    VALUES (p_chat_type, p_chat_name);
    SELECT LAST_INSERT_ID() AS chat_id;
END //

-- sp_create_direct_chat
DROP PROCEDURE IF EXISTS sp_create_direct_chat//
CREATE PROCEDURE sp_create_direct_chat(IN p_chat_type INT, IN p_chat_name VARCHAR(255), IN p_current_user_id INT, IN p_other_user_id INT)
BEGIN
    DECLARE v_chat_id INT;
    
    START TRANSACTION;
    
    INSERT INTO chats (chat_type, name)
    VALUES (p_chat_type, p_chat_name);
    
    SET v_chat_id = LAST_INSERT_ID();
    
    INSERT INTO chat_participants (chat_id, user_id, last_seen)
    VALUES (v_chat_id, p_current_user_id, NOW()),
           (v_chat_id, p_other_user_id, '2000-01-01 00:00:00');
           
    COMMIT;
    
    SELECT v_chat_id AS chat_id;
END //

-- sp_get_existing_direct_chat_id
DROP PROCEDURE IF EXISTS sp_get_existing_direct_chat_id//
CREATE PROCEDURE sp_get_existing_direct_chat_id(IN p_direct_chat_type_id INT, IN p_current_user_id INT, IN p_other_user_id INT)
BEGIN
    SELECT c.chat_id
    FROM chats c
    INNER JOIN chat_participants cp
        ON cp.chat_id = c.chat_id
    WHERE c.chat_type = p_direct_chat_type_id
    GROUP BY c.chat_id
    HAVING COUNT(DISTINCT cp.user_id) = 2
    AND SUM(cp.user_id = p_current_user_id) = 1
    AND SUM(cp.user_id = p_other_user_id) = 1
    LIMIT 1;
END //

-- sp_check_chat_participant
DROP PROCEDURE IF EXISTS sp_check_chat_participant//
CREATE PROCEDURE sp_check_chat_participant(IN p_chat_id INT, IN p_user_id INT)
BEGIN
    SELECT 1
    FROM chat_participants
    WHERE chat_id = p_chat_id
        AND user_id = p_user_id
    LIMIT 1;
END //

-- sp_get_chat_type_id
DROP PROCEDURE IF EXISTS sp_get_chat_type_id//
CREATE PROCEDURE sp_get_chat_type_id(IN p_type_name VARCHAR(50))
BEGIN
    SELECT type_id
    FROM chat_types
    WHERE type = p_type_name
    LIMIT 1;
END //

DELIMITER ;