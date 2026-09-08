-- Helpdesk Carrousel V2
-- Organizacion + registro con asignacion
-- Idempotente para TEST

USE helpdesk_carrousel_test;
SET NAMES utf8mb4;

-- 1) Puestos organizacionales: independientes del rol/permisos del Helpdesk.
CREATE TABLE IF NOT EXISTS positions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(140) NOT NULL,
    description VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 100,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO positions (code,name,description,sort_order) VALUES
('PARK_USER','Colaborador de parque','Usuario operativo asignado a un parque',10),
('PARK_MANAGER','Encargado de parque','Responsable operativo de un parque',20),
('REGIONAL_SUPERVISOR','Supervisor regional','Responsable de una region o grupo de parques',30),
('MANAGEMENT','Gerencia','Responsable gerencial dentro de la estructura organizacional',40),
('ADMINISTRATION','Administracion','Personal administrativo',50),
('OPERATIONS','Operaciones','Personal de operaciones',60),
('MAINTENANCE','Mantenimiento','Personal de mantenimiento',70),
('TECHNOLOGY','Tecnologia','Personal de tecnologia / sistemas',80),
('OTHER','Otro','Otro puesto o funcion',99)
ON DUPLICATE KEY UPDATE
    name=VALUES(name),description=VALUES(description),sort_order=VALUES(sort_order),is_active=1;

-- 2) Ampliar la asignacion existente sin borrar historial.
ALTER TABLE user_assignments ADD COLUMN IF NOT EXISTS region_id BIGINT UNSIGNED NULL AFTER user_id;
ALTER TABLE user_assignments ADD COLUMN IF NOT EXISTS position_id BIGINT UNSIGNED NULL AFTER area_id;
ALTER TABLE user_assignments ADD COLUMN IF NOT EXISTS assignment_type ENUM('PARK','CORPORATE','OTHER') NOT NULL DEFAULT 'OTHER' AFTER position_id;

-- Inferir tipo/region de las asignaciones que ya existen.
UPDATE user_assignments ua
LEFT JOIN parks p ON p.id=ua.park_id
SET ua.assignment_type=CASE
        WHEN ua.park_id IS NOT NULL THEN 'PARK'
        WHEN ua.area_id IS NOT NULL THEN 'CORPORATE'
        ELSE 'OTHER'
    END,
    ua.region_id=COALESCE(ua.region_id,p.region_id)
WHERE ua.status='ACTIVE' AND ua.ends_at IS NULL;

-- 3) Separar permisos Helpdesk de jerarquia organizacional.
-- Los usuarios que venian como SUPERVISOR/GERENCIA pasan a Solicitante.
-- Su puesto se conserva en la asignacion organizacional.
UPDATE user_assignments ua
JOIN users u ON u.id=ua.user_id
JOIN roles r ON r.id=u.role_id AND r.code='SUPERVISOR'
JOIN positions p ON p.code='REGIONAL_SUPERVISOR'
SET ua.position_id=COALESCE(ua.position_id,p.id)
WHERE ua.status='ACTIVE' AND ua.ends_at IS NULL;

UPDATE user_assignments ua
JOIN users u ON u.id=ua.user_id
JOIN roles r ON r.id=u.role_id AND r.code='MANAGEMENT'
JOIN positions p ON p.code='MANAGEMENT'
SET ua.position_id=COALESCE(ua.position_id,p.id)
WHERE ua.status='ACTIVE' AND ua.ends_at IS NULL;

UPDATE user_assignments ua
JOIN users u ON u.id=ua.user_id
JOIN roles r ON r.id=u.role_id AND r.code='REQUESTER'
JOIN positions p ON p.code='PARK_USER'
SET ua.position_id=COALESCE(ua.position_id,p.id)
WHERE ua.status='ACTIVE' AND ua.ends_at IS NULL AND ua.park_id IS NOT NULL;

UPDATE users u
JOIN roles oldr ON oldr.id=u.role_id AND oldr.code IN('SUPERVISOR','MANAGEMENT')
JOIN roles req ON req.code='REQUESTER'
SET u.role_id=req.id,u.updated_at=NOW();

-- Ya no se usan como roles de seguridad; quedan solo por compatibilidad historica.
UPDATE roles SET is_active=0 WHERE code IN('SUPERVISOR','MANAGEMENT');

-- 4) Completar region de parques a partir de la asignacion de sus supervisores cuando sea posible.
-- No fuerza datos si la region aun no esta catalogada.
UPDATE user_assignments ua
JOIN parks p ON p.id=ua.park_id
SET ua.region_id=p.region_id
WHERE ua.status='ACTIVE' AND ua.ends_at IS NULL AND ua.region_id IS NULL AND p.region_id IS NOT NULL;

-- Resultado de control.
SELECT
    (SELECT COUNT(*) FROM positions WHERE is_active=1) AS puestos_activos,
    (SELECT COUNT(*) FROM users WHERE deleted_at IS NULL) AS usuarios,
    (SELECT COUNT(*) FROM user_assignments WHERE status='ACTIVE' AND ends_at IS NULL) AS asignaciones_activas,
    (SELECT COUNT(*) FROM user_assignments WHERE status='ACTIVE' AND ends_at IS NULL AND manager_user_id IS NOT NULL) AS con_responsable;
