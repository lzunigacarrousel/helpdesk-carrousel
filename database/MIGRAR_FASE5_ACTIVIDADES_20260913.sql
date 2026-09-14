USE carrousel_helpdesk;
SET NAMES utf8mb4;

-- =========================================================
-- FASE 5 - ACTIVIDADES / VISITAS
-- Migracion incremental idempotente para PC TEST.
-- =========================================================

CREATE TABLE IF NOT EXISTS ticket_activities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    activity_type ENUM('VISITA_EN_SITIO','SOPORTE_REMOTO','SEGUIMIENTO','INTERVENCION_PROVEEDOR','OTRA') NOT NULL,
    status ENUM('PROGRAMADA','EN_CURSO','FINALIZADA','CANCELADA') NOT NULL DEFAULT 'PROGRAMADA',
    responsible_user_id BIGINT UNSIGNED NOT NULL,
    provider_user_id BIGINT UNSIGNED NULL,
    park_id BIGINT UNSIGNED NULL,
    is_remote TINYINT(1) NOT NULL DEFAULT 0,
    objective TEXT NOT NULL,
    internal_preparation_notes TEXT NULL,
    scheduled_start_at DATETIME NOT NULL,
    scheduled_end_at DATETIME NOT NULL,
    started_at DATETIME NULL,
    finished_at DATETIME NULL,
    result_code ENUM('RESUELTA','PARCIAL','SIN_RESOLVER','REQUIERE_SEGUIMIENTO') NULL,
    work_performed TEXT NULL,
    result_summary TEXT NULL,
    pending_items TEXT NULL,
    requester_visible TINYINT(1) NOT NULL DEFAULT 0,
    requester_summary VARCHAR(500) NULL,
    cancel_reason VARCHAR(500) NULL,
    cancelled_at DATETIME NULL,
    cancelled_by BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ta_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_ta_responsible FOREIGN KEY (responsible_user_id) REFERENCES users(id),
    CONSTRAINT fk_ta_provider FOREIGN KEY (provider_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_ta_park FOREIGN KEY (park_id) REFERENCES parks(id) ON DELETE SET NULL,
    CONSTRAINT fk_ta_cancelled_by FOREIGN KEY (cancelled_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_ta_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_ta_ticket_status_schedule (ticket_id,status,scheduled_start_at),
    INDEX idx_ta_responsible_status_schedule (responsible_user_id,status,scheduled_start_at),
    INDEX idx_ta_park_status_schedule (park_id,status,scheduled_start_at),
    INDEX idx_ta_provider_status (provider_user_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ticket_activity_participants (
    activity_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (activity_id,user_id),
    CONSTRAINT fk_tap_activity FOREIGN KEY (activity_id) REFERENCES ticket_activities(id) ON DELETE CASCADE,
    CONSTRAINT fk_tap_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_tap_created_by FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE ticket_attachments
    ADD COLUMN IF NOT EXISTS activity_id BIGINT UNSIGNED NULL AFTER comment_id;

SET @has_activity_index := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA=DATABASE()
      AND TABLE_NAME='ticket_attachments'
      AND INDEX_NAME='idx_ticket_attachments_activity'
);
SET @sql := IF(
    @has_activity_index=0,
    'ALTER TABLE ticket_attachments ADD INDEX idx_ticket_attachments_activity (activity_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_activity_fk := (
    SELECT COUNT(*)
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA=DATABASE()
      AND TABLE_NAME='ticket_attachments'
      AND CONSTRAINT_NAME='fk_ticket_attachment_activity'
);
SET @sql := IF(
    @has_activity_fk=0,
    'ALTER TABLE ticket_attachments ADD CONSTRAINT fk_ticket_attachment_activity FOREIGN KEY (activity_id) REFERENCES ticket_activities(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO permissions(code,name,module,description) VALUES
('activities.view','Ver actividades','activities','Consulta actividades operativas dentro del alcance'),
('activities.create','Programar actividades','activities','Crear actividades ligadas a tickets'),
('activities.manage','Gestionar actividades','activities','Reprogramar, iniciar, finalizar y administrar participantes'),
('activities.cancel','Cancelar actividades','activities','Cancelar actividades con motivo auditable')
ON DUPLICATE KEY UPDATE
    name=VALUES(name),
    module=VALUES(module),
    description=VALUES(description);

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id
FROM roles r
JOIN permissions p ON p.code IN('activities.view','activities.create','activities.manage','activities.cancel')
WHERE r.code IN('ADMIN','SEMIADMIN','TECHNICIAN');

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id
FROM roles r
JOIN permissions p ON p.code='activities.view'
WHERE r.code IN('MANAGEMENT','SUPERVISOR');

INSERT IGNORE INTO schema_migrations(version,name,applied_at)
VALUES('2026-09-13-fase5-actividades','Fase 5 - Actividades y visitas',NOW());

SELECT 'OK - FASE 5 ACTIVIDADES / VISITAS MIGRADA' AS resultado;
