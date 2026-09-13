USE carrousel_helpdesk;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS ticket_work_reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    author_user_id BIGINT UNSIGNED NULL,
    author_access_type ENUM('INTERNAL','EXTERNAL') NOT NULL,
    report_type ENUM('PROGRESS','INFO_REQUEST','WORK_COMPLETED') NOT NULL DEFAULT 'PROGRESS',
    progress_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
    diagnosis TEXT NOT NULL,
    root_cause TEXT NOT NULL,
    actions_performed TEXT NOT NULL,
    parts_materials TEXT NOT NULL,
    configuration_changes TEXT NOT NULL,
    tests_performed TEXT NOT NULL,
    result_summary TEXT NOT NULL,
    pending_items TEXT NOT NULL,
    preventive_recommendation TEXT NOT NULL,
    provider_reference VARCHAR(190) NOT NULL,
    time_spent_minutes INT UNSIGNED NOT NULL DEFAULT 0,
    commitment_at DATETIME NULL,
    ready_for_review TINYINT(1) NOT NULL DEFAULT 0,
    comment_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_work_report_ticket FOREIGN KEY(ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_work_report_author FOREIGN KEY(author_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_work_report_comment FOREIGN KEY(comment_id) REFERENCES ticket_comments(id) ON DELETE SET NULL,
    CONSTRAINT chk_work_report_progress CHECK (progress_percent BETWEEN 0 AND 100),
    INDEX idx_work_report_ticket_created(ticket_id,created_at),
    INDEX idx_work_report_author_created(author_user_id,created_at),
    INDEX idx_work_report_type_review(report_type,ready_for_review)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations(version,name,applied_at)
VALUES('2026-09-12-ticket-work-reports','Informes técnicos estructurados de proveedores',NOW());

SELECT 'OK - ticket_work_reports disponible' AS resultado;
