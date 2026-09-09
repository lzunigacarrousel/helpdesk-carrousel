-- Helpdesk Carrousel V2
-- Canal de colaboración externa separado de conversación pública e interna.
-- Incremental e idempotente para PC TEST.

USE helpdesk_carrousel_test;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS schema_migrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(120) NOT NULL UNIQUE,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

SET @comments_type := (
    SELECT COLUMN_TYPE FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='ticket_comments' AND COLUMN_NAME='visibility'
    LIMIT 1
);
SET @sql_comments := IF(
    @comments_type IS NOT NULL AND @comments_type NOT LIKE '%EXTERNAL%',
    "ALTER TABLE ticket_comments MODIFY visibility ENUM('PUBLIC','INTERNAL','EXTERNAL') NOT NULL DEFAULT 'PUBLIC'",
    'SELECT 1'
);
PREPARE stmt_comments FROM @sql_comments;
EXECUTE stmt_comments;
DEALLOCATE PREPARE stmt_comments;

SET @attachments_type := (
    SELECT COLUMN_TYPE FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='ticket_attachments' AND COLUMN_NAME='visibility'
    LIMIT 1
);
SET @sql_attachments := IF(
    @attachments_type IS NOT NULL AND @attachments_type NOT LIKE '%EXTERNAL%',
    "ALTER TABLE ticket_attachments MODIFY visibility ENUM('PUBLIC','INTERNAL','EXTERNAL') NOT NULL DEFAULT 'PUBLIC'",
    'SELECT 1'
);
PREPARE stmt_attachments FROM @sql_attachments;
EXECUTE stmt_attachments;
DEALLOCATE PREPARE stmt_attachments;

INSERT IGNORE INTO schema_migrations(version) VALUES('2026-09-09_conversaciones_v2');

SELECT 'OK - Conversaciones V2' AS resultado,
       (SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='ticket_comments' AND COLUMN_NAME='visibility' LIMIT 1) AS comentarios,
       (SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='ticket_attachments' AND COLUMN_NAME='visibility' LIMIT 1) AS adjuntos;
