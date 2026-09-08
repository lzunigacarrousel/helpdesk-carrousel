-- Helpdesk Carrousel V2
-- Gestion interna, reportes y usuarios externos
-- Idempotente para TEST

USE helpdesk_carrousel_test;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS external_profiles (
    user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    organization_name VARCHAR(190) NOT NULL,
    external_type ENUM('PROVIDER','PARTNER','OTHER') NOT NULL DEFAULT 'PROVIDER',
    notes VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_external_profile_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_external_profiles_org (organization_name)
) ENGINE=InnoDB;

INSERT INTO permissions(code,name,module,description) VALUES
('management.view','Ver dashboard de gestión','MANAGEMENT','Permite consultar métricas internas del Helpdesk.'),
('reports.view','Ver y exportar informes','REPORTS','Permite consultar y exportar informes operativos.'),
('external.manage','Gestionar usuarios externos','EXTERNAL','Permite crear proveedores externos y asignarles casos especiales.')
ON DUPLICATE KEY UPDATE name=VALUES(name),module=VALUES(module),description=VALUES(description);

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.code='SEMIADMIN' AND p.code IN('management.view','reports.view','external.manage');

-- El administrador no necesita asignación explícita porque Auth::can() le concede acceso total.

SELECT 'OK - Gestion, reportes y externos V2' AS resultado;
SELECT COUNT(*) AS perfiles_externos FROM external_profiles;
SELECT code,name FROM permissions WHERE code IN('management.view','reports.view','external.manage') ORDER BY code;
