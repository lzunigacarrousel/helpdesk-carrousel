-- Helpdesk Carrousel V2
-- Notificaciones operativas internas + trazabilidad de correo
-- Incremental, no borra datos.
USE helpdesk_carrousel_test;

CREATE TABLE IF NOT EXISTS notification_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_key VARCHAR(100) NOT NULL,
    ticket_id BIGINT UNSIGNED NULL,
    problem_id BIGINT UNSIGNED NULL,
    actor_user_id BIGINT UNSIGNED NULL,
    payload_json LONGTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ne_event_created (event_key, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notification_deliveries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,
    channel ENUM('EMAIL','IN_APP') NOT NULL DEFAULT 'EMAIL',
    recipient_user_id BIGINT UNSIGNED NULL,
    recipient_email VARCHAR(190) NOT NULL,
    title VARCHAR(180) NULL,
    message VARCHAR(500) NULL,
    action_url VARCHAR(500) NULL,
    status ENUM('PENDING','SENT','FAILED','SKIPPED') NOT NULL DEFAULT 'PENDING',
    attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    last_error TEXT NULL,
    sent_at DATETIME NULL,
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nd_status_created (status, created_at),
    INDEX idx_nd_user_channel_read (recipient_user_id,channel,read_at,created_at)
) ENGINE=InnoDB;

ALTER TABLE notification_deliveries
    MODIFY channel ENUM('EMAIL','IN_APP') NOT NULL DEFAULT 'EMAIL';

ALTER TABLE notification_deliveries
    ADD COLUMN IF NOT EXISTS title VARCHAR(180) NULL AFTER recipient_email,
    ADD COLUMN IF NOT EXISTS message VARCHAR(500) NULL AFTER title,
    ADD COLUMN IF NOT EXISTS action_url VARCHAR(500) NULL AFTER message,
    ADD COLUMN IF NOT EXISTS read_at DATETIME NULL AFTER sent_at;

ALTER TABLE notification_deliveries
    ADD INDEX IF NOT EXISTS idx_nd_user_channel_read (recipient_user_id,channel,read_at,created_at);

CREATE TABLE IF NOT EXISTS schema_migrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration_key VARCHAR(150) NOT NULL UNIQUE,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO schema_migrations(migration_key,applied_at)
VALUES('20260909_notifications_events_v2',NOW());

SELECT 'OK - Notificaciones operativas V2' resultado,
       (SELECT COUNT(*) FROM notification_events) eventos,
       (SELECT COUNT(*) FROM notification_deliveries WHERE channel='IN_APP') notificaciones_internas,
       (SELECT COUNT(*) FROM schema_migrations) migraciones_registradas;
