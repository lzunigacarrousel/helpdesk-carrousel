-- Helpdesk Carrousel 360
-- Verificacion de instalacion NUEVA de produccion.
-- Solo lectura: no modifica datos.
USE carrousel_helpdesk;
SET NAMES utf8mb4;

SELECT 'USUARIOS_ACTIVOS' control,COUNT(*) valor
FROM users WHERE deleted_at IS NULL;

SELECT 'ADMIN_INICIAL' control,COUNT(*) valor
FROM users u
JOIN roles r ON r.id=u.role_id
WHERE LOWER(u.email)='luis@carrousel.com.gt'
  AND r.code='ADMIN'
  AND u.access_type='INTERNAL'
  AND u.status='ACTIVE'
  AND u.deleted_at IS NULL;

SELECT 'DATOS_OPERATIVOS' control,
(
    (SELECT COUNT(*) FROM otp_codes) +
    (SELECT COUNT(*) FROM user_sessions) +
    (SELECT COUNT(*) FROM user_assignments) +
    (SELECT COUNT(*) FROM tickets) +
    (SELECT COUNT(*) FROM ticket_events) +
    (SELECT COUNT(*) FROM ticket_comments) +
    (SELECT COUNT(*) FROM ticket_attachments) +
    (SELECT COUNT(*) FROM ticket_resolutions) +
    (SELECT COUNT(*) FROM ticket_feedback) +
    (SELECT COUNT(*) FROM external_ticket_access) +
    (SELECT COUNT(*) FROM external_profiles) +
    (SELECT COUNT(*) FROM known_problems) +
    (SELECT COUNT(*) FROM problem_occurrences) +
    (SELECT COUNT(*) FROM problem_tags) +
    (SELECT COUNT(*) FROM known_problem_tags) +
    (SELECT COUNT(*) FROM knowledge_articles) +
    (SELECT COUNT(*) FROM knowledge_revisions) +
    (SELECT COUNT(*) FROM knowledge_article_sources) +
    (SELECT COUNT(*) FROM ticket_resolution_references) +
    (SELECT COUNT(*) FROM solution_suggestion_events) +
    (SELECT COUNT(*) FROM problem_solutions) +
    (SELECT COUNT(*) FROM notification_events) +
    (SELECT COUNT(*) FROM notification_deliveries) +
    (SELECT COUNT(*) FROM audit_logs) +
    (SELECT COUNT(*) FROM ticket_work_reports) +
    (SELECT COUNT(*) FROM ticket_activities) +
    (SELECT COUNT(*) FROM ticket_activity_participants)
) valor;

SELECT 'TABLAS_LEGACY' control,COUNT(*) valor
FROM information_schema.TABLES
WHERE TABLE_SCHEMA=DATABASE()
  AND TABLE_TYPE='BASE TABLE'
  AND TABLE_NAME IN('problem_attachments');

SELECT 'COLUMNAS_LEGACY_CONOCIMIENTO' control,COUNT(*) valor
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
  AND TABLE_NAME='knowledge_articles'
  AND COLUMN_NAME IN(
      'title','summary','content','status','visibility',
      'category_id','author_user_id','published_at'
  );

SELECT 'PERMISOS_LEGACY' control,COUNT(*) valor
FROM permissions
WHERE code='knowledge.manage';

SELECT 'VERSION_PREPROD' control,COUNT(*) valor
FROM schema_migrations
WHERE version='2026-09-19-preprod-clean-schema';

SELECT CASE
    WHEN DATABASE()='carrousel_helpdesk'
     AND (SELECT COUNT(*) FROM users WHERE deleted_at IS NULL)=1
     AND (SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id
          WHERE LOWER(u.email)='luis@carrousel.com.gt'
            AND r.code='ADMIN'
            AND u.access_type='INTERNAL'
            AND u.status='ACTIVE'
            AND u.deleted_at IS NULL)=1
     AND (
        (SELECT COUNT(*) FROM otp_codes) +
        (SELECT COUNT(*) FROM user_sessions) +
        (SELECT COUNT(*) FROM user_assignments) +
        (SELECT COUNT(*) FROM tickets) +
        (SELECT COUNT(*) FROM ticket_events) +
        (SELECT COUNT(*) FROM ticket_comments) +
        (SELECT COUNT(*) FROM ticket_attachments) +
        (SELECT COUNT(*) FROM ticket_resolutions) +
        (SELECT COUNT(*) FROM ticket_feedback) +
        (SELECT COUNT(*) FROM external_ticket_access) +
        (SELECT COUNT(*) FROM external_profiles) +
        (SELECT COUNT(*) FROM known_problems) +
        (SELECT COUNT(*) FROM problem_occurrences) +
        (SELECT COUNT(*) FROM problem_tags) +
        (SELECT COUNT(*) FROM known_problem_tags) +
        (SELECT COUNT(*) FROM knowledge_articles) +
        (SELECT COUNT(*) FROM knowledge_revisions) +
        (SELECT COUNT(*) FROM knowledge_article_sources) +
        (SELECT COUNT(*) FROM ticket_resolution_references) +
        (SELECT COUNT(*) FROM solution_suggestion_events) +
        (SELECT COUNT(*) FROM problem_solutions) +
        (SELECT COUNT(*) FROM notification_events) +
        (SELECT COUNT(*) FROM notification_deliveries) +
        (SELECT COUNT(*) FROM audit_logs) +
        (SELECT COUNT(*) FROM ticket_work_reports) +
        (SELECT COUNT(*) FROM ticket_activities) +
        (SELECT COUNT(*) FROM ticket_activity_participants)
     )=0
     AND (SELECT COUNT(*) FROM information_schema.TABLES
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE'
            AND TABLE_NAME='problem_attachments')=0
     AND (SELECT COUNT(*) FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE()
            AND TABLE_NAME='knowledge_articles'
            AND COLUMN_NAME IN(
                'title','summary','content','status','visibility',
                'category_id','author_user_id','published_at'
            ))=0
     AND (SELECT COUNT(*) FROM permissions WHERE code='knowledge.manage')=0
     AND (SELECT COUNT(*) FROM schema_migrations
          WHERE version='2026-09-19-preprod-clean-schema')=1
    THEN 'PASS'
    ELSE 'FAIL'
END AS produccion_limpia_gate;
