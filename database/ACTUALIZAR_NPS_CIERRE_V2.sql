-- Helpdesk Carrousel V2
-- Confirmación del solicitante, NPS y cierre posterior a resolución.
-- Incremental e idempotente para PC TEST.

USE helpdesk_carrousel_test;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS schema_migrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(120) NOT NULL UNIQUE,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ticket_feedback (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    requester_user_id BIGINT UNSIGNED NULL,
    nps_score TINYINT UNSIGNED NOT NULL,
    comment VARCHAR(1000) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ticket_feedback_ticket (ticket_id),
    KEY idx_ticket_feedback_nps (nps_score),
    KEY idx_ticket_feedback_created (created_at),
    CONSTRAINT chk_ticket_feedback_nps CHECK (nps_score BETWEEN 0 AND 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations(version) VALUES('2026-09-09_nps_cierre_v2');

SELECT 'OK - NPS y cierre V2' AS resultado,
       (SELECT COUNT(*) FROM ticket_feedback) AS respuestas,
       (SELECT COUNT(*) FROM schema_migrations WHERE version='2026-09-09_nps_cierre_v2') AS migracion_registrada;
