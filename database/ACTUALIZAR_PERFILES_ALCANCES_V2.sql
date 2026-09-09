-- Helpdesk Carrousel V2
-- Perfiles, operacion de soporte y alcances de consulta
-- Incremental e idempotente para TEST

USE helpdesk_carrousel_test;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS schema_migrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(120) NOT NULL UNIQUE,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO permissions(code,name,module,description) VALUES
('management.view','Ver dashboard de gestión','MANAGEMENT','Consulta métricas e indicadores dentro del alcance autorizado.'),
('reports.view','Ver y exportar informes','REPORTS','Consulta y exporta informes dentro del alcance autorizado.'),
('reports.global','Ver informes globales','REPORTS','Permite consultar indicadores globales sin restricción organizacional.')
ON DUPLICATE KEY UPDATE name=VALUES(name),module=VALUES(module),description=VALUES(description);

UPDATE roles SET name='Gerencia',description='Consulta ejecutiva global, indicadores, informes y conocimiento; no atiende solicitudes.' WHERE code='MANAGEMENT';
UPDATE roles SET name='Supervisor',description='Consulta y seguimiento dentro de su región, parque o área; no atiende solicitudes.' WHERE code='SUPERVISOR';
UPDATE roles SET description='Atención y gestión operativa de tickets dentro del alcance de soporte.' WHERE code='TECHNICIAN';
UPDATE roles SET description='Operación amplia de soporte y gestión sin privilegios administrativos totales.' WHERE code='SEMIADMIN';

-- Gerencia: consulta global y lectura de contexto, nunca operación de tickets.
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.code='MANAGEMENT' AND p.code IN(
    'management.view','reports.view','reports.global','users.view','assignments.view','problems.view','knowledge.view'
);

-- Supervisor: la consulta queda limitada por su asignación organizacional desde ScopeService.
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.code='SUPERVISOR' AND p.code IN(
    'management.view','reports.view','users.view','assignments.view','problems.view','knowledge.view'
);

-- Los perfiles de consulta no deben recibir acciones operativas aunque las hayan heredado en una etapa anterior.
DELETE rp FROM role_permissions rp
JOIN roles r ON r.id=rp.role_id
JOIN permissions p ON p.id=rp.permission_id
WHERE r.code IN('MANAGEMENT','SUPERVISOR')
  AND p.code IN(
    'tickets.view_queue','tickets.claim','tickets.reassign','tickets.change_status',
    'tickets.comment_public','tickets.comment_internal','tickets.manage_special',
    'tickets.view_all','users.manage','assignments.manage','catalogs.manage','sla.manage','external.manage','knowledge.manage','problems.manage','audit.view'
  );

-- Gerencia conserva reportes globales; Supervisor nunca los eleva a global.
DELETE rp FROM role_permissions rp
JOIN roles r ON r.id=rp.role_id
JOIN permissions p ON p.id=rp.permission_id
WHERE r.code='SUPERVISOR' AND p.code='reports.global';

-- El equipo que atiende casos se mantiene separado de los perfiles que solo consultan.
SET @it_team_id := (SELECT id FROM support_teams WHERE code='IT' LIMIT 1);

INSERT INTO support_team_members(team_id,user_id,is_active,joined_at,ended_at)
SELECT @it_team_id,u.id,1,NOW(),NULL
FROM users u
JOIN roles r ON r.id=u.role_id
WHERE @it_team_id IS NOT NULL
  AND u.deleted_at IS NULL
  AND u.access_type='INTERNAL'
  AND r.code IN('ADMIN','SEMIADMIN','TECHNICIAN')
ON DUPLICATE KEY UPDATE is_active=1,ended_at=NULL;

UPDATE support_team_members stm
JOIN users u ON u.id=stm.user_id
JOIN roles r ON r.id=u.role_id
SET stm.is_active=0,stm.ended_at=COALESCE(stm.ended_at,NOW())
WHERE @it_team_id IS NOT NULL
  AND stm.team_id=@it_team_id
  AND r.code NOT IN('ADMIN','SEMIADMIN','TECHNICIAN');

INSERT IGNORE INTO schema_migrations(version) VALUES('2026-09-09_perfiles_alcances_v2');

SELECT 'OK - Perfiles y alcances V2' AS resultado;
SELECT r.code,r.name,
       GROUP_CONCAT(p.code ORDER BY p.code SEPARATOR ', ') permisos
FROM roles r
LEFT JOIN role_permissions rp ON rp.role_id=r.id
LEFT JOIN permissions p ON p.id=rp.permission_id
WHERE r.code IN('MANAGEMENT','SUPERVISOR','TECHNICIAN')
GROUP BY r.id,r.code,r.name
ORDER BY r.code;
