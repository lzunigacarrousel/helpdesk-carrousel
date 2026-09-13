USE carrousel_helpdesk;
SET NAMES utf8mb4;

SELECT CASE
  WHEN COUNT(*)=1 THEN 'OK - ticket_work_reports existe'
  ELSE 'ERROR - ticket_work_reports no existe'
END AS resultado
FROM information_schema.TABLES
WHERE TABLE_SCHEMA=DATABASE()
  AND TABLE_NAME='ticket_work_reports';

SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
  AND TABLE_NAME='ticket_work_reports'
ORDER BY ORDINAL_POSITION;

SELECT CASE
  WHEN COUNT(*)=19 THEN 'OK - columnas requeridas disponibles'
  ELSE CONCAT('ERROR - columnas encontradas: ',COUNT(*),' de 19')
END AS resultado
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
  AND TABLE_NAME='ticket_work_reports'
  AND COLUMN_NAME IN (
    'ticket_id','author_user_id','author_access_type','report_type','progress_percent','diagnosis','root_cause','actions_performed',
    'parts_materials','configuration_changes','tests_performed','result_summary','pending_items','preventive_recommendation','provider_reference',
    'time_spent_minutes','commitment_at','ready_for_review','comment_id'
  );

SELECT CASE
  WHEN EXISTS(
    SELECT 1 FROM schema_migrations WHERE version='2026-09-12-ticket-work-reports'
  ) THEN 'OK - migracion registrada'
  ELSE 'ERROR - migracion no registrada'
END AS resultado;
