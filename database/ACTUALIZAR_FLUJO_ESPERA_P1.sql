-- Helpdesk Carrousel V2 · P1 Operacional
-- Motivos de espera + control mínimo de versión del esquema
-- NO destructivo / idempotente para TEST

USE helpdesk_carrousel_test;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS schema_migrations (
    version VARCHAR(60) NOT NULL PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE tickets ADD COLUMN IF NOT EXISTS pending_reason_code VARCHAR(50) NULL AFTER status;
ALTER TABLE tickets ADD COLUMN IF NOT EXISTS pending_note VARCHAR(500) NULL AFTER pending_reason_code;

-- No debe quedar un motivo de espera activo en un caso que ya no está en espera.
UPDATE tickets
SET pending_reason_code=NULL,pending_note=NULL
WHERE status<>'PENDING' AND (pending_reason_code IS NOT NULL OR pending_note IS NOT NULL);

INSERT IGNORE INTO schema_migrations(version,name)
VALUES('P1-20260909-001','Motivos de espera y control de migraciones');

SELECT 'OK - Flujo de espera P1' AS resultado,
       (SELECT COUNT(*) FROM schema_migrations) AS migraciones_registradas,
       (SELECT COUNT(*) FROM tickets WHERE status='PENDING') AS tickets_en_espera;
