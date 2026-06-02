-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               11.6.2-MariaDB - mariadb.org binary distribution
-- Server OS:                    Win64
-- HeidiSQL Version:             12.14.0.7165
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for huge
CREATE DATABASE IF NOT EXISTS `huge` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci */;
USE `huge`;

-- Dumping structure for table huge.messages
CREATE TABLE IF NOT EXISTS `messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `chat_id` int(11) NOT NULL DEFAULT 0,
  `sent_from_id` int(11) NOT NULL DEFAULT 0,
  `content` varchar(1500) NOT NULL,
  `timestamp` timestamp NOT NULL,
  PRIMARY KEY (`message_id`) USING BTREE,
  KEY `sent_from_id` (`sent_from_id`) USING BTREE,
  KEY `chat_id` (`chat_id`) USING BTREE,
  CONSTRAINT `fk_chat_id_chats_id` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`chat_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_sent_from_id_users_id` FOREIGN KEY (`sent_from_id`) REFERENCES `users` (`user_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='This table is for each message sent';

-- Dumping data for table huge.messages: ~5 rows (approximately)
DELETE FROM `messages`;
INSERT INTO `messages` (`message_id`, `chat_id`, `sent_from_id`, `content`, `timestamp`) VALUES
	(1, 1, 4, 'this is for testing purposes', '2026-06-02 11:10:20'),
	(2, 1, 1, 'Yes', '2026-06-02 11:39:10'),
	(3, 1, 1, 'Test from User 1', '2026-06-02 11:35:19'),
	(4, 1, 4, 'Testing from U4', '2026-06-02 11:35:40'),
	(5, 1, 1, 'Old Message?', '2026-06-02 11:35:20');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
