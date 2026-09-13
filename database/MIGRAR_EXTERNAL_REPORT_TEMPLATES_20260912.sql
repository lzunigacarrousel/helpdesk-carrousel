USE carrousel_helpdesk;
SET NAMES utf8mb4;

-- Plantillas de documentación externa por tipo de servicio.
ALTER TABLE external_profiles
    ADD COLUMN IF NOT EXISTS report_template ENUM(
        'GENERAL_SUPPORT','SOFTWARE_SUPPORT','SOFTWARE_DEVELOPMENT','AUDIT_ADVISORY'
    ) NOT NULL DEFAULT 'GENERAL_SUPPORT' AFTER external_type;

-- Un caso puede usar una plantilla distinta a la predeterminada del proveedor.
ALTER TABLE external_ticket_access
    ADD COLUMN IF NOT EXISTS report_template ENUM(
        'GENERAL_SUPPORT','SOFTWARE_SUPPORT','SOFTWARE_DEVELOPMENT','AUDIT_ADVISORY'
    ) NULL AFTER can_upload;

-- El informe guarda la plantilla y el estado real con que fue documentado.
ALTER TABLE ticket_work_reports
    ADD COLUMN IF NOT EXISTS report_template ENUM(
        'GENERAL_SUPPORT','SOFTWARE_SUPPORT','SOFTWARE_DEVELOPMENT','AUDIT_ADVISORY'
    ) NOT NULL DEFAULT 'GENERAL_SUPPORT' AFTER author_access_type,
    ADD COLUMN IF NOT EXISTS work_status ENUM(
        'ANALYSIS','WAITING_CARROUSEL','WAITING_THIRD_PARTY','IN_PROGRESS','VALIDATING','READY_FOR_REVIEW'
    ) NOT NULL DEFAULT 'ANALYSIS' AFTER report_template,
    ADD COLUMN IF NOT EXISTS system_module VARCHAR(190) NULL AFTER commitment_at,
    ADD COLUMN IF NOT EXISTS environment VARCHAR(80) NULL AFTER system_module,
    ADD COLUMN IF NOT EXISTS error_symptom TEXT NULL AFTER environment,
    ADD COLUMN IF NOT EXISTS reproduction_steps TEXT NULL AFTER error_symptom,
    ADD COLUMN IF NOT EXISTS procedure_steps TEXT NULL AFTER reproduction_steps,
    ADD COLUMN IF NOT EXISTS tools_access_used TEXT NULL AFTER procedure_steps,
    ADD COLUMN IF NOT EXISTS rollback_steps TEXT NULL AFTER tools_access_used,
    ADD COLUMN IF NOT EXISTS escalation_criteria TEXT NULL AFTER rollback_steps,
    ADD COLUMN IF NOT EXISTS code_changes TEXT NULL AFTER escalation_criteria,
    ADD COLUMN IF NOT EXISTS database_changes TEXT NULL AFTER code_changes,
    ADD COLUMN IF NOT EXISTS release_version VARCHAR(120) NULL AFTER database_changes,
    ADD COLUMN IF NOT EXISTS deployment_notes TEXT NULL AFTER release_version,
    ADD COLUMN IF NOT EXISTS review_scope TEXT NULL AFTER deployment_notes,
    ADD COLUMN IF NOT EXISTS finding TEXT NULL AFTER review_scope,
    ADD COLUMN IF NOT EXISTS evidence_summary TEXT NULL AFTER finding,
    ADD COLUMN IF NOT EXISTS risk_level ENUM('LOW','MEDIUM','HIGH','CRITICAL') NULL AFTER evidence_summary,
    ADD COLUMN IF NOT EXISTS business_impact TEXT NULL AFTER risk_level,
    ADD COLUMN IF NOT EXISTS recommendation TEXT NULL AFTER business_impact,
    ADD COLUMN IF NOT EXISTS recommendation_priority ENUM('LOW','MEDIUM','HIGH','CRITICAL') NULL AFTER recommendation,
    ADD COLUMN IF NOT EXISTS suggested_owner VARCHAR(190) NULL AFTER recommendation_priority,
    ADD COLUMN IF NOT EXISTS follow_up TEXT NULL AFTER suggested_owner,
    ADD COLUMN IF NOT EXISTS conclusion TEXT NULL AFTER follow_up;

INSERT IGNORE INTO schema_migrations(version,name,applied_at)
VALUES('2026-09-12-external-report-templates','Plantillas de documentación externa por tipo de servicio',NOW());

SELECT 'OK - plantillas de documentación externa disponibles' AS resultado;
