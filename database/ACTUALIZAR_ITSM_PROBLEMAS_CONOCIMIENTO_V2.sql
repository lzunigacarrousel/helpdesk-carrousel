-- Helpdesk Carrousel V2 · ITSM 1
-- Problem Management + Knowledge Management
-- Incremental, no destructivo e idempotente para TEST

USE helpdesk_carrousel_test;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS schema_migrations (
    version VARCHAR(60) NOT NULL PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS known_problems (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    problem_number VARCHAR(30) NOT NULL UNIQUE,
    title VARCHAR(220) NOT NULL,
    description TEXT NOT NULL,
    root_cause TEXT NULL,
    workaround TEXT NULL,
    permanent_solution TEXT NULL,
    status ENUM('OPEN','INVESTIGATING','WORKAROUND','PERMANENT_SOLUTION','CLOSED') NOT NULL DEFAULT 'OPEN',
    category_id BIGINT UNSIGNED NULL,
    park_id BIGINT UNSIGNED NULL,
    owner_user_id BIGINT UNSIGNED NULL,
    occurrence_count INT UNSIGNED NOT NULL DEFAULT 0,
    first_seen_at DATETIME NULL,
    last_seen_at DATETIME NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_problem_category FOREIGN KEY (category_id) REFERENCES ticket_categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_problem_park FOREIGN KEY (park_id) REFERENCES parks(id) ON DELETE SET NULL,
    CONSTRAINT fk_problem_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_problem_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_problem_status_category (status,category_id),
    INDEX idx_problem_park_status (park_id,status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS problem_occurrences (
    problem_id BIGINT UNSIGNED NOT NULL,
    ticket_id BIGINT UNSIGNED NOT NULL,
    linked_by BIGINT UNSIGNED NULL,
    confidence ENUM('MANUAL','SUGGESTED','CONFIRMED') NOT NULL DEFAULT 'MANUAL',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(problem_id,ticket_id),
    CONSTRAINT fk_occ_problem FOREIGN KEY(problem_id) REFERENCES known_problems(id) ON DELETE CASCADE,
    CONSTRAINT fk_occ_ticket FOREIGN KEY(ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_occ_linked_by FOREIGN KEY(linked_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_occ_ticket (ticket_id,created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS problem_tags (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS known_problem_tags (
    problem_id BIGINT UNSIGNED NOT NULL,
    tag_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY(problem_id,tag_id),
    CONSTRAINT fk_kpt_problem FOREIGN KEY(problem_id) REFERENCES known_problems(id) ON DELETE CASCADE,
    CONSTRAINT fk_kpt_tag FOREIGN KEY(tag_id) REFERENCES problem_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS knowledge_articles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_number VARCHAR(30) NOT NULL UNIQUE,
    title VARCHAR(220) NOT NULL,
    summary TEXT NULL,
    content LONGTEXT NOT NULL,
    status ENUM('DRAFT','PUBLISHED','ARCHIVED') NOT NULL DEFAULT 'DRAFT',
    visibility ENUM('INTERNAL','PUBLIC') NOT NULL DEFAULT 'INTERNAL',
    category_id BIGINT UNSIGNED NULL,
    author_user_id BIGINT UNSIGNED NULL,
    published_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_article_category FOREIGN KEY(category_id) REFERENCES ticket_categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_article_author FOREIGN KEY(author_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_article_status_visibility(status,visibility),
    INDEX idx_article_category_updated(category_id,updated_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS problem_solutions (
    problem_id BIGINT UNSIGNED NOT NULL,
    article_id BIGINT UNSIGNED NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    linked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(problem_id,article_id),
    CONSTRAINT fk_ps_problem FOREIGN KEY(problem_id) REFERENCES known_problems(id) ON DELETE CASCADE,
    CONSTRAINT fk_ps_article FOREIGN KEY(article_id) REFERENCES knowledge_articles(id) ON DELETE CASCADE,
    INDEX idx_ps_article(article_id,is_primary)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS problem_attachments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    problem_id BIGINT UNSIGNED NOT NULL,
    uploaded_by_user_id BIGINT UNSIGNED NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    storage_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    size_bytes BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pa_problem FOREIGN KEY(problem_id) REFERENCES known_problems(id) ON DELETE CASCADE,
    CONSTRAINT fk_pa_uploader FOREIGN KEY(uploaded_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO permissions(code,name,module,description) VALUES
('problems.view','Ver problemas conocidos','problems','Consulta de recurrencia'),
('problems.manage','Administrar problemas conocidos','problems','Crear, investigar y resolver problemas recurrentes'),
('knowledge.view','Ver conocimiento','knowledge','Consulta de artículos permitidos'),
('knowledge.manage','Administrar conocimiento','knowledge','Crear, editar y publicar artículos');

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.code IN('problems.view','problems.manage','knowledge.view','knowledge.manage') WHERE r.code='ADMIN';
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.code IN('problems.view','problems.manage','knowledge.view','knowledge.manage') WHERE r.code='SEMIADMIN';
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.code IN('problems.view','knowledge.view') WHERE r.code IN('TECHNICIAN','MANAGEMENT','SUPERVISOR');
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.code='knowledge.view' WHERE r.code='REQUESTER';

INSERT IGNORE INTO schema_migrations(version,name)
VALUES('ITSM1-20260909-001','Problem Management y Knowledge Management');

SELECT 'OK - ITSM Problemas + Conocimiento V2' AS resultado,
       (SELECT COUNT(*) FROM known_problems) AS problemas,
       (SELECT COUNT(*) FROM knowledge_articles) AS articulos,
       (SELECT COUNT(*) FROM schema_migrations) AS migraciones_registradas;
