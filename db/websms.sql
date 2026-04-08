DROP TABLE IF EXISTS `sms_messages`;

CREATE TABLE `sms_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sender_name` VARCHAR(120) DEFAULT NULL,
  `sender_number` VARCHAR(40) NOT NULL,
  `receiver_number` VARCHAR(40) DEFAULT NULL,
  `message_text` TEXT NOT NULL,
  `status` ENUM('received', 'processed', 'failed') NOT NULL DEFAULT 'received',
  `gateway_payload` LONGTEXT DEFAULT NULL,
  `received_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sms_messages_received_at` (`received_at`),
  KEY `idx_sms_messages_sender_number` (`sender_number`),
  KEY `idx_sms_messages_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sms_messages` (`sender_name`, `sender_number`, `receiver_number`, `message_text`, `status`, `received_at`) VALUES
  ('Alice Logistics', '+1555001001', '+1555099999', 'Pickup confirmed for order #4831. Driver will arrive in 20 minutes.', 'received', NOW() - INTERVAL 2 HOUR),
  ('Marketing Team', '+1555001002', '+1555099999', 'Promo SMS batch sent to 3,200 subscribers. Delivery report is ready.', 'processed', NOW() - INTERVAL 3 HOUR),
  ('John Doe', '+1555001003', '+1555099999', 'Please resend my verification code. The previous message did not arrive.', 'failed', NOW() - INTERVAL 1 DAY),
  ('System Alerts', '+1555001004', '+1555099999', 'Gateway backup activated successfully. No interruption detected across outbound messages.', 'processed', NOW() - INTERVAL 1 DAY - INTERVAL 2 HOUR);