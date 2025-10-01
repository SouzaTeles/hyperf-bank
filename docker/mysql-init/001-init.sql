-- Initialize database and user for the application (idempotent on first boot)
CREATE DATABASE IF NOT EXISTS `hyperf-pix`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'hyperf'@'%' IDENTIFIED BY 'hyperf';
GRANT ALL PRIVILEGES ON `hyperf-pix`.* TO 'hyperf'@'%';
FLUSH PRIVILEGES;
