USE carrousel_helpdesk;
SET NAMES utf8mb4;

SELECT 'TABLAS_FASE5' prueba, COUNT(*) hallazgos
FROM information_schema.TABLES
WHERE TABLE_SCHEMA=DATABASE()
  AND TABLE_TYPE='BASE TABLE'
  AND TABLE_NAME IN ('ticket_activities','ticket_activity_participants');

SELECT 'ACTIVITY_ID_EN_ADJUNTOS' prueba, COUNT(*) hallazgos
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
  AND TABLE_NAME='ticket_attachments'
  AND COLUMN_NAME='activity_id';

SELECT 'FK_ADJUNTO_ACTIVIDAD' prueba, COUNT(*) hallazgos
FROM information_schema.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA=DATABASE()
  AND TABLE_NAME='ticket_attachments'
  AND CONSTRAINT_NAME='fk_ticket_attachment_activity';

SELECT 'PERMISOS_FASE5' prueba, COUNT(*) hallazgos
FROM permissions
WHERE code IN('activities.view','activities.create','activities.manage','activities.cancel');

SELECT 'MIGRACION_FASE5_REGISTRADA' prueba, COUNT(*) hallazgos
FROM schema_migrations
WHERE version='2026-09-13-fase5-actividades';

SELECT 'ACTIVIDADES_FINALIZADAS_INCOMPLETAS' prueba, COUNT(*) hallazgos
FROM ticket_activities
WHERE status='FINALIZADA'
  AND (
      started_at IS NULL OR finished_at IS NULL OR result_code IS NULL
      OR NULLIF(TRIM(work_performed),'') IS NULL
      OR NULLIF(TRIM(result_summary),'') IS NULL
  );

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

SELECT 'RESUMEN_PUBLICO_INCOMPLETO' prueba, COUNT(*) hallazgos
FROM ticket_activities
WHERE requester_visible=1 AND NULLIF(TRIM(requester_summary),'') IS NULL;

SELECT 'ADJUNTOS_ACTIVIDAD_OTRO_TICKET' prueba, COUNT(*) hallazgos
FROM ticket_attachments ta
JOIN ticket_activities act ON act.id=ta.activity_id
WHERE ta.ticket_id<>act.ticket_id;

SELECT 'OK - VERIFICACION FASE 5 ACTIVIDADES / VISITAS' AS resultado;
