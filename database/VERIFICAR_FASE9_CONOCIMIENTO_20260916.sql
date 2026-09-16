-- Helpdesk Carrousel - Verificacion Fase 9: Conocimiento versionado
-- Ejecutar despues de MIGRAR_FASE9_CONOCIMIENTO_20260916.sql en PC TEST.

USE carrousel_helpdesk;

SELECT 'ARTICULOS_LEGACY' AS control, COUNT(*) AS valor
FROM knowledge_articles;

SELECT 'ARTICULOS_CON_REVISION' AS control, COUNT(DISTINCT article_id) AS valor
FROM knowledge_revisions;

SELECT 'REVISIONES_TOTALES' AS control, COUNT(*) AS valor
FROM knowledge_revisions;

SELECT 'published_without_internal_pointer' AS control, COUNT(*) AS valor
FROM knowledge_articles
WHERE status='PUBLISHED'
  AND current_internal_revision_id IS NULL;

SELECT 'public_without_public_pointer' AS control, COUNT(*) AS valor
FROM knowledge_articles
WHERE status='PUBLISHED'
  AND visibility='PUBLIC'
  AND current_public_revision_id IS NULL;

SELECT 'archived_with_active_lifecycle' AS control, COUNT(*) AS valor
FROM knowledge_articles
WHERE status='ARCHIVED'
  AND lifecycle_status<>'ARCHIVED';

SELECT 'internal_pointer_wrong_article' AS control, COUNT(*) AS valor
FROM knowledge_articles ka
JOIN knowledge_revisions kr ON kr.id=ka.current_internal_revision_id
WHERE kr.article_id<>ka.id;

SELECT 'public_pointer_wrong_article' AS control, COUNT(*) AS valor
FROM knowledge_articles ka
JOIN knowledge_revisions kr ON kr.id=ka.current_public_revision_id
WHERE kr.article_id<>ka.id;

SELECT 'public_pointer_not_published' AS control, COUNT(*) AS valor
FROM knowledge_articles ka
JOIN knowledge_revisions kr ON kr.id=ka.current_public_revision_id
WHERE kr.state<>'PUBLISHED'
   OR kr.internal_published_at IS NULL;

SELECT 'problem_solutions' AS control, COUNT(*) AS valor
FROM problem_solutions;

SELECT 'knowledge_article_sources' AS control, COUNT(*) AS valor
FROM knowledge_article_sources;

SELECT 'articles_without_source' AS control, COUNT(*) AS valor
FROM knowledge_articles ka
WHERE NOT EXISTS (
    SELECT 1 FROM knowledge_article_sources kas WHERE kas.article_id=ka.id
);

SELECT 'duplicate_revision_numbers' AS control, COUNT(*) AS valor
FROM (
    SELECT article_id,revision_number,COUNT(*) c
    FROM knowledge_revisions
    GROUP BY article_id,revision_number
    HAVING COUNT(*)>1
) d;

SELECT 'permissions_phase9' AS control, COUNT(*) AS valor
FROM permissions
WHERE code IN(
    'knowledge.draft_manage','knowledge.review','knowledge.publish_internal',
    'knowledge.publish_public','knowledge.history','knowledge.restore'
);

SELECT 'technician_forbidden_publish_permissions' AS control, COUNT(*) AS valor
FROM role_permissions rp
JOIN roles r ON r.id=rp.role_id
JOIN permissions p ON p.id=rp.permission_id
WHERE r.code='TECHNICIAN'
  AND p.code IN('knowledge.review','knowledge.publish_internal','knowledge.publish_public','knowledge.history','knowledge.restore');

SELECT 'semiadmin_expected_permissions' AS control, COUNT(*) AS valor
FROM role_permissions rp
JOIN roles r ON r.id=rp.role_id
JOIN permissions p ON p.id=rp.permission_id
WHERE r.code='SEMIADMIN'
  AND p.code IN(
      'knowledge.draft_manage','knowledge.review','knowledge.publish_internal',
      'knowledge.publish_public','knowledge.history','knowledge.restore'
  );

SELECT
    CASE
        WHEN (SELECT COUNT(*) FROM knowledge_articles)=
             (SELECT COUNT(DISTINCT article_id) FROM knowledge_revisions)
         AND (SELECT COUNT(*) FROM knowledge_articles WHERE status='PUBLISHED' AND current_internal_revision_id IS NULL)=0
         AND (SELECT COUNT(*) FROM knowledge_articles WHERE status='PUBLISHED' AND visibility='PUBLIC' AND current_public_revision_id IS NULL)=0
         AND (SELECT COUNT(*) FROM knowledge_articles ka WHERE NOT EXISTS (SELECT 1 FROM knowledge_article_sources kas WHERE kas.article_id=ka.id))=0
         AND (SELECT COUNT(*) FROM role_permissions rp JOIN roles r ON r.id=rp.role_id JOIN permissions p ON p.id=rp.permission_id WHERE r.code='TECHNICIAN' AND p.code IN('knowledge.review','knowledge.publish_internal','knowledge.publish_public','knowledge.history','knowledge.restore'))=0
        THEN 'PASS'
        ELSE 'FAIL'
    END AS fase9_schema_gate;
