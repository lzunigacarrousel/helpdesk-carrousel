$ErrorActionPreference = 'Stop'

$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$root = (Resolve-Path (Join-Path $root '..')).Path
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

function Update-TextFile {
    param(
        [string]$RelativePath,
        [scriptblock]$Transform
    )

    $path = Join-Path $root $RelativePath
    if (-not (Test-Path $path)) { throw "No existe $RelativePath" }

    $raw = [System.IO.File]::ReadAllText($path)
    $newline = if ($raw.Contains("`r`n")) { "`r`n" } else { "`n" }
    $text = $raw.Replace("`r`n", "`n")
    $updated = & $Transform $text

    if ($updated -eq $text) {
        Write-Host "[OK] $RelativePath ya estaba actualizado"
        return
    }

    if ($newline -eq "`r`n") { $updated = $updated.Replace("`n", "`r`n") }
    [System.IO.File]::WriteAllText($path, $updated, $utf8NoBom)
    Write-Host "[OK] Actualizado $RelativePath"
}

function Replace-Once {
    param(
        [string]$Text,
        [string]$Old,
        [string]$New,
        [string]$Label
    )

    $oldNorm = $Old.Replace("`r`n", "`n")
    $newNorm = $New.Replace("`r`n", "`n")

    if ($Text.Contains($newNorm)) { return $Text }
    if (-not $Text.Contains($oldNorm)) { throw "No se encontro el marcador esperado: $Label" }
    return $Text.Replace($oldNorm, $newNorm)
}

$activityTables = @'
-- =========================================================
-- FASE 5 - ACTIVIDADES / VISITAS
-- =========================================================
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
    CONSTRAINT fk_ta_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_ta_responsible FOREIGN KEY (responsible_user_id) REFERENCES users(id),
    CONSTRAINT fk_ta_provider FOREIGN KEY (provider_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_ta_park FOREIGN KEY (park_id) REFERENCES parks(id) ON DELETE SET NULL,
    CONSTRAINT fk_ta_cancelled_by FOREIGN KEY (cancelled_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_ta_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_ta_ticket_status_schedule (ticket_id,status,scheduled_start_at),
    INDEX idx_ta_responsible_status_schedule (responsible_user_id,status,scheduled_start_at),
    INDEX idx_ta_park_status_schedule (park_id,status,scheduled_start_at),
    INDEX idx_ta_provider_status (provider_user_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ticket_activity_participants (
    activity_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (activity_id,user_id),
    CONSTRAINT fk_tap_activity FOREIGN KEY (activity_id) REFERENCES ticket_activities(id) ON DELETE CASCADE,
    CONSTRAINT fk_tap_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_tap_created_by FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

'@

$workReports = @'
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

'@

Update-TextFile 'database/INSTALAR.sql' {
    param($text)

    if (-not $text.Contains('CREATE TABLE ticket_activities (')) {
        $marker = 'CREATE TABLE ticket_attachments ('
        if (-not $text.Contains($marker)) { throw 'No se encontro ticket_attachments para insertar actividades' }
        $text = $text.Replace($marker, $activityTables + $marker)
    }

    $text = Replace-Once $text @'
    ticket_id BIGINT UNSIGNED NOT NULL,
    comment_id BIGINT UNSIGNED NULL,
    uploaded_by_user_id BIGINT UNSIGNED NULL,
'@ @'
    ticket_id BIGINT UNSIGNED NOT NULL,
    comment_id BIGINT UNSIGNED NULL,
    activity_id BIGINT UNSIGNED NULL,
    uploaded_by_user_id BIGINT UNSIGNED NULL,
'@ 'ticket_attachments.activity_id'

    $text = Replace-Once $text @'
    CONSTRAINT fk_attachment_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_attachment_comment FOREIGN KEY (comment_id) REFERENCES ticket_comments(id) ON DELETE CASCADE,
    CONSTRAINT fk_attachment_uploader FOREIGN KEY (uploaded_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_attachments_ticket (ticket_id, visibility)
'@ @'
    CONSTRAINT fk_attachment_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_attachment_comment FOREIGN KEY (comment_id) REFERENCES ticket_comments(id) ON DELETE CASCADE,
    CONSTRAINT fk_ticket_attachment_activity FOREIGN KEY (activity_id) REFERENCES ticket_activities(id) ON DELETE SET NULL,
    CONSTRAINT fk_attachment_uploader FOREIGN KEY (uploaded_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_attachments_ticket (ticket_id, visibility),
    INDEX idx_ticket_attachments_activity (activity_id)
'@ 'FK e indice de adjuntos por actividad'

    $text = Replace-Once $text @'
    can_comment TINYINT(1) NOT NULL DEFAULT 1,
    can_upload TINYINT(1) NOT NULL DEFAULT 1,
    granted_by BIGINT UNSIGNED NULL,
'@ @'
    can_comment TINYINT(1) NOT NULL DEFAULT 1,
    can_upload TINYINT(1) NOT NULL DEFAULT 1,
    report_template ENUM('GENERAL_SUPPORT','SOFTWARE_SUPPORT','SOFTWARE_DEVELOPMENT','AUDIT_ADVISORY') NULL,
    granted_by BIGINT UNSIGNED NULL,
'@ 'external_ticket_access.report_template'

    $text = Replace-Once $text @'
    organization_name VARCHAR(190) NOT NULL,
    external_type ENUM('PROVIDER','PARTNER','OTHER') NOT NULL DEFAULT 'PROVIDER',
    notes VARCHAR(500) NULL,
'@ @'
    organization_name VARCHAR(190) NOT NULL,
    external_type ENUM('PROVIDER','PARTNER','OTHER') NOT NULL DEFAULT 'PROVIDER',
    report_template ENUM('GENERAL_SUPPORT','SOFTWARE_SUPPORT','SOFTWARE_DEVELOPMENT','AUDIT_ADVISORY') NOT NULL DEFAULT 'GENERAL_SUPPORT',
    notes VARCHAR(500) NULL,
'@ 'external_profiles.report_template'

    if (-not $text.Contains('CREATE TABLE ticket_work_reports (')) {
        $marker = "-- =========================================================`n-- PROBLEMAS RECURRENTES Y CONOCIMIENTO"
        if (-not $text.Contains($marker)) { throw 'No se encontro marcador de problemas para insertar ticket_work_reports' }
        $text = $text.Replace($marker, $workReports + $marker)
    }

    $text = Replace-Once $text @'
('external.manage','Gestionar usuarios externos','EXTERNAL','Permite crear colaboradores externos y compartir casos especiales.'),
('problems.view','Ver problemas conocidos','problems','Consulta de recurrencia'),
'@ @'
('external.manage','Gestionar usuarios externos','EXTERNAL','Permite crear colaboradores externos y compartir casos especiales.'),
('activities.view','Ver actividades','activities','Consulta actividades operativas dentro del alcance'),
('activities.create','Programar actividades','activities','Crear actividades ligadas a tickets'),
('activities.manage','Gestionar actividades','activities','Reprogramar, iniciar, finalizar y administrar participantes'),
('activities.cancel','Cancelar actividades','activities','Cancelar actividades con motivo auditable'),
('problems.view','Ver problemas conocidos','problems','Consulta de recurrencia'),
'@ 'permisos activities.*'

    $text = Replace-Once $text @'
    'users.view','assignments.view','assignments.manage','catalogs.manage','reports.view','reports.global',
    'management.view','external.manage','problems.view','problems.manage','knowledge.view','knowledge.manage','sla.manage'
'@ @'
    'users.view','assignments.view','assignments.manage','catalogs.manage','reports.view','reports.global',
    'management.view','external.manage','activities.view','activities.create','activities.manage','activities.cancel',
    'problems.view','problems.manage','knowledge.view','knowledge.manage','sla.manage'
'@ 'permisos SEMIADMIN'

    $text = Replace-Once $text @'
    'tickets.view_own','tickets.view_queue','tickets.claim','tickets.change_status','tickets.resolve','tickets.classify',
    'tickets.comment_public','tickets.comment_internal','reports.view','problems.view','knowledge.view'
'@ @'
    'tickets.view_own','tickets.view_queue','tickets.claim','tickets.change_status','tickets.resolve','tickets.classify',
    'tickets.comment_public','tickets.comment_internal','reports.view','activities.view','activities.create','activities.manage','activities.cancel',
    'problems.view','knowledge.view'
'@ 'permisos TECHNICIAN'

    $text = Replace-Once $text @'
    'management.view','reports.view','reports.global','users.view','assignments.view','problems.view','knowledge.view'
'@ @'
    'management.view','reports.view','reports.global','users.view','assignments.view','activities.view','problems.view','knowledge.view'
'@ 'permisos MANAGEMENT'

    $text = Replace-Once $text @'
    'tickets.view_own','management.view','reports.view','users.view','assignments.view','problems.view','knowledge.view'
'@ @'
    'tickets.view_own','management.view','reports.view','users.view','assignments.view','activities.view','problems.view','knowledge.view'
'@ 'permisos SUPERVISOR'

    $text = Replace-Once $text @'
INSERT INTO schema_migrations(version,name) VALUES
('2026-09-09-clean-schema-v2.4','Esquema canonico Helpdesk Carrousel para instalacion limpia');

SET FOREIGN_KEY_CHECKS = 1;
'@ @'
INSERT INTO schema_migrations(version,name) VALUES
('2026-09-09-clean-schema-v2.4','Esquema canonico Helpdesk Carrousel para instalacion limpia');

INSERT IGNORE INTO schema_migrations(version,name,applied_at) VALUES
('2026-09-12-ticket-work-reports','Informes técnicos estructurados de proveedores',NOW()),
('2026-09-12-external-report-templates','Plantillas de documentación externa por tipo de servicio',NOW()),
('2026-09-13-fase5-actividades','Fase 5 - Actividades y visitas',NOW());

SET FOREIGN_KEY_CHECKS = 1;
'@ 'schema_migrations canonicas'

    return $text
}

Update-TextFile 'database/VERIFICAR_INSTALACION.sql' {
    param($text)

    $text = Replace-Once $text @'
('support_teams'),('support_team_members'),('support_scopes'),('ticket_categories'),('sla_policies'),
('tickets'),('ticket_events'),('ticket_comments'),('ticket_attachments'),('external_ticket_access'),
('external_profiles'),('ticket_resolutions'),('ticket_feedback'),('known_problems'),('problem_occurrences'),
'@ @'
('support_teams'),('support_team_members'),('support_scopes'),('ticket_categories'),('sla_policies'),
('tickets'),('ticket_events'),('ticket_comments'),('ticket_activities'),('ticket_activity_participants'),('ticket_attachments'),('external_ticket_access'),
('external_profiles'),('ticket_work_reports'),('ticket_resolutions'),('ticket_feedback'),('known_problems'),('problem_occurrences'),
'@ 'tablas canonicas fase 5'

    $text = Replace-Once $text @'
('tickets','priority_source'),('tickets','status'),('tickets','pending_reason_code'),('tickets','pending_note'),
('tickets','resolution_due_at'),('ticket_comments','visibility'),('ticket_attachments','visibility'),
('ticket_resolutions','solution_applied'),('ticket_resolutions','resolved_by'),
('ticket_feedback','nps_score'),('external_profiles','organization_name'),
'@ @'
('tickets','priority_source'),('tickets','status'),('tickets','pending_reason_code'),('tickets','pending_note'),
('tickets','resolution_due_at'),('ticket_comments','visibility'),('ticket_attachments','visibility'),('ticket_attachments','activity_id'),
('ticket_activities','activity_type'),('ticket_activities','status'),('ticket_activities','responsible_user_id'),
('ticket_activities','scheduled_start_at'),('ticket_activities','scheduled_end_at'),('ticket_activities','requester_visible'),
('ticket_activity_participants','activity_id'),('ticket_activity_participants','user_id'),
('ticket_resolutions','solution_applied'),('ticket_resolutions','resolved_by'),
('ticket_feedback','nps_score'),('external_profiles','organization_name'),('external_profiles','report_template'),
('external_ticket_access','report_template'),('ticket_work_reports','report_template'),('ticket_work_reports','work_status'),
'@ 'columnas canonicas fase 5'

    $text = Replace-Once $text @'
    DECLARE missing_trigger INT DEFAULT 0;
    DECLARE bad_categories INT DEFAULT 0;
'@ @'
    DECLARE missing_trigger INT DEFAULT 0;
    DECLARE missing_activity_fks INT DEFAULT 0;
    DECLARE bad_categories INT DEFAULT 0;
'@ 'variable de FKs fase 5'

    $text = Replace-Once $text @'
    SELECT 4-COUNT(*) INTO missing_permissions
    FROM permissions
    WHERE code IN('tickets.resolve','tickets.classify','management.view','external.manage');
    IF missing_permissions > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Instalacion invalida: faltan permisos actuales';
    END IF;
'@ @'
    SELECT 8-COUNT(*) INTO missing_permissions
    FROM permissions
    WHERE code IN('tickets.resolve','tickets.classify','management.view','external.manage',
                  'activities.view','activities.create','activities.manage','activities.cancel');
    IF missing_permissions > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Instalacion invalida: faltan permisos actuales';
    END IF;

    SELECT 3-COUNT(*) INTO missing_activity_fks
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA=DATABASE()
      AND CONSTRAINT_NAME IN('fk_ta_ticket','fk_tap_activity','fk_ticket_attachment_activity');
    IF missing_activity_fks > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Instalacion invalida: faltan relaciones de actividades';
    END IF;
'@ 'permisos y FKs fase 5'

    return $text
}

Update-TextFile 'database/VERIFICAR_ESTABILIDAD_V2.sql' {
    param($text)

    $block = @'
SELECT 'ACTIVIDADES_FINALIZADAS_INCOMPLETAS' prueba, COUNT(*) hallazgos
FROM ticket_activities
WHERE status='FINALIZADA'
  AND (started_at IS NULL OR finished_at IS NULL OR result_code IS NULL
       OR NULLIF(TRIM(work_performed),'') IS NULL
       OR NULLIF(TRIM(result_summary),'') IS NULL);

SELECT 'ACTIVIDADES_CANCELADAS_SIN_MOTIVO' prueba, COUNT(*) hallazgos
FROM ticket_activities
WHERE status='CANCELADA'
  AND (cancelled_at IS NULL OR cancelled_by IS NULL OR NULLIF(TRIM(cancel_reason),'') IS NULL);

SELECT 'VISITAS_SIN_PARQUE' prueba, COUNT(*) hallazgos
FROM ticket_activities
WHERE activity_type='VISITA_EN_SITIO' AND park_id IS NULL;

SELECT 'REMOTOS_INCONSISTENTES' prueba, COUNT(*) hallazgos
FROM ticket_activities
WHERE (activity_type='SOPORTE_REMOTO' AND is_remote<>1)
   OR (activity_type='VISITA_EN_SITIO' AND is_remote<>0);

SELECT 'INTERVENCIONES_SIN_PROVEEDOR' prueba, COUNT(*) hallazgos
FROM ticket_activities
WHERE activity_type='INTERVENCION_PROVEEDOR' AND provider_user_id IS NULL;

SELECT 'ADJUNTOS_ACTIVIDAD_OTRO_TICKET' prueba, COUNT(*) hallazgos
FROM ticket_attachments ta
JOIN ticket_activities act ON act.id=ta.activity_id
WHERE ta.ticket_id<>act.ticket_id;

'@

    if (-not $text.Contains("'ACTIVIDADES_FINALIZADAS_INCOMPLETAS'")) {
        $marker = "SELECT 'MIGRACIONES_REGISTRADAS' prueba, COUNT(*) hallazgos FROM schema_migrations;"
        if (-not $text.Contains($marker)) { throw 'No se encontro marcador de estabilidad' }
        $text = $text.Replace($marker, $block + $marker)
    }

    if (-not $text.Contains("'TOTAL_ACTIVIDADES'")) {
        $marker = "SELECT 'TOTAL_TICKETS' prueba, COUNT(*) hallazgos FROM tickets WHERE deleted_at IS NULL;"
        if (-not $text.Contains($marker)) { throw 'No se encontro TOTAL_TICKETS' }
        $text = $text.Replace($marker, $marker + "`nSELECT 'TOTAL_ACTIVIDADES' prueba, COUNT(*) hallazgos FROM ticket_activities;")
    }

    return $text
}

Update-TextFile '.github/workflows/helpdesk-ci.yml' {
    param($text)

    if ($text.Contains('= "36"')) {
        $text = $text.Replace('= "36"', '= "39"')
    }

    if (-not $text.Contains('Verify Phase 5 schema')) {
        $marker = '          test "$(mariadb -h127.0.0.1 -uroot -N -B -e ""SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=''helpdesk_carrousel'';"")" = "0"'
        if (-not $text.Contains($marker)) {
            $marker = '          test "$(mariadb -h127.0.0.1 -uroot -N -B -e \"SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=''helpdesk_carrousel'';\")" = "0"'
        }
        if (-not $text.Contains($marker)) { throw 'No se encontro ultimo check del job de base limpia' }
        $addition = @'

      - name: Verify Phase 5 schema
        env:
          MYSQL_PWD: root
        run: mariadb -h127.0.0.1 -uroot --default-character-set=utf8mb4 < database/VERIFICAR_FASE5_ACTIVIDADES_20260913.sql
'@
        $text = $text.Replace($marker, $marker + $addition)
    }

    return $text
}

Write-Host ''
Write-Host '[OK] Aplicacion estructural de Task 2 completada.'
Write-Host '     No se ejecuto ninguna migracion contra MariaDB.'
