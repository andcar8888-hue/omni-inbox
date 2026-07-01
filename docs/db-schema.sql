CREATE TABLE businesses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(191) NOT NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(191) NOT NULL,
  email VARCHAR(191) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('owner','agent') NOT NULL DEFAULT 'agent',
  created_at DATETIME NOT NULL,
  FOREIGN KEY (business_id) REFERENCES businesses(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE channels (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  platform ENUM('whatsapp','messenger','instagram','telegram') NOT NULL,
  external_account_id VARCHAR(191) NOT NULL,  -- WA phone_number_id, page id, bot token id, etc
  credentials_encrypted TEXT NOT NULL,          -- never store tokens in plaintext
  created_at DATETIME NOT NULL,
  FOREIGN KEY (business_id) REFERENCES businesses(id),
  UNIQUE KEY uniq_channel (platform, external_account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE contacts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  channel_id BIGINT UNSIGNED NOT NULL,
  external_contact_id VARCHAR(191) NOT NULL,   -- WA wa_id, PSID, IG-scoped id, Telegram chat_id
  display_name VARCHAR(191) NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (channel_id) REFERENCES channels(id),
  UNIQUE KEY uniq_contact (channel_id, external_contact_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE conversations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  channel_id BIGINT UNSIGNED NOT NULL,
  contact_id BIGINT UNSIGNED NOT NULL,
  assigned_user_id BIGINT UNSIGNED NULL,
  status ENUM('open','pending','closed') NOT NULL DEFAULT 'open',
  last_message_at DATETIME NOT NULL,
  unread_count INT UNSIGNED NOT NULL DEFAULT 0,
  FOREIGN KEY (channel_id) REFERENCES channels(id),
  FOREIGN KEY (contact_id) REFERENCES contacts(id),
  FOREIGN KEY (assigned_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id BIGINT UNSIGNED NOT NULL,
  direction ENUM('inbound','outbound') NOT NULL,
  sender_user_id BIGINT UNSIGNED NULL,          -- set for outbound
  external_message_id VARCHAR(191) NULL,        -- platform's own message id, for dedupe
  body TEXT NULL,
  attachments_json JSON NULL,
  status ENUM('sent','delivered','read','failed') NOT NULL DEFAULT 'sent',
  created_at DATETIME NOT NULL,
  FOREIGN KEY (conversation_id) REFERENCES conversations(id),
  FOREIGN KEY (sender_user_id) REFERENCES users(id),
  UNIQUE KEY uniq_external_message (external_message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
