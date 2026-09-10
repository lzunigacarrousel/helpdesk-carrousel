-- Helpdesk Carrousel V2
-- FINALIZAR ESQUEMA ACTUAL PARA INSTALACION LIMPIA
-- Requisito: ejecutar inmediatamente despues de database/INSTALAR.sql
-- MariaDB 10.4+
-- No importa datos historicos ni usuarios de aplicaciones anteriores.

USE helpdesk_carrousel_test;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
-- CONTROL CANONICO DE VERSION DEL ESQUEMA
-- =========================================================
CREATE TABLE schema_migrations (
    version VARCHAR(150) NOT NULL PRIMARY KEY,
    name VARCHAR(180) NULL,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- ORGANIZACION ACTUAL
-- =========================================================
CREATE TABLE positions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(140) NOT NULL,
    description VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 100,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO positions(code,name,description,sort_order) VALUES
('PARK_USER','Colaborador de parque','Usuario operativo asignado a un parque',10),
('PARK_MANAGER','Encargado de parque','Responsable operativo de un parque',20),
('REGIONAL_SUPERVISOR','Supervisor regional','Responsable de una region o grupo de parques',30),
('MANAGEMENT','Gerencia','Responsable gerencial dentro de la estructura organizacional',40),
('ADMINISTRATION','Administracion','Personal administrativo',50),
('OPERATIONS','Operaciones','Personal de operaciones',60),
('MAINTENANCE','Mantenimiento','Personal de mantenimiento',70),
('TECHNOLOGY','Tecnologia','Personal de tecnologia / sistemas',80),
('OTHER','Otro','Otro puesto o funcion',99);

ALTER TABLE user_assignments
    ADD COLUMN region_id BIGINT UNSIGNED NULL AFTER user_id,
    ADD COLUMN position_id BIGINT UNSIGNED NULL AFTER area_id,
    ADD COLUMN assignment_type ENUM('PARK','CORPORATE','OTHER') NOT NULL DEFAULT 'OTHER' AFTER position_id,
    ADD CONSTRAINT fk_assign_region FOREIGN KEY(region_id) REFERENCES regions(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_assign_position FOREIGN KEY(position_id) REFERENCES positions(id) ON DELETE SET NULL,
    ADD INDEX idx_assign_region_status(region_id,status),
    ADD INDEX idx_assign_position(position_id);

INSERT INTO regions(code,name,is_active) VALUES
('CC_REGION_6','Sur Occidente',1),
('CC_REGION_7','Nor Oriente',1),
('CC_REGION_8','Central',1),
('CC_REGION_9','Central Alianzas',1);

INSERT INTO areas(code,name,description,is_active) VALUES
('MANAGEMENT','Gerencia','Area gerencial',1),
('MAINTENANCE','Mantenimiento','Mantenimiento y soporte operativo',1)
ON DUPLICATE KEY UPDATE name=VALUES(name),description=VALUES(description),is_active=1;

-- =========================================================
-- FLUJO ACTUAL DE TICKETS
-- =========================================================
ALTER TABLE tickets
    ADD COLUMN pending_reason_code VARCHAR(50) NULL AFTER status,
    ADD COLUMN pending_note VARCHAR(500) NULL AFTER pending_reason_code;

ALTER TABLE ticket_comments
    MODIFY visibility ENUM('PUBLIC','INTERNAL','EXTERNAL') NOT NULL DEFAULT 'PUBLIC';

ALTER TABLE ticket_attachments
    MODIFY visibility ENUM('PUBLIC','INTERNAL','EXTERNAL') NOT NULL DEFAULT 'PUBLIC';

CREATE TABLE ticket_resolutions (
    ticket_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    resolution_type VARCHAR(60) NOT NULL DEFAULT 'OTHER',
    root_cause TEXT NULL,
    solution_applied TEXT NOT NULL,
    preventive_action TEXT NULL,
    is_reusable TINYINT(1) NOT NULL DEFAULT 1,
    resolved_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_resolution_ticket FOREIGN KEY(ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_resolution_user FOREIGN KEY(resolved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_resolution_type(resolution_type),
    INDEX idx_resolution_reusable(is_reusable),
    INDEX idx_resolution_user(resolved_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ticket_feedback (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    requester_user_id BIGINT UNSIGNED NULL,
    nps_score TINYINT UNSIGNED NOT NULL,
    comment VARCHAR(1000) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ticket_feedback_ticket(ticket_id),
    KEY idx_ticket_feedback_nps(nps_score),
    KEY idx_ticket_feedback_created(created_at),
    KEY idx_ticket_feedback_requester(requester_user_id),
    CONSTRAINT fk_feedback_ticket FOREIGN KEY(ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_feedback_requester FOREIGN KEY(requester_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_ticket_feedback_nps CHECK (nps_score BETWEEN 0 AND 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TRIGGER IF EXISTS trg_tickets_require_resolution;
DELIMITER $$
CREATE TRIGGER trg_tickets_require_resolution
BEFORE UPDATE ON tickets
FOR EACH ROW
BEGIN
    IF NEW.status IN ('RESOLVED','CLOSED')
       AND OLD.status <> NEW.status
       AND NOT EXISTS (SELECT 1 FROM ticket_resolutions tr WHERE tr.ticket_id=NEW.id)
    THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Debe registrar la solucion antes de finalizar el caso';
    END IF;
END$$
DELIMITER ;

-- =========================================================
-- COLABORADORES EXTERNOS
-- =========================================================
CREATE TABLE external_profiles (
    user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    organization_name VARCHAR(190) NOT NULL,
    external_type ENUM('PROVIDER','PARTNER','OTHER') NOT NULL DEFAULT 'PROVIDER',
    notes VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_external_profile_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_external_profiles_org(organization_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- NOTIFICACIONES ACTUALES
-- =========================================================
ALTER TABLE notification_deliveries
    MODIFY channel ENUM('EMAIL','IN_APP') NOT NULL DEFAULT 'EMAIL',
    ADD COLUMN title VARCHAR(180) NULL AFTER recipient_email,
    ADD COLUMN message VARCHAR(500) NULL AFTER title,
    ADD COLUMN action_url VARCHAR(500) NULL AFTER message,
    ADD COLUMN read_at DATETIME NULL AFTER sent_at,
    ADD INDEX idx_nd_user_channel_read(recipient_user_id,channel,read_at,created_at);

-- =========================================================
-- PERMISOS ACTUALES
-- =========================================================
INSERT INTO permissions(code,name,module,description) VALUES
('tickets.resolve','Documentar y resolver tickets','tickets','Permite registrar causa, solucion y marcar un caso como resuelto.'),
('management.view','Ver dashboard de gestion','MANAGEMENT','Permite consultar metricas internas del Helpdesk.'),
('external.manage','Gestionar usuarios externos','EXTERNAL','Permite crear colaboradores externos y compartir casos especiales.')
ON DUPLICATE KEY UPDATE name=VALUES(name),module=VALUES(module),description=VALUES(description);

-- Administrador: todos los permisos existentes y nuevos.
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p WHERE r.code='ADMIN';

-- Semiadministrador: operacion amplia y gestion.
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.code='SEMIADMIN' AND p.code IN(
    'tickets.view_own','tickets.view_queue','tickets.claim','tickets.reassign','tickets.change_status',
    'tickets.comment_public','tickets.comment_internal','tickets.view_all','tickets.manage_special','tickets.resolve',
    'users.view','assignments.view','assignments.manage','catalogs.manage','reports.view','reports.global',
    'management.view','external.manage','problems.view','problems.manage','knowledge.view','knowledge.manage','sla.manage'
);

-- Tecnico: atencion de tickets y consulta operativa.
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.code='TECHNICIAN' AND p.code IN(
    'tickets.view_own','tickets.view_queue','tickets.claim','tickets.change_status','tickets.resolve',
    'tickets.comment_public','tickets.comment_internal','reports.view','problems.view','knowledge.view'
);

-- Gerencia y Supervisor son perfiles vigentes de consulta; no se convierten en solicitante.
UPDATE roles SET is_active=1,name='Gerencia',description='Consulta ejecutiva global, indicadores, informes y conocimiento; no atiende solicitudes.' WHERE code='MANAGEMENT';
UPDATE roles SET is_active=1,name='Supervisor',description='Consulta y seguimiento dentro de su region, parque o area; no atiende solicitudes.' WHERE code='SUPERVISOR';

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.code='MANAGEMENT' AND p.code IN('management.view','reports.view','reports.global','users.view','assignments.view','problems.view','knowledge.view');

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.code='SUPERVISOR' AND p.code IN('management.view','reports.view','users.view','assignments.view','problems.view','knowledge.view');

DELETE rp FROM role_permissions rp
JOIN roles r ON r.id=rp.role_id
JOIN permissions p ON p.id=rp.permission_id
WHERE r.code IN('MANAGEMENT','SUPERVISOR')
  AND p.code IN(
    'tickets.view_queue','tickets.claim','tickets.reassign','tickets.change_status','tickets.resolve',
    'tickets.comment_public','tickets.comment_internal','tickets.manage_special','tickets.view_all',
    'users.manage','assignments.manage','catalogs.manage','sla.manage','external.manage',
    'knowledge.manage','problems.manage','audit.view'
  );

-- Equipo IT: membresia operativa explicita y alcance global inicial.
SET @it_team_id := (SELECT id FROM support_teams WHERE code='IT' LIMIT 1);
INSERT IGNORE INTO support_scopes(team_id,park_id,area_id,scope_type,is_active)
SELECT @it_team_id,NULL,NULL,'GLOBAL',1 WHERE @it_team_id IS NOT NULL;

INSERT INTO support_team_members(team_id,user_id,is_active,joined_at,ended_at)
SELECT @it_team_id,u.id,1,NOW(),NULL
FROM users u JOIN roles r ON r.id=u.role_id
WHERE @it_team_id IS NOT NULL
  AND u.deleted_at IS NULL
  AND u.access_type='INTERNAL'
  AND r.code IN('ADMIN','SEMIADMIN','TECHNICIAN')
ON DUPLICATE KEY UPDATE is_active=1,ended_at=NULL;

-- Normalizacion del codigo Semnox.
UPDATE ticket_categories SET code='SEMNOX',updated_at=NOW() WHERE LOWER(code)='semnox';

INSERT INTO schema_migrations(version,name) VALUES
('2026-09-09-clean-schema-v2.4','Esquema canonico Helpdesk Carrousel para instalacion limpia');

SELECT 'OK - ESQUEMA FINAL V2 PREPARADO' AS resultado;
