-- Helpdesk Carrousel V2
-- Organizacion + registro con asignacion
-- Idempotente para TEST

USE helpdesk_carrousel_test;
SET NAMES utf8mb4;

-- =========================================================
-- PUESTOS: separados de los roles/permisos del Helpdesk
-- =========================================================
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
ON DUPLICATE KEY UPDATE name=VALUES(name),description=VALUES(description),sort_order=VALUES(sort_order),is_active=1;

-- =========================================================
-- CATALOGOS ORGANIZACIONALES CANONICOS
-- =========================================================
INSERT INTO areas (code,name,description,is_active) VALUES
('MANAGEMENT','Gerencia','Area gerencial',1),
('MAINTENANCE','Mantenimiento','Mantenimiento y soporte operativo',1)
ON DUPLICATE KEY UPDATE name=VALUES(name),description=VALUES(description),is_active=1;

INSERT INTO regions (code,name,is_active) VALUES
('CC_REGION_6','Sur Occidente',1),
('CC_REGION_7','Nor Oriente',1),
('CC_REGION_8','Central',1),
('CC_REGION_9','Central Alianzas',1)
ON DUPLICATE KEY UPDATE name=VALUES(name),is_active=1;

-- =========================================================
-- AMPLIAR ASIGNACION SIN BORRAR HISTORIAL
-- =========================================================
ALTER TABLE user_assignments ADD COLUMN IF NOT EXISTS region_id BIGINT UNSIGNED NULL AFTER user_id;
ALTER TABLE user_assignments ADD COLUMN IF NOT EXISTS position_id BIGINT UNSIGNED NULL AFTER area_id;
ALTER TABLE user_assignments ADD COLUMN IF NOT EXISTS assignment_type ENUM('PARK','CORPORATE','OTHER') NOT NULL DEFAULT 'OTHER' AFTER position_id;

UPDATE user_assignments ua
LEFT JOIN parks p ON p.id=ua.park_id
SET ua.assignment_type=CASE
        WHEN ua.park_id IS NOT NULL THEN 'PARK'
        WHEN ua.area_id IS NOT NULL THEN 'CORPORATE'
        ELSE 'OTHER'
    END,
    ua.region_id=COALESCE(ua.region_id,p.region_id)
WHERE ua.status='ACTIVE' AND ua.ends_at IS NULL;

-- =========================================================
-- LIMPIAR EL IMPORT DE CAJA CHICA:
-- regiones no son areas; areas duplicadas se consolidan
-- =========================================================
UPDATE user_assignments ua
JOIN areas olda ON olda.id=ua.area_id AND olda.code='CC_AREA_1'
JOIN areas canon ON canon.code='ADMIN'
SET ua.area_id=canon.id;

UPDATE user_assignments ua
JOIN areas olda ON olda.id=ua.area_id AND olda.code='CC_AREA_3'
JOIN areas canon ON canon.code='MANAGEMENT'
SET ua.area_id=canon.id;

UPDATE user_assignments ua
JOIN areas olda ON olda.id=ua.area_id AND olda.code='CC_AREA_4'
JOIN areas canon ON canon.code='MAINTENANCE'
SET ua.area_id=canon.id;

UPDATE user_assignments ua
JOIN areas olda ON olda.id=ua.area_id AND olda.code='CC_AREA_5'
JOIN areas canon ON canon.code='OPERATIONS'
SET ua.area_id=canon.id;

-- Region Sur Occidente
UPDATE user_assignments ua
JOIN areas olda ON olda.id=ua.area_id AND olda.code='CC_AREA_6'
JOIN regions rg ON rg.code='CC_REGION_6'
LEFT JOIN areas op ON op.code='OPERATIONS'
SET ua.region_id=rg.id,ua.area_id=IF(ua.park_id IS NOT NULL,op.id,NULL);

-- Region Nor Oriente
UPDATE user_assignments ua
JOIN areas olda ON olda.id=ua.area_id AND olda.code='CC_AREA_7'
JOIN regions rg ON rg.code='CC_REGION_7'
LEFT JOIN areas op ON op.code='OPERATIONS'
SET ua.region_id=rg.id,ua.area_id=IF(ua.park_id IS NOT NULL,op.id,NULL);

-- Region Central
UPDATE user_assignments ua
JOIN areas olda ON olda.id=ua.area_id AND olda.code='CC_AREA_8'
JOIN regions rg ON rg.code='CC_REGION_8'
LEFT JOIN areas op ON op.code='OPERATIONS'
SET ua.region_id=rg.id,ua.area_id=IF(ua.park_id IS NOT NULL,op.id,NULL);

-- Region Central Alianzas
UPDATE user_assignments ua
JOIN areas olda ON olda.id=ua.area_id AND olda.code='CC_AREA_9'
JOIN regions rg ON rg.code='CC_REGION_9'
LEFT JOIN areas op ON op.code='OPERATIONS'
SET ua.region_id=rg.id,ua.area_id=IF(ua.park_id IS NOT NULL,op.id,NULL);

UPDATE areas SET is_active=0 WHERE code IN('CC_AREA_1','CC_AREA_3','CC_AREA_4','CC_AREA_5','CC_AREA_6','CC_AREA_7','CC_AREA_8','CC_AREA_9');

-- La region de cada parque se obtiene de sus asignaciones importadas.
UPDATE parks p
JOIN (
    SELECT park_id,MAX(region_id) region_id
    FROM user_assignments
    WHERE park_id IS NOT NULL AND region_id IS NOT NULL
    GROUP BY park_id
) x ON x.park_id=p.id
SET p.region_id=COALESCE(p.region_id,x.region_id);

UPDATE user_assignments ua
JOIN parks p ON p.id=ua.park_id
SET ua.region_id=COALESCE(ua.region_id,p.region_id)
WHERE ua.status='ACTIVE' AND ua.ends_at IS NULL;

-- =========================================================
-- SEPARAR ROL DEL SISTEMA DE PUESTO ORGANIZACIONAL
-- =========================================================
UPDATE user_assignments ua
JOIN users u ON u.id=ua.user_id
JOIN roles r ON r.id=u.role_id AND r.code='SUPERVISOR'
JOIN positions p ON p.code='REGIONAL_SUPERVISOR'
SET ua.position_id=COALESCE(ua.position_id,p.id),ua.assignment_type='CORPORATE'
WHERE ua.status='ACTIVE' AND ua.ends_at IS NULL;

UPDATE user_assignments ua
JOIN users u ON u.id=ua.user_id
JOIN roles r ON r.id=u.role_id AND r.code='MANAGEMENT'
JOIN positions p ON p.code='MANAGEMENT'
SET ua.position_id=COALESCE(ua.position_id,p.id),ua.assignment_type='CORPORATE'
WHERE ua.status='ACTIVE' AND ua.ends_at IS NULL;

UPDATE user_assignments ua
JOIN users u ON u.id=ua.user_id
JOIN roles r ON r.id=u.role_id AND r.code='REQUESTER'
JOIN positions p ON p.code='PARK_USER'
SET ua.position_id=COALESCE(ua.position_id,p.id),ua.assignment_type='PARK'
WHERE ua.status='ACTIVE' AND ua.ends_at IS NULL AND ua.park_id IS NOT NULL;

-- Puestos corporativos basicos si aun no tienen uno.
UPDATE user_assignments ua JOIN areas a ON a.id=ua.area_id AND a.code='ADMIN' JOIN positions p ON p.code='ADMINISTRATION'
SET ua.position_id=COALESCE(ua.position_id,p.id) WHERE ua.status='ACTIVE' AND ua.ends_at IS NULL AND ua.park_id IS NULL;
UPDATE user_assignments ua JOIN areas a ON a.id=ua.area_id AND a.code='OPERATIONS' JOIN positions p ON p.code='OPERATIONS'
SET ua.position_id=COALESCE(ua.position_id,p.id) WHERE ua.status='ACTIVE' AND ua.ends_at IS NULL AND ua.park_id IS NULL;
UPDATE user_assignments ua JOIN areas a ON a.id=ua.area_id AND a.code='MAINTENANCE' JOIN positions p ON p.code='MAINTENANCE'
SET ua.position_id=COALESCE(ua.position_id,p.id) WHERE ua.status='ACTIVE' AND ua.ends_at IS NULL AND ua.park_id IS NULL;

UPDATE users u
JOIN roles oldr ON oldr.id=u.role_id AND oldr.code IN('SUPERVISOR','MANAGEMENT')
JOIN roles req ON req.code='REQUESTER'
SET u.role_id=req.id,u.updated_at=NOW();

-- Quedan solo como compatibilidad historica del primer import.
UPDATE roles SET is_active=0 WHERE code IN('SUPERVISOR','MANAGEMENT');

-- =========================================================
-- RESULTADO DE CONTROL
-- =========================================================
SELECT
    (SELECT COUNT(*) FROM positions WHERE is_active=1) AS puestos_activos,
    (SELECT COUNT(*) FROM regions WHERE is_active=1) AS regiones_activas,
    (SELECT COUNT(*) FROM areas WHERE is_active=1) AS areas_activas,
    (SELECT COUNT(*) FROM users WHERE deleted_at IS NULL) AS usuarios,
    (SELECT COUNT(*) FROM user_assignments WHERE status='ACTIVE' AND ends_at IS NULL) AS asignaciones_activas,
    (SELECT COUNT(*) FROM user_assignments WHERE status='ACTIVE' AND ends_at IS NULL AND manager_user_id IS NOT NULL) AS con_responsable,
    (SELECT COUNT(*) FROM user_assignments WHERE status='ACTIVE' AND ends_at IS NULL AND position_id IS NULL) AS sin_puesto;
