-- Helpdesk Carrousel V2
-- Verificacion estricta de una instalacion limpia actual.
-- Devuelve error SQL si falta una tabla/columna esencial o aparece una tabla no canonica.

USE helpdesk_carrousel_test;
SET NAMES utf8mb4;

DROP TEMPORARY TABLE IF EXISTS required_tables;
CREATE TEMPORARY TABLE required_tables(name VARCHAR(100) PRIMARY KEY);
INSERT INTO required_tables(name) VALUES
('roles'),('permissions'),('users'),('role_permissions'),('user_permission_overrides'),
('otp_codes'),('user_sessions'),('regions'),('parks'),('areas'),('positions'),('user_assignments'),
('support_teams'),('support_team_members'),('support_scopes'),('ticket_categories'),('sla_policies'),
('tickets'),('ticket_events'),('ticket_comments'),('ticket_attachments'),('external_ticket_access'),
('external_profiles'),('ticket_resolutions'),('ticket_feedback'),('known_problems'),('problem_occurrences'),
('problem_tags'),('known_problem_tags'),('knowledge_articles'),('problem_solutions'),('problem_attachments'),
('notification_events'),('notification_deliveries'),('audit_logs'),('schema_migrations');

DROP TEMPORARY TABLE IF EXISTS required_columns;
CREATE TEMPORARY TABLE required_columns(table_name VARCHAR(100),column_name VARCHAR(100),PRIMARY KEY(table_name,column_name));
INSERT INTO required_columns(table_name,column_name) VALUES
('users','full_name'),('users','access_type'),('users','status'),('users','email_verified_at'),
('user_assignments','region_id'),('user_assignments','position_id'),('user_assignments','assignment_type'),('user_assignments','manager_user_id'),
('tickets','requester_user_id'),('tickets','requester_email'),('tickets','status'),('tickets','pending_reason_code'),('tickets','pending_note'),
('tickets','resolution_due_at'),('ticket_comments','visibility'),('ticket_attachments','visibility'),
('ticket_resolutions','solution_applied'),('ticket_resolutions','resolved_by'),
('ticket_feedback','nps_score'),('external_profiles','organization_name'),
('notification_deliveries','channel'),('notification_deliveries','title'),('notification_deliveries','message'),
('notification_deliveries','action_url'),('notification_deliveries','read_at'),
('schema_migrations','version'),('schema_migrations','name'),('schema_migrations','applied_at');

DROP PROCEDURE IF EXISTS verify_clean_helpdesk;
DELIMITER $$
CREATE PROCEDURE verify_clean_helpdesk()
BEGIN
    DECLARE missing_tables INT DEFAULT 0;
    DECLARE unexpected_tables INT DEFAULT 0;
    DECLARE missing_columns INT DEFAULT 0;
    DECLARE bad_parks INT DEFAULT 0;
    DECLARE bad_roles INT DEFAULT 0;
    DECLARE missing_permissions INT DEFAULT 0;
    DECLARE missing_admin INT DEFAULT 0;
    DECLARE missing_trigger INT DEFAULT 0;

    SELECT COUNT(*) INTO missing_tables
    FROM required_tables r
    LEFT JOIN information_schema.TABLES t
      ON t.TABLE_SCHEMA=DATABASE() AND t.TABLE_NAME=r.name AND t.TABLE_TYPE='BASE TABLE'
    WHERE t.TABLE_NAME IS NULL;

    IF missing_tables > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Instalacion invalida: faltan tablas canonicas';
    END IF;

    SELECT COUNT(*) INTO unexpected_tables
    FROM information_schema.TABLES t
    LEFT JOIN required_tables r ON r.name=t.TABLE_NAME
    WHERE t.TABLE_SCHEMA=DATABASE() AND t.TABLE_TYPE='BASE TABLE' AND r.name IS NULL;

    IF unexpected_tables > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Instalacion invalida: existen tablas no canonicas/legacy';
    END IF;

    SELECT COUNT(*) INTO missing_columns
    FROM required_columns r
    LEFT JOIN information_schema.COLUMNS c
      ON c.TABLE_SCHEMA=DATABASE() AND c.TABLE_NAME=r.table_name AND c.COLUMN_NAME=r.column_name
    WHERE c.COLUMN_NAME IS NULL;

    IF missing_columns > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Instalacion invalida: faltan columnas requeridas por la aplicacion';
    END IF;

    SELECT COUNT(*) INTO bad_parks FROM parks WHERE is_active=1 AND region_id IS NULL;
    IF bad_parks > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Instalacion invalida: hay parques activos sin region';
    END IF;

    SELECT COUNT(*) INTO bad_roles
    FROM roles
    WHERE code IN('ADMIN','SEMIADMIN','TECHNICIAN','MANAGEMENT','SUPERVISOR','REQUESTER','EXTERNAL')
      AND is_active<>1;
    IF bad_roles > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Instalacion invalida: un perfil vigente esta inactivo';
    END IF;

    SELECT 3-COUNT(*) INTO missing_permissions
    FROM permissions
    WHERE code IN('tickets.resolve','management.view','external.manage');
    IF missing_permissions > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Instalacion invalida: faltan permisos actuales';
    END IF;

    SELECT COUNT(*) INTO missing_admin
    FROM users u JOIN roles r ON r.id=u.role_id
    WHERE LOWER(u.email)='luis@carrousel.com.gt' AND r.code='ADMIN'
      AND u.access_type='INTERNAL' AND u.status='ACTIVE' AND u.deleted_at IS NULL;
    IF missing_admin <> 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Instalacion invalida: administrador inicial no disponible';
    END IF;

    SELECT COUNT(*) INTO missing_trigger
    FROM information_schema.TRIGGERS
    WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME='trg_tickets_require_resolution';
    IF missing_trigger <> 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Instalacion invalida: falta control de resolucion de tickets';
    END IF;
END$$
DELIMITER ;

CALL verify_clean_helpdesk();
DROP PROCEDURE verify_clean_helpdesk;

SELECT DATABASE() AS base_actual,
       (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE') AS tablas_canonicas,
       (SELECT COUNT(*) FROM users WHERE deleted_at IS NULL) AS usuarios,
       (SELECT COUNT(*) FROM regions WHERE is_active=1) AS regiones,
       (SELECT COUNT(*) FROM parks WHERE is_active=1) AS parques,
       (SELECT COUNT(*) FROM positions WHERE is_active=1) AS puestos,
       (SELECT COUNT(*) FROM ticket_categories WHERE is_active=1) AS categorias,
       (SELECT COUNT(*) FROM schema_migrations) AS versiones_esquema;

SELECT r.code AS perfil,r.name,r.is_active,COUNT(rp.permission_id) AS permisos
FROM roles r
LEFT JOIN role_permissions rp ON rp.role_id=r.id
GROUP BY r.id,r.code,r.name,r.is_active
ORDER BY FIELD(r.code,'ADMIN','SEMIADMIN','TECHNICIAN','MANAGEMENT','SUPERVISOR','REQUESTER','EXTERNAL');

SELECT rg.name AS region,COUNT(p.id) AS parques
FROM regions rg
LEFT JOIN parks p ON p.region_id=rg.id AND p.is_active=1
WHERE rg.is_active=1
GROUP BY rg.id,rg.name
ORDER BY rg.name;

SELECT 'OK - INSTALACION LIMPIA HELPDESK CARROUSEL V2' AS resultado;
