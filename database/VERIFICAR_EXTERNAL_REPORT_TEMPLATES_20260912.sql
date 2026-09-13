USE carrousel_helpdesk;

SELECT CASE WHEN COUNT(*)=1
    THEN 'OK - external_profiles.report_template existe'
    ELSE 'FALLO - falta external_profiles.report_template' END AS resultado
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='external_profiles' AND COLUMN_NAME='report_template';

SELECT CASE WHEN COUNT(*)=1
    THEN 'OK - external_ticket_access.report_template existe'
    ELSE 'FALLO - falta external_ticket_access.report_template' END AS resultado
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='external_ticket_access' AND COLUMN_NAME='report_template';

SELECT CASE WHEN COUNT(*)>=22
    THEN 'OK - ticket_work_reports tiene campos de plantillas y especialidades'
    ELSE CONCAT('FALLO - faltan campos en ticket_work_reports: encontrados ',COUNT(*),' de 22+') END AS resultado
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='ticket_work_reports'
  AND COLUMN_NAME IN (
    'report_template','work_status','system_module','environment','error_symptom','reproduction_steps',
    'procedure_steps','tools_access_used','rollback_steps','escalation_criteria','code_changes','database_changes',
    'release_version','deployment_notes','review_scope','finding','evidence_summary','risk_level','business_impact',
    'recommendation','recommendation_priority','conclusion'
  );

SELECT CASE WHEN COUNT(*)=1
    THEN 'OK - migracion de plantillas registrada'
    ELSE 'FALLO - migracion de plantillas no registrada' END AS resultado
FROM schema_migrations
WHERE version='2026-09-12-external-report-templates';

SELECT 'OK - verificacion de plantillas externas terminada' AS resultado;
