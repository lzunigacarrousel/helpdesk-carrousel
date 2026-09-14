$ErrorActionPreference = 'Stop'

$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$installPath = Join-Path $root 'database\INSTALAR.sql'

if (-not (Test-Path $installPath)) {
    throw "No existe database/INSTALAR.sql en $root"
}

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
$raw = [System.IO.File]::ReadAllText($installPath)
$newline = if ($raw.Contains("`r`n")) { "`r`n" } else { "`n" }
$text = $raw.Replace("`r`n", "`n")

$marker = "INSERT INTO support_teams(code,name,description)"
$signature = "-- FASE 5 - BASELINE CANONICA Y ACTIVIDADES"

if ($text.Contains($signature)) {
    Write-Host '[OK] database/INSTALAR.sql ya contiene la baseline canonica de Fase 5.'
    exit 0
}

if (-not $text.Contains($marker)) {
    throw 'No se encontro el marcador esperado antes de support_teams.'
}

$block = @'
-- =========================================================
-- FASE 5 - BASELINE CANONICA Y ACTIVIDADES
-- =========================================================
-- Normaliza funcionalidades externas ya vigentes y agrega Actividades / Visitas.

ALTER TABLE external_profiles
    ADD COLUMN report_template ENUM(
        'GENERAL_SUPPORT','SOFTWARE_SUPPORT','SOFTWARE_DEVELOPMENT','AUDIT_ADVISORY'
    ) NOT NULL DEFAULT 'GENERAL_SUPPORT' AFTER external_type;

ALTER TABLE external_ticket_access
    ADD COLUMN report_template ENUM(
        'GENERAL_SUPPORT','SOFTWARE_SUPPORT','SOFTWARE_DEVELOPMENT','AUDIT_ADVISORY'
    ) NULL AFTER can_upload;

CREATE TABLE ticket_work_reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    author_user_id BIGINT UNSIGNED NULL,
    author_access_type ENUM('INTERNAL','EXTERNAL') NOT NULL,
    report_template ENUM('GENERAL_SUPPORT','SOFTWARE_SUPPORT','SOFTWARE_DEVELOPMENT','AUDIT_ADVISORY') NOT NULL DEFAULT 'GENERAL_SUPPORT',
    work_status ENUM('ANALYSIS','WAITING_CARROUSEL','WAITING_THIRD_PARTY','IN_PROGRESS','VALIDATING','READY_FOR_REVIEW') NOT NULL DEFAULT 'ANALYSIS',
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
    system_module VARCHAR(190) NULL,
    environment VARCHAR(80) NULL,
    error_symptom TEXT NULL,
    reproduction_steps TEXT NULL,
    procedure_steps TEXT NULL,
    tools_access_used TEXT NULL,
    rollback_steps TEXT NULL,
    escalation_criteria TEXT NULL,
    code_changes TEXT NULL,
    database_changes TEXT NULL,
    release_version VARCHAR(120) NULL,
    deployment_notes TEXT NULL,
    review_scope TEXT NULL,
    finding TEXT NULL,
    evidence_summary TEXT NULL,
    risk_level ENUM('LOW','MEDIUM','HIGH','CRITICAL') NULL,
    business_impact TEXT NULL,
    recommendation TEXT NULL,
    recommendation_priority ENUM('LOW','MEDIUM','HIGH','CRITICAL') NULL,
    suggested_owner VARCHAR(190) NULL,
    follow_up TEXT NULL,
    conclusion TEXT NULL,
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

CREATE TABLE ticket_activities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    activity_type ENUM('VISITA_EN_SITIO','SOPORTE_REMOTO','SEGUIMIENTO','INTERVENCION_PROVEEDOR','OTRA') NOT NULL,
    status ENUM('PROGRAMADA','EN_CURSO','FINALIZADA','CANCELADA') NOT NULL DEFAULT 'PROGRAMADA',
    responsible_user_id BIGINT UNSIGNED NOT NULL,
    provider_user_id BIGINT UNSIGNED NULL,
    park_id BIGINT UNSIGNED NULL,
    is_remote TINYINT(1) NOT NULL DEFAULT 0,
    objective TEXT NOT NULL,
    internal_preparation_notes TEXT NULL,
    scheduled_start_at DATETIME NOT NULL,
    scheduled_end_at DATETIME NOT NULL,
    started_at DATETIME NULL,
    finished_at DATETIME NULL,
    result_code ENUM('RESUELTA','PARCIAL','SIN_RESOLVER','REQUIERE_SEGUIMIENTO') NULL,
    work_performed TEXT NULL,
    result_summary TEXT NULL,
    pending_items TEXT NULL,
    requester_visible TINYINT(1) NOT NULL DEFAULT 0,
    requester_summary VARCHAR(500) NULL,
    cancel_reason VARCHAR(500) NULL,
    cancelled_at DATETIME NULL,
    cancelled_by BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ta_ticket FOREIGN KEY(ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_ta_responsible FOREIGN KEY(responsible_user_id) REFERENCES users(id),
    CONSTRAINT fk_ta_provider FOREIGN KEY(provider_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_ta_park FOREIGN KEY(park_id) REFERENCES parks(id) ON DELETE SET NULL,
    CONSTRAINT fk_ta_cancelled_by FOREIGN KEY(cancelled_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_ta_created_by FOREIGN KEY(created_by) REFERENCES users(id),
    INDEX idx_ta_ticket_status_schedule(ticket_id,status,scheduled_start_at),
    INDEX idx_ta_responsible_status_schedule(responsible_user_id,status,scheduled_start_at),
    INDEX idx_ta_park_status_schedule(park_id,status,scheduled_start_at),
    INDEX idx_ta_provider_status(provider_user_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ticket_activity_participants (
    activity_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(activity_id,user_id),
    CONSTRAINT fk_tap_activity FOREIGN KEY(activity_id) REFERENCES ticket_activities(id) ON DELETE CASCADE,
    CONSTRAINT fk_tap_user FOREIGN KEY(user_id) REFERENCES users(id),
    CONSTRAINT fk_tap_created_by FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE ticket_attachments
    ADD COLUMN activity_id BIGINT UNSIGNED NULL AFTER comment_id,
    ADD CONSTRAINT fk_ticket_attachment_activity FOREIGN KEY(activity_id) REFERENCES ticket_activities(id) ON DELETE SET NULL,
    ADD INDEX idx_ticket_attachments_activity(activity_id);

INSERT INTO permissions(code,name,module,description) VALUES
('activities.view','Ver actividades','activities','Consulta actividades operativas dentro del alcance'),
('activities.create','Programar actividades','activities','Crear actividades ligadas a tickets'),
('activities.manage','Gestionar actividades','activities','Reprogramar, iniciar, finalizar y administrar participantes'),
('activities.cancel','Cancelar actividades','activities','Cancelar actividades con motivo auditable')
ON DUPLICATE KEY UPDATE
    name=VALUES(name),module=VALUES(module),description=VALUES(description);

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id
FROM roles r
JOIN permissions p ON p.code IN('activities.view','activities.create','activities.manage','activities.cancel')
WHERE r.code IN('ADMIN','SEMIADMIN','TECHNICIAN');

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id
FROM roles r
JOIN permissions p ON p.code='activities.view'
WHERE r.code IN('MANAGEMENT','SUPERVISOR');

INSERT IGNORE INTO schema_migrations(version,name,applied_at) VALUES
('2026-09-12-ticket-work-reports','Informes técnicos estructurados de proveedores',NOW()),
('2026-09-12-external-report-templates','Plantillas de documentación externa por tipo de servicio',NOW()),
('2026-09-13-fase5-actividades','Fase 5 - Actividades y visitas',NOW());

'@

$updated = $text.Replace($marker, $block + $marker)
if ($updated -eq $text) {
    throw 'No se pudo insertar la baseline canonica de Fase 5.'
}

if ($newline -eq "`r`n") {
    $updated = $updated.Replace("`n", "`r`n")
}

[System.IO.File]::WriteAllText($installPath, $updated, $utf8NoBom)

Write-Host '[OK] Actualizado database/INSTALAR.sql'
Write-Host '[OK] Ruta del repositorio:' $root
Write-Host '[OK] No se ejecuto ninguna migracion contra MariaDB.'
