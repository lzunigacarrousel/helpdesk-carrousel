-- Helpdesk Carrousel - Fase 9: Conocimiento versionado
-- Fecha: 2026-09-16
-- MariaDB 10.4+
-- Migracion incremental, aditiva e idempotente.
-- NO elimina columnas legacy de knowledge_articles.
-- Ejecutar primero y unicamente en PC TEST.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

USE carrousel_helpdesk;

-- =========================================================
-- 1. REVISIONES DE CONOCIMIENTO
-- =========================================================
CREATE TABLE IF NOT EXISTS knowledge_revisions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id BIGINT UNSIGNED NOT NULL,
    revision_number INT UNSIGNED NOT NULL,
    state ENUM('DRAFT','IN_REVIEW','PUBLISHED') NOT NULL DEFAULT 'DRAFT',
    title VARCHAR(220) NOT NULL,
    summary TEXT NULL,
    content LONGTEXT NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    based_on_revision_id BIGINT UNSIGNED NULL,
    created_by_user_id BIGINT UNSIGNED NULL,
    change_note VARCHAR(500) NULL,
    submitted_by_user_id BIGINT UNSIGNED NULL,
    submitted_at DATETIME NULL,
    reviewed_by_user_id BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    review_note VARCHAR(500) NULL,
    internal_published_by_user_id BIGINT UNSIGNED NULL,
    internal_published_at DATETIME NULL,
    public_published_by_user_id BIGINT UNSIGNED NULL,
    public_published_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_kr_article FOREIGN KEY (article_id) REFERENCES knowledge_articles(id) ON DELETE CASCADE,
    CONSTRAINT fk_kr_category FOREIGN KEY (category_id) REFERENCES ticket_categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_kr_based_on FOREIGN KEY (based_on_revision_id) REFERENCES knowledge_revisions(id) ON DELETE SET NULL,
    CONSTRAINT fk_kr_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_kr_submitted_by FOREIGN KEY (submitted_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_kr_reviewed_by FOREIGN KEY (reviewed_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_kr_internal_published_by FOREIGN KEY (internal_published_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_kr_public_published_by FOREIGN KEY (public_published_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uq_kr_article_revision (article_id, revision_number),
    INDEX idx_kr_article_state (article_id, state, revision_number),
    INDEX idx_kr_category_state (category_id, state)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE knowledge_articles
    ADD COLUMN IF NOT EXISTS lifecycle_status ENUM('ACTIVE','ARCHIVED') NOT NULL DEFAULT 'ACTIVE' AFTER article_number,
    ADD COLUMN IF NOT EXISTS current_internal_revision_id BIGINT UNSIGNED NULL AFTER lifecycle_status,
    ADD COLUMN IF NOT EXISTS current_public_revision_id BIGINT UNSIGNED NULL AFTER current_internal_revision_id,
    ADD COLUMN IF NOT EXISTS created_by_user_id BIGINT UNSIGNED NULL AFTER current_public_revision_id,
    ADD COLUMN IF NOT EXISTS archived_at DATETIME NULL AFTER published_at;

CREATE INDEX IF NOT EXISTS idx_ka_lifecycle_internal ON knowledge_articles(lifecycle_status,current_internal_revision_id);
CREATE INDEX IF NOT EXISTS idx_ka_lifecycle_public ON knowledge_articles(lifecycle_status,current_public_revision_id);

UPDATE knowledge_articles
SET created_by_user_id=COALESCE(created_by_user_id,author_user_id),
    lifecycle_status=CASE WHEN status='ARCHIVED' THEN 'ARCHIVED' ELSE 'ACTIVE' END,
    archived_at=CASE
        WHEN status='ARCHIVED' THEN COALESCE(archived_at,updated_at,created_at)
        ELSE archived_at
    END;

-- Cada articulo legacy genera exactamente REV 1 una sola vez.
INSERT INTO knowledge_revisions(
    article_id,revision_number,state,title,summary,content,category_id,
    based_on_revision_id,created_by_user_id,change_note,
    submitted_by_user_id,submitted_at,reviewed_by_user_id,reviewed_at,review_note,
    internal_published_by_user_id,internal_published_at,
    public_published_by_user_id,public_published_at,
    created_at,updated_at
)
SELECT
    ka.id,
    1,
    CASE WHEN ka.status='DRAFT' THEN 'DRAFT' ELSE 'PUBLISHED' END,
    ka.title,
    ka.summary,
    ka.content,
    ka.category_id,
    NULL,
    COALESCE(ka.created_by_user_id,ka.author_user_id),
    'Migracion Fase 9 desde articulo legacy',
    NULL,
    NULL,
    CASE WHEN ka.status IN('PUBLISHED','ARCHIVED') THEN ka.author_user_id ELSE NULL END,
    CASE WHEN ka.status IN('PUBLISHED','ARCHIVED') THEN COALESCE(ka.published_at,ka.updated_at) ELSE NULL END,
    NULL,
    CASE WHEN ka.status IN('PUBLISHED','ARCHIVED') THEN ka.author_user_id ELSE NULL END,
    CASE WHEN ka.status IN('PUBLISHED','ARCHIVED') THEN COALESCE(ka.published_at,ka.updated_at) ELSE NULL END,
    CASE WHEN ka.status='PUBLISHED' AND ka.visibility='PUBLIC' THEN ka.author_user_id ELSE NULL END,
    CASE WHEN ka.status='PUBLISHED' AND ka.visibility='PUBLIC' THEN COALESCE(ka.published_at,ka.updated_at) ELSE NULL END,
    ka.created_at,
    ka.updated_at
FROM knowledge_articles ka
WHERE NOT EXISTS (
    SELECT 1
    FROM knowledge_revisions kr
    WHERE kr.article_id=ka.id
      AND kr.revision_number=1
);

-- Punteros vigentes. Los archivados conservan revision pero no se exponen.
UPDATE knowledge_articles ka
JOIN knowledge_revisions kr
  ON kr.article_id=ka.id
 AND kr.revision_number=1
SET ka.current_internal_revision_id=kr.id
WHERE ka.status='PUBLISHED'
  AND ka.current_internal_revision_id IS NULL;

UPDATE knowledge_articles ka
JOIN knowledge_revisions kr
  ON kr.article_id=ka.id
 AND kr.revision_number=1
SET ka.current_public_revision_id=kr.id
WHERE ka.status='PUBLISHED'
  AND ka.visibility='PUBLIC'
  AND ka.current_public_revision_id IS NULL;

-- =========================================================
-- 2. ORIGEN ESTRUCTURADO DEL ARTICULO
-- =========================================================
CREATE TABLE IF NOT EXISTS knowledge_article_sources (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id BIGINT UNSIGNED NOT NULL,
    source_type ENUM('TICKET','PROBLEM','MANUAL') NOT NULL DEFAULT 'MANUAL',
    source_ticket_id BIGINT UNSIGNED NULL,
    source_problem_id BIGINT UNSIGNED NULL,
    created_by_user_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_kas_article FOREIGN KEY (article_id) REFERENCES knowledge_articles(id) ON DELETE CASCADE,
    CONSTRAINT fk_kas_ticket FOREIGN KEY (source_ticket_id) REFERENCES tickets(id) ON DELETE SET NULL,
    CONSTRAINT fk_kas_problem FOREIGN KEY (source_problem_id) REFERENCES known_problems(id) ON DELETE SET NULL,
    CONSTRAINT fk_kas_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_kas_article_created (article_id,created_at),
    INDEX idx_kas_ticket (source_ticket_id),
    INDEX idx_kas_problem (source_problem_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backfill conservador desde relaciones existentes problem_solutions.
INSERT INTO knowledge_article_sources(article_id,source_type,source_problem_id,created_by_user_id,created_at)
SELECT ps.article_id,'PROBLEM',ps.problem_id,NULL,ps.linked_at
FROM problem_solutions ps
WHERE NOT EXISTS (
    SELECT 1
    FROM knowledge_article_sources kas
    WHERE kas.article_id=ps.article_id
      AND kas.source_type='PROBLEM'
      AND kas.source_problem_id=ps.problem_id
);

-- Articulos sin fuente estructurada quedan marcados como MANUAL.
INSERT INTO knowledge_article_sources(article_id,source_type,created_by_user_id,created_at)
SELECT ka.id,'MANUAL',COALESCE(ka.created_by_user_id,ka.author_user_id),ka.created_at
FROM knowledge_articles ka
WHERE NOT EXISTS (
    SELECT 1 FROM knowledge_article_sources kas WHERE kas.article_id=ka.id
);

-- =========================================================
-- 3. REFERENCIAS USADAS EN LA RESOLUCION
-- =========================================================
CREATE TABLE IF NOT EXISTS ticket_resolution_references (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    reference_type ENUM('KNOWLEDGE','PROBLEM','TICKET') NOT NULL,
    knowledge_article_id BIGINT UNSIGNED NULL,
    knowledge_revision_id BIGINT UNSIGNED NULL,
    problem_id BIGINT UNSIGNED NULL,
    source_ticket_id BIGINT UNSIGNED NULL,
    used_by_user_id BIGINT UNSIGNED NULL,
    used_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    applied_root_cause TINYINT(1) NOT NULL DEFAULT 0,
    applied_solution TINYINT(1) NOT NULL DEFAULT 0,
    applied_prevention TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_trr_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_trr_article FOREIGN KEY (knowledge_article_id) REFERENCES knowledge_articles(id) ON DELETE SET NULL,
    CONSTRAINT fk_trr_revision FOREIGN KEY (knowledge_revision_id) REFERENCES knowledge_revisions(id) ON DELETE SET NULL,
    CONSTRAINT fk_trr_problem FOREIGN KEY (problem_id) REFERENCES known_problems(id) ON DELETE SET NULL,
    CONSTRAINT fk_trr_source_ticket FOREIGN KEY (source_ticket_id) REFERENCES tickets(id) ON DELETE SET NULL,
    CONSTRAINT fk_trr_used_by FOREIGN KEY (used_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_trr_ticket_used (ticket_id,used_at),
    INDEX idx_trr_article_revision (knowledge_article_id,knowledge_revision_id),
    INDEX idx_trr_problem (problem_id),
    INDEX idx_trr_source_ticket (source_ticket_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 4. EVENTOS DE SUGERENCIA / USO
-- =========================================================
CREATE TABLE IF NOT EXISTS solution_suggestion_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NULL,
    actor_user_id BIGINT UNSIGNED NULL,
    context ENUM('INTERNAL_TICKET','SELF_SERVICE') NOT NULL,
    event_type ENUM('SUGGESTED','OPENED','USED_REFERENCE') NOT NULL,
    reference_type ENUM('KNOWLEDGE','PROBLEM','TICKET') NOT NULL,
    knowledge_article_id BIGINT UNSIGNED NULL,
    knowledge_revision_id BIGINT UNSIGNED NULL,
    problem_id BIGINT UNSIGNED NULL,
    source_ticket_id BIGINT UNSIGNED NULL,
    rank_position SMALLINT UNSIGNED NULL,
    score SMALLINT UNSIGNED NULL,
    metadata_json LONGTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sse_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_sse_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_sse_article FOREIGN KEY (knowledge_article_id) REFERENCES knowledge_articles(id) ON DELETE SET NULL,
    CONSTRAINT fk_sse_revision FOREIGN KEY (knowledge_revision_id) REFERENCES knowledge_revisions(id) ON DELETE SET NULL,
    CONSTRAINT fk_sse_problem FOREIGN KEY (problem_id) REFERENCES known_problems(id) ON DELETE SET NULL,
    CONSTRAINT fk_sse_source_ticket FOREIGN KEY (source_ticket_id) REFERENCES tickets(id) ON DELETE SET NULL,
    INDEX idx_sse_ticket_created (ticket_id,created_at),
    INDEX idx_sse_context_event (context,event_type,created_at),
    INDEX idx_sse_article_revision (knowledge_article_id,knowledge_revision_id),
    INDEX idx_sse_problem (problem_id),
    INDEX idx_sse_source_ticket (source_ticket_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 5. PERMISOS EDITORIALES FASE 9
-- =========================================================
INSERT INTO permissions(code,name,module,description) VALUES
('knowledge.draft_manage','Crear y editar borradores','knowledge','Permite crear y mejorar revisiones borrador de conocimiento.'),
('knowledge.review','Revisar conocimiento','knowledge','Permite revisar borradores enviados y devolverlos con observaciones.'),
('knowledge.publish_internal','Publicar para soporte','knowledge','Permite publicar una revision como vigente para uso interno.'),
('knowledge.publish_public','Habilitar para solicitantes','knowledge','Permite habilitar una revision ya publicada internamente para autoservicio.'),
('knowledge.history','Ver historial de conocimiento','knowledge','Permite consultar y comparar todas las revisiones.'),
('knowledge.restore','Restaurar conocimiento','knowledge','Permite crear un nuevo borrador a partir de una revision historica.')
ON DUPLICATE KEY UPDATE
    name=VALUES(name),
    module=VALUES(module),
    description=VALUES(description);

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id
FROM roles r
JOIN permissions p ON p.code IN(
    'knowledge.view','knowledge.draft_manage'
)
WHERE r.code='TECHNICIAN';

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id
FROM roles r
JOIN permissions p ON p.code IN(
    'knowledge.view','knowledge.draft_manage','knowledge.review',
    'knowledge.publish_internal','knowledge.publish_public',
    'knowledge.history','knowledge.restore'
)
WHERE r.code='SEMIADMIN';

-- ADMIN ya recibe todos los permisos por politica canonica; para instalaciones
-- migradas se garantiza explicitamente el enlace a los permisos nuevos.
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id
FROM roles r
JOIN permissions p ON p.code LIKE 'knowledge.%'
WHERE r.code='ADMIN';

INSERT IGNORE INTO schema_migrations(version,name,applied_at)
VALUES('2026-09-16-fase9-conocimiento','Fase 9 - Conocimiento versionado',NOW());

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'OK - MIGRACION FASE 9 CONOCIMIENTO PREPARADA' AS resultado;
