-- Helpdesk Carrousel V2
-- Captura estructurada de resoluciones para informes y conocimiento reutilizable

USE helpdesk_carrousel_test;

CREATE TABLE IF NOT EXISTS ticket_resolutions (
    ticket_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    resolution_type VARCHAR(60) NOT NULL DEFAULT 'OTHER',
    root_cause TEXT NULL,
    solution_applied TEXT NOT NULL,
    preventive_action TEXT NULL,
    is_reusable TINYINT(1) NOT NULL DEFAULT 0,
    resolved_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_resolution_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_resolution_user FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_resolution_type (resolution_type),
    INDEX idx_resolution_reusable (is_reusable),
    INDEX idx_resolution_user (resolved_by)
) ENGINE=InnoDB;

-- Regla de integridad: un caso no puede quedar Resuelto/Cerrado sin documentar cómo se resolvió.
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

SELECT 'OK - Resoluciones V2' AS resultado;
