-- Helpdesk Carrousel - Fase 12
-- Verificacion final de estructura migrada e idempotencia sensible.
-- SOLO LECTURA. No cambia datos.
USE carrousel_helpdesk;

SELECT DATABASE() AS base_actual;

SELECT 'MIGRACION_FASE5' control,COUNT(*) valor
FROM schema_migrations
WHERE version='2026-09-13-fase5-actividades';

SELECT 'MIGRACION_FASE9' control,COUNT(*) valor
FROM schema_migrations
WHERE version='2026-09-16-fase9-conocimiento';

SELECT 'TABLAS_ACTIVIDADES' control,COUNT(*) valor
FROM information_schema.TABLES
WHERE TABLE_SCHEMA=DATABASE()
  AND TABLE_TYPE='BASE TABLE'
  AND TABLE_NAME IN('ticket_activities','ticket_activity_participants');

SELECT 'PERMISOS_ACTIVIDADES' control,COUNT(*) valor
FROM permissions
WHERE code IN('activities.view','activities.create','activities.manage','activities.cancel');

SELECT 'PERMISOS_CONOCIMIENTO_FASE9' control,COUNT(*) valor
FROM permissions
WHERE code IN(
  'knowledge.draft_manage','knowledge.review','knowledge.publish_internal',
  'knowledge.publish_public','knowledge.history','knowledge.restore'
);

SELECT 'ARTICULOS_SIN_REVISION' control,COUNT(*) valor
FROM knowledge_articles ka
WHERE NOT EXISTS(
  SELECT 1 FROM knowledge_revisions kr WHERE kr.article_id=ka.id
);

SELECT 'ARTICULOS_SIN_FUENTE' control,COUNT(*) valor
FROM knowledge_articles ka
WHERE NOT EXISTS(
  SELECT 1 FROM knowledge_article_sources kas WHERE kas.article_id=ka.id
);

SELECT 'REVISIONES_DUPLICADAS' control,COUNT(*) valor
FROM(
  SELECT article_id,revision_number
  FROM knowledge_revisions
  GROUP BY article_id,revision_number
  HAVING COUNT(*)>1
) x;

SELECT 'PUBLICADOS_SIN_PUNTERO_INTERNO' control,COUNT(*) valor
FROM knowledge_articles
WHERE status='PUBLISHED' AND current_internal_revision_id IS NULL;

SELECT 'PUBLICOS_SIN_PUNTERO_PUBLICO' control,COUNT(*) valor
FROM knowledge_articles
WHERE status='PUBLISHED'
  AND visibility='PUBLIC'
  AND current_public_revision_id IS NULL;

SELECT 'TECHNICIAN_CON_PERMISOS_EDITORIALES_PROHIBIDOS' control,COUNT(*) valor
FROM role_permissions rp
JOIN roles r ON r.id=rp.role_id
JOIN permissions p ON p.id=rp.permission_id
WHERE r.code='TECHNICIAN'
  AND p.code IN(
    'knowledge.review','knowledge.publish_internal','knowledge.publish_public',
    'knowledge.history','knowledge.restore'
  );

SELECT CASE
  WHEN DATABASE()='carrousel_helpdesk'
   AND (SELECT COUNT(*) FROM schema_migrations WHERE version='2026-09-13-fase5-actividades')=1
   AND (SELECT COUNT(*) FROM schema_migrations WHERE version='2026-09-16-fase9-conocimiento')=1
   AND (SELECT COUNT(*) FROM information_schema.TABLES
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE'
          AND TABLE_NAME IN('ticket_activities','ticket_activity_participants'))=2
   AND (SELECT COUNT(*) FROM permissions
        WHERE code IN('activities.view','activities.create','activities.manage','activities.cancel'))=4
   AND (SELECT COUNT(*) FROM permissions
        WHERE code IN(
          'knowledge.draft_manage','knowledge.review','knowledge.publish_internal',
          'knowledge.publish_public','knowledge.history','knowledge.restore'
        ))=6
   AND (SELECT COUNT(*) FROM knowledge_articles ka
        WHERE NOT EXISTS(SELECT 1 FROM knowledge_revisions kr WHERE kr.article_id=ka.id))=0
   AND (SELECT COUNT(*) FROM knowledge_articles ka
        WHERE NOT EXISTS(SELECT 1 FROM knowledge_article_sources kas WHERE kas.article_id=ka.id))=0
   AND (SELECT COUNT(*) FROM(
          SELECT article_id,revision_number
          FROM knowledge_revisions
          GROUP BY article_id,revision_number
          HAVING COUNT(*)>1
        ) d)=0
   AND (SELECT COUNT(*) FROM knowledge_articles
        WHERE status='PUBLISHED' AND current_internal_revision_id IS NULL)=0
   AND (SELECT COUNT(*) FROM knowledge_articles
        WHERE status='PUBLISHED' AND visibility='PUBLIC'
          AND current_public_revision_id IS NULL)=0
   AND (SELECT COUNT(*) FROM role_permissions rp
        JOIN roles r ON r.id=rp.role_id
        JOIN permissions p ON p.id=rp.permission_id
        WHERE r.code='TECHNICIAN'
          AND p.code IN(
            'knowledge.review','knowledge.publish_internal','knowledge.publish_public',
            'knowledge.history','knowledge.restore'
          ))=0
  THEN 'PASS'
  ELSE 'FAIL'
END AS fase12_db_gate;
