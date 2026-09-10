-- Helpdesk Carrousel V2
-- Esquema CANONICO para instalacion limpia
-- MariaDB 10.4+
-- Fuente unica de verdad de estructura y catalogos iniciales.
-- No ejecuta migraciones historicas ni importa tickets/usuarios anteriores.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS helpdesk_carrousel
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE helpdesk_carrousel;

-- =========================================================
-- CONTROL DE ESQUEMA
-- =========================================================
CREATE TABLE schema_migrations (
    version VARCHAR(150) NOT NULL PRIMARY KEY,
    name VARCHAR(180) NULL,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- SEGURIDAD, USUARIOS Y PERMISOS
-- =========================================================
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    module VARCHAR(80) NOT NULL,
    description VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL,
    access_type ENUM('INTERNAL','EXTERNAL') NOT NULL DEFAULT 'INTERNAL',
    email VARCHAR(190) NOT NULL UNIQUE,
    full_name VARCHAR(180) NOT NULL,
    phone VARCHAR(40) NULL,
    employee_code VARCHAR(50) NULL,
    status ENUM('PENDING','ACTIVE','BLOCKED','DISABLED') NOT NULL DEFAULT 'PENDING',
    email_verified_at DATETIME NULL,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id),
    INDEX idx_users_role_status (role_id, status),
    INDEX idx_users_access_type (access_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_permission_overrides (
    user_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    effect ENUM('DENY') NOT NULL DEFAULT 'DENY',
    reason VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    PRIMARY KEY (user_id, permission_id),
    CONSTRAINT fk_upo_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_upo_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    CONSTRAINT fk_upo_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE otp_codes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    email VARCHAR(190) NOT NULL,
    purpose ENUM('LOGIN','REGISTER','VERIFY_EMAIL') NOT NULL DEFAULT 'LOGIN',
    code_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    max_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 5,
    consumed_at DATETIME NULL,
    request_ip VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_otp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_otp_email_purpose (email, purpose, created_at),
    INDEX idx_otp_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    device_name VARCHAR(150) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_sessions_user_active (user_id, revoked_at, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- ORGANIZACION
-- =========================================================
CREATE TABLE regions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE parks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    region_id BIGINT UNSIGNED NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(160) NOT NULL,
    cost_center VARCHAR(50) NULL,
    address VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_parks_region FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE SET NULL,
    INDEX idx_parks_active (is_active),
    INDEX idx_parks_cost_center (cost_center)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE areas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE positions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(140) NOT NULL,
    description VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 100,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    region_id BIGINT UNSIGNED NULL,
    park_id BIGINT UNSIGNED NULL,
    area_id BIGINT UNSIGNED NULL,
    position_id BIGINT UNSIGNED NULL,
    assignment_type ENUM('PARK','CORPORATE','OTHER') NOT NULL DEFAULT 'OTHER',
    manager_user_id BIGINT UNSIGNED NULL,
    status ENUM('ACTIVE','ENDED') NOT NULL DEFAULT 'ACTIVE',
    starts_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ends_at DATETIME NULL,
    reason VARCHAR(255) NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_assign_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_assign_region FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE SET NULL,
    CONSTRAINT fk_assign_park FOREIGN KEY (park_id) REFERENCES parks(id) ON DELETE SET NULL,
    CONSTRAINT fk_assign_area FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE SET NULL,
    CONSTRAINT fk_assign_position FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE SET NULL,
    CONSTRAINT fk_assign_manager FOREIGN KEY (manager_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_assign_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_assign_user_status (user_id, status),
    INDEX idx_assign_region_status (region_id, status),
    INDEX idx_assign_park_status (park_id, status),
    INDEX idx_assign_position (position_id),
    INDEX idx_assign_manager_status (manager_user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- ESTRUCTURA DE SOPORTE
-- =========================================================
CREATE TABLE support_teams (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE support_team_members (
    team_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ended_at DATETIME NULL,
    PRIMARY KEY (team_id, user_id),
    CONSTRAINT fk_stm_team FOREIGN KEY (team_id) REFERENCES support_teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_stm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE support_scopes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id BIGINT UNSIGNED NOT NULL,
    park_id BIGINT UNSIGNED NULL,
    area_id BIGINT UNSIGNED NULL,
    scope_type ENUM('GLOBAL','PARK','AREA','PARK_AREA') NOT NULL DEFAULT 'GLOBAL',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_scope_team FOREIGN KEY (team_id) REFERENCES support_teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_scope_park FOREIGN KEY (park_id) REFERENCES parks(id) ON DELETE CASCADE,
    CONSTRAINT fk_scope_area FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE CASCADE,
    INDEX idx_scope_team_active (team_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- CATALOGO Y SLA
-- =========================================================
CREATE TABLE ticket_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id BIGINT UNSIGNED NULL,
    code VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(255) NULL,
    default_priority ENUM('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL DEFAULT 'MEDIUM',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_category_parent FOREIGN KEY (parent_id) REFERENCES ticket_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sla_policies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    priority ENUM('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL,
    first_response_minutes INT UNSIGNED NOT NULL,
    resolution_minutes INT UNSIGNED NOT NULL,
    business_hours_only TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sla_category FOREIGN KEY (category_id) REFERENCES ticket_categories(id) ON DELETE CASCADE,
    INDEX idx_sla_lookup (category_id, priority, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- TICKETS Y CONVERSACIONES
-- =========================================================
CREATE TABLE tickets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(30) NOT NULL UNIQUE,
    case_type ENUM('NORMAL','SPECIAL') NOT NULL DEFAULT 'NORMAL',
    visibility_mode ENUM('INTERNAL','EXTERNAL_ALLOWED') NOT NULL DEFAULT 'INTERNAL',
    origin ENUM('PUBLIC_WEB','AUTHENTICATED_WEB','INTERNAL','IMPORT') NOT NULL DEFAULT 'PUBLIC_WEB',
    requester_user_id BIGINT UNSIGNED NULL,
    requester_email VARCHAR(190) NOT NULL,
    requester_name VARCHAR(180) NOT NULL,
    requester_phone VARCHAR(40) NULL,
    park_id BIGINT UNSIGNED NULL,
    area_id BIGINT UNSIGNED NULL,
    supervisor_user_id BIGINT UNSIGNED NULL,
    category_id BIGINT UNSIGNED NULL,
    support_team_id BIGINT UNSIGNED NULL,
    assigned_to BIGINT UNSIGNED NULL,
    assigned_at DATETIME NULL,
    subject VARCHAR(220) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL DEFAULT 'MEDIUM',
    status ENUM('NEW','AVAILABLE','IN_PROGRESS','PENDING','RESOLVED','CLOSED','REOPENED','CANCELLED') NOT NULL DEFAULT 'NEW',
    pending_reason_code VARCHAR(50) NULL,
    pending_note VARCHAR(500) NULL,
    sla_policy_id BIGINT UNSIGNED NULL,
    first_response_due_at DATETIME NULL,
    resolution_due_at DATETIME NULL,
    first_response_at DATETIME NULL,
    resolved_at DATETIME NULL,
    closed_at DATETIME NULL,
    source_ip VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_ticket_requester FOREIGN KEY (requester_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_ticket_park FOREIGN KEY (park_id) REFERENCES parks(id) ON DELETE SET NULL,
    CONSTRAINT fk_ticket_area FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE SET NULL,
    CONSTRAINT fk_ticket_supervisor FOREIGN KEY (supervisor_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_ticket_category FOREIGN KEY (category_id) REFERENCES ticket_categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_ticket_team FOREIGN KEY (support_team_id) REFERENCES support_teams(id) ON DELETE SET NULL,
    CONSTRAINT fk_ticket_assigned FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_ticket_sla FOREIGN KEY (sla_policy_id) REFERENCES sla_policies(id) ON DELETE SET NULL,
    INDEX idx_tickets_queue (status, assigned_to, priority, created_at),
    INDEX idx_tickets_requester_email (requester_email, created_at),
    INDEX idx_tickets_park_status (park_id, status),
    INDEX idx_tickets_assigned_status (assigned_to, status),
    INDEX idx_tickets_category_created (category_id, created_at),
    INDEX idx_tickets_resolution_due (resolution_due_at, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ticket_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(80) NOT NULL,
    actor_user_id BIGINT UNSIGNED NULL,
    actor_type ENUM('USER','PUBLIC','SYSTEM') NOT NULL DEFAULT 'USER',
    old_value LONGTEXT NULL,
    new_value LONGTEXT NULL,
    metadata_json LONGTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_event_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_events_ticket_created (ticket_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ticket_comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    author_user_id BIGINT UNSIGNED NULL,
    author_name VARCHAR(180) NULL,
    author_email VARCHAR(190) NULL,
    visibility ENUM('PUBLIC','INTERNAL','EXTERNAL') NOT NULL DEFAULT 'PUBLIC',
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_comment_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_comment_author FOREIGN KEY (author_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_comments_ticket_visibility (ticket_id, visibility, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ticket_attachments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    comment_id BIGINT UNSIGNED NULL,
    uploaded_by_user_id BIGINT UNSIGNED NULL,
    visibility ENUM('PUBLIC','INTERNAL','EXTERNAL') NOT NULL DEFAULT 'PUBLIC',
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    storage_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    size_bytes BIGINT UNSIGNED NOT NULL,
    sha256 CHAR(64) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_attachment_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_attachment_comment FOREIGN KEY (comment_id) REFERENCES ticket_comments(id) ON DELETE CASCADE,
    CONSTRAINT fk_attachment_uploader FOREIGN KEY (uploaded_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_attachments_ticket (ticket_id, visibility)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ticket_resolutions (
    ticket_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    resolution_type VARCHAR(60) NOT NULL DEFAULT 'OTHER',
    root_cause TEXT NULL,
    solution_applied TEXT NOT NULL,
    preventive_action TEXT NULL,
    is_reusable TINYINT(1) NOT NULL DEFAULT 1,
    resolved_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_resolution_ticket FOREIGN KEY(ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_resolution_user FOREIGN KEY(resolved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_resolution_type(resolution_type),
    INDEX idx_resolution_reusable(is_reusable),
    INDEX idx_resolution_user(resolved_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ticket_feedback (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    requester_user_id BIGINT UNSIGNED NULL,
    nps_score TINYINT UNSIGNED NOT NULL,
    comment VARCHAR(1000) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ticket_feedback_ticket(ticket_id),
    KEY idx_ticket_feedback_nps(nps_score),
    KEY idx_ticket_feedback_created(created_at),
    KEY idx_ticket_feedback_requester(requester_user_id),
    CONSTRAINT fk_feedback_ticket FOREIGN KEY(ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_feedback_requester FOREIGN KEY(requester_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_ticket_feedback_nps CHECK (nps_score BETWEEN 0 AND 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE external_ticket_access (
    ticket_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    can_comment TINYINT(1) NOT NULL DEFAULT 1,
    can_upload TINYINT(1) NOT NULL DEFAULT 1,
    granted_by BIGINT UNSIGNED NULL,
    granted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revoked_at DATETIME NULL,
    PRIMARY KEY (ticket_id, user_id),
    CONSTRAINT fk_eta_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_eta_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_eta_granted_by FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_eta_user_active (user_id, revoked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE external_profiles (
    user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    organization_name VARCHAR(190) NOT NULL,
    external_type ENUM('PROVIDER','PARTNER','OTHER') NOT NULL DEFAULT 'PROVIDER',
    notes VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_external_profile_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_external_profiles_org(organization_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- PROBLEMAS RECURRENTES Y CONOCIMIENTO
-- =========================================================
CREATE TABLE known_problems (
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
    INDEX idx_problem_status_category (status, category_id),
    INDEX idx_problem_park_status (park_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE problem_occurrences (
    problem_id BIGINT UNSIGNED NOT NULL,
    ticket_id BIGINT UNSIGNED NOT NULL,
    linked_by BIGINT UNSIGNED NULL,
    confidence ENUM('MANUAL','SUGGESTED','CONFIRMED') NOT NULL DEFAULT 'MANUAL',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (problem_id, ticket_id),
    CONSTRAINT fk_occ_problem FOREIGN KEY (problem_id) REFERENCES known_problems(id) ON DELETE CASCADE,
    CONSTRAINT fk_occ_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_occ_linked_by FOREIGN KEY (linked_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE problem_tags (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE known_problem_tags (
    problem_id BIGINT UNSIGNED NOT NULL,
    tag_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (problem_id, tag_id),
    CONSTRAINT fk_kpt_problem FOREIGN KEY (problem_id) REFERENCES known_problems(id) ON DELETE CASCADE,
    CONSTRAINT fk_kpt_tag FOREIGN KEY (tag_id) REFERENCES problem_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE knowledge_articles (
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
    CONSTRAINT fk_article_category FOREIGN KEY (category_id) REFERENCES ticket_categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_article_author FOREIGN KEY (author_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_article_status_visibility (status, visibility)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE problem_solutions (
    problem_id BIGINT UNSIGNED NOT NULL,
    article_id BIGINT UNSIGNED NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    linked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (problem_id, article_id),
    CONSTRAINT fk_ps_problem FOREIGN KEY (problem_id) REFERENCES known_problems(id) ON DELETE CASCADE,
    CONSTRAINT fk_ps_article FOREIGN KEY (article_id) REFERENCES knowledge_articles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE problem_attachments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    problem_id BIGINT UNSIGNED NOT NULL,
    uploaded_by_user_id BIGINT UNSIGNED NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    storage_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    size_bytes BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pa_problem FOREIGN KEY (problem_id) REFERENCES known_problems(id) ON DELETE CASCADE,
    CONSTRAINT fk_pa_uploader FOREIGN KEY (uploaded_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- NOTIFICACIONES Y AUDITORIA
-- =========================================================
CREATE TABLE notification_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_key VARCHAR(100) NOT NULL,
    ticket_id BIGINT UNSIGNED NULL,
    problem_id BIGINT UNSIGNED NULL,
    actor_user_id BIGINT UNSIGNED NULL,
    payload_json LONGTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ne_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_ne_problem FOREIGN KEY (problem_id) REFERENCES known_problems(id) ON DELETE CASCADE,
    CONSTRAINT fk_ne_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_ne_event_created (event_key, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notification_deliveries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,
    channel ENUM('EMAIL','IN_APP') NOT NULL DEFAULT 'EMAIL',
    recipient_user_id BIGINT UNSIGNED NULL,
    recipient_email VARCHAR(190) NOT NULL,
    title VARCHAR(180) NULL,
    message VARCHAR(500) NULL,
    action_url VARCHAR(500) NULL,
    status ENUM('PENDING','SENT','FAILED','SKIPPED') NOT NULL DEFAULT 'PENDING',
    attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    last_error TEXT NULL,
    sent_at DATETIME NULL,
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_nd_event FOREIGN KEY (event_id) REFERENCES notification_events(id) ON DELETE CASCADE,
    CONSTRAINT fk_nd_user FOREIGN KEY (recipient_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_nd_status_created (status, created_at),
    INDEX idx_nd_user_channel_read (recipient_user_id, channel, read_at, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_user_id BIGINT UNSIGNED NULL,
    actor_email VARCHAR(190) NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(80) NOT NULL,
    entity_id VARCHAR(80) NULL,
    source ENUM('PUBLIC_WEB','AUTHENTICATED_WEB','SYSTEM','IMPORT') NOT NULL DEFAULT 'AUTHENTICATED_WEB',
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    old_values LONGTEXT NULL,
    new_values LONGTEXT NULL,
    metadata_json LONGTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_audit_entity (entity_type, entity_id, created_at),
    INDEX idx_audit_actor (actor_user_id, created_at),
    INDEX idx_audit_action (action, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- CONTROL DE RESOLUCION
-- =========================================================
DROP TRIGGER IF EXISTS trg_tickets_require_resolution;
DELIMITER $$
CREATE TRIGGER trg_tickets_require_resolution
BEFORE UPDATE ON tickets
FOR EACH ROW
BEGIN
    IF NEW.status IN ('RESOLVED','CLOSED')
       AND OLD.status <> NEW.status
       AND NOT EXISTS (SELECT 1 FROM ticket_resolutions tr WHERE tr.ticket_id=NEW.id)
    THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Debe registrar la solucion antes de finalizar el caso';
    END IF;
END$$
DELIMITER ;

-- =========================================================
-- SEMILLAS CANONICAS
-- =========================================================
INSERT INTO roles (code, name, description) VALUES
('ADMIN','Administrador','Control total del Helpdesk Carrousel'),
('SEMIADMIN','Semiadministrador','Operacion amplia de soporte sin privilegios administrativos totales'),
('TECHNICIAN','Tecnico','Atencion y gestion de tickets dentro de su alcance'),
('MANAGEMENT','Gerencia','Consulta ejecutiva global, indicadores, informes y conocimiento; no atiende solicitudes.'),
('SUPERVISOR','Supervisor','Consulta y seguimiento dentro de su region, parque o area; no atiende solicitudes.'),
('REQUESTER','Solicitante','Usuario interno que crea y consulta sus propios tickets'),
('EXTERNAL','Externo','Acceso restringido exclusivamente a casos especiales asignados');

INSERT INTO permissions (code, name, module, description) VALUES
('tickets.create_public','Crear ticket publico','tickets','Creacion de tickets sin autenticacion'),
('tickets.view_own','Ver tickets propios','tickets','Consulta de tickets asociados al correo autenticado'),
('tickets.view_queue','Ver cola disponible','tickets','Consulta de tickets disponibles dentro del alcance'),
('tickets.claim','Tomar caso','tickets','Asignacion atomica de un ticket disponible al usuario actual'),
('tickets.reassign','Reasignar casos','tickets','Cambio de tecnico responsable'),
('tickets.change_status','Cambiar estado','tickets','Gestion del flujo de estados'),
('tickets.comment_public','Responder publicamente','tickets','Comentarios visibles para solicitante y externos autorizados'),
('tickets.comment_internal','Notas internas','tickets','Comentarios solo visibles internamente'),
('tickets.view_all','Ver todos los tickets','tickets','Consulta global sin restriccion organizacional'),
('tickets.manage_special','Gestionar casos especiales','tickets','Habilitar y administrar acceso externo'),
('tickets.resolve','Documentar y resolver tickets','tickets','Permite registrar causa, solucion y marcar un caso como resuelto.'),
('users.view','Ver usuarios','users','Consulta de usuarios'),
('users.manage','Administrar usuarios','users','Alta, activacion, bloqueo y edicion'),
('assignments.view','Ver asignaciones','organization','Consulta de asignaciones organizacionales'),
('assignments.manage','Administrar asignaciones','organization','Gestion historica de parque, area y responsable'),
('catalogs.manage','Administrar catalogos','catalogs','Parques, areas, categorias y equipos'),
('reports.view','Ver reportes','reports','Indicadores y reportes operativos'),
('reports.global','Ver reportes globales','reports','Indicadores globales'),
('management.view','Ver dashboard de gestion','MANAGEMENT','Permite consultar metricas internas del Helpdesk.'),
('external.manage','Gestionar usuarios externos','EXTERNAL','Permite crear colaboradores externos y compartir casos especiales.'),
('problems.view','Ver problemas conocidos','problems','Consulta de recurrencia'),
('problems.manage','Administrar problemas conocidos','problems','Crear, investigar y resolver problemas recurrentes'),
('knowledge.view','Ver conocimiento','knowledge','Consulta de articulos permitidos'),
('knowledge.manage','Administrar conocimiento','knowledge','Crear, editar y publicar articulos'),
('audit.view','Ver auditoria','audit','Consulta de trazabilidad del sistema'),
('sla.manage','Administrar SLA','sla','Gestion de politicas de primera respuesta y resolucion');

-- Administrador: todos los permisos.
INSERT INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p WHERE r.code='ADMIN';

-- Semiadministrador: operacion amplia y gestion.
INSERT INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.code='SEMIADMIN' AND p.code IN(
    'tickets.view_own','tickets.view_queue','tickets.claim','tickets.reassign','tickets.change_status',
    'tickets.comment_public','tickets.comment_internal','tickets.view_all','tickets.manage_special','tickets.resolve',
    'users.view','assignments.view','assignments.manage','catalogs.manage','reports.view','reports.global',
    'management.view','external.manage','problems.view','problems.manage','knowledge.view','knowledge.manage','sla.manage'
);

-- Tecnico: atencion de tickets y consulta operativa.
INSERT INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.code='TECHNICIAN' AND p.code IN(
    'tickets.view_own','tickets.view_queue','tickets.claim','tickets.change_status','tickets.resolve',
    'tickets.comment_public','tickets.comment_internal','reports.view','problems.view','knowledge.view'
);

-- Gerencia: consulta ejecutiva.
INSERT INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.code='MANAGEMENT' AND p.code IN(
    'management.view','reports.view','reports.global','users.view','assignments.view','problems.view','knowledge.view'
);

-- Supervisor: consulta dentro de alcance; conserva consulta de solicitudes propias.
INSERT INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.code='SUPERVISOR' AND p.code IN(
    'tickets.view_own','management.view','reports.view','users.view','assignments.view','problems.view','knowledge.view'
);

INSERT INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.code='REQUESTER' AND p.code IN('tickets.view_own','tickets.comment_public','knowledge.view');

INSERT INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.code='EXTERNAL' AND p.code IN('tickets.comment_public');

INSERT INTO support_teams(code,name,description)
VALUES ('IT','Soporte IT','Equipo principal de soporte de tecnologia');

INSERT INTO areas(code,name,description,is_active) VALUES
('IT','Tecnologia','Sistemas, infraestructura y soporte tecnologico',1),
('OPERATIONS','Operaciones','Operacion de parques y procesos operativos',1),
('ADMIN','Administracion','Procesos administrativos',1),
('OTHER','Otros','Casos fuera de las areas principales',1),
('MANAGEMENT','Gerencia','Area gerencial',1),
('MAINTENANCE','Mantenimiento','Mantenimiento y soporte operativo',1);

INSERT INTO positions(code,name,description,sort_order) VALUES
('PARK_USER','Colaborador de parque','Usuario operativo asignado a un parque',10),
('PARK_MANAGER','Encargado de parque','Responsable operativo de un parque',20),
('REGIONAL_SUPERVISOR','Supervisor regional','Responsable de una region o grupo de parques',30),
('MANAGEMENT','Gerencia','Responsable gerencial dentro de la estructura organizacional',40),
('ADMINISTRATION','Administracion','Personal administrativo',50),
('OPERATIONS','Operaciones','Personal de operaciones',60),
('MAINTENANCE','Mantenimiento','Personal de mantenimiento',70),
('TECHNOLOGY','Tecnologia','Personal de tecnologia / sistemas',80),
('OTHER','Otro','Otro puesto o funcion',99);

INSERT INTO ticket_categories(code,name,default_priority) VALUES
('HARDWARE','Hardware','MEDIUM'),
('SOFTWARE','Software','MEDIUM'),
('NETWORK','Red e Internet','HIGH'),
('ACCESS','Accesos y Credenciales','HIGH'),
('POS','POS y Facturacion','HIGH'),
('SEMNOX','Semnox / Parafait','HIGH'),
('REPORTS','Reportes y BI','MEDIUM'),
('OTHER','Otros','MEDIUM');

INSERT INTO sla_policies(name,category_id,priority,first_response_minutes,resolution_minutes,business_hours_only) VALUES
('SLA Baja',NULL,'LOW',480,2880,0),
('SLA Media',NULL,'MEDIUM',240,1440,0),
('SLA Alta',NULL,'HIGH',60,480,0),
('SLA Critica',NULL,'CRITICAL',15,120,0);

-- Regiones canonicas.
INSERT INTO regions(code,name,is_active) VALUES
('CC_REGION_6','Sur Occidente',1),
('CC_REGION_7','Nor Oriente',1),
('CC_REGION_8','Central',1),
('CC_REGION_9','Central Alianzas',1);

-- Parques canonicos.
INSERT INTO parks(region_id,code,name,cost_center,is_active)
SELECT r.id,'CC_PARK_504','Alturas Huehuetenango','070',1 FROM regions r WHERE r.code='CC_REGION_6'
UNION ALL SELECT r.id,'CC_PARK_451','Coatepeque','052',1 FROM regions r WHERE r.code='CC_REGION_6'
UNION ALL SELECT r.id,'CC_PARK_390','Huehuetenango','032',1 FROM regions r WHERE r.code='CC_REGION_6'
UNION ALL SELECT r.id,'CC_PARK_430','Interplaza Xela','049',1 FROM regions r WHERE r.code='CC_REGION_6'
UNION ALL SELECT r.id,'CC_PARK_331','Mazatenango','018',1 FROM regions r WHERE r.code='CC_REGION_6'
UNION ALL SELECT r.id,'CC_PARK_345','Retalhuleu','020',1 FROM regions r WHERE r.code='CC_REGION_6'
UNION ALL SELECT r.id,'CC_PARK_465','Totonicapan','056',1 FROM regions r WHERE r.code='CC_REGION_6'
UNION ALL SELECT r.id,'CC_PARK_508','Celajes Quiche','071',1 FROM regions r WHERE r.code='CC_REGION_6'
UNION ALL SELECT r.id,'CC_PARK_463','Carcha','055',1 FROM regions r WHERE r.code='CC_REGION_7'
UNION ALL SELECT r.id,'CC_PARK_378','Pradera Chiquimula','028',1 FROM regions r WHERE r.code='CC_REGION_7'
UNION ALL SELECT r.id,'CC_PARK_323','Coban','017',1 FROM regions r WHERE r.code='CC_REGION_7'
UNION ALL SELECT r.id,'CC_PARK_409','Jalapa','042',1 FROM regions r WHERE r.code='CC_REGION_7'
UNION ALL SELECT r.id,'CC_PARK_402','Jutiapa','041',1 FROM regions r WHERE r.code='CC_REGION_7'
UNION ALL SELECT r.id,'CC_PARK_512','Metroplaza Mundo Maya Peten','072',1 FROM regions r WHERE r.code='CC_REGION_7'
UNION ALL SELECT r.id,'CC_PARK_371','Puerto Barrios','026',1 FROM regions r WHERE r.code='CC_REGION_7'
UNION ALL SELECT r.id,'CC_PARK_444','Zacapa','051',1 FROM regions r WHERE r.code='CC_REGION_7'
UNION ALL SELECT r.id,'CC_PARK_480','Cayala','059',1 FROM regions r WHERE r.code='CC_REGION_8'
UNION ALL SELECT r.id,'CC_PARK_316','El Frutal','016',1 FROM regions r WHERE r.code='CC_REGION_8'
UNION ALL SELECT r.id,'CC_PARK_273','Florida','007',1 FROM regions r WHERE r.code='CC_REGION_8'
UNION ALL SELECT r.id,'CC_PARK_304','Metro Centro','013',1 FROM regions r WHERE r.code='CC_REGION_8'
UNION ALL SELECT r.id,'CC_PARK_266','Santa Clara','006',1 FROM regions r WHERE r.code='CC_REGION_8'
UNION ALL SELECT r.id,'CC_PARK_437','Vistares','050',1 FROM regions r WHERE r.code='CC_REGION_8'
UNION ALL SELECT r.id,'CC_PARK_469','Andaria','057',1 FROM regions r WHERE r.code='CC_REGION_9'
UNION ALL SELECT r.id,'CC_PARK_281','Pradera Chimaltenango','009',1 FROM regions r WHERE r.code='CC_REGION_9'
UNION ALL SELECT r.id,'CC_PARK_455','Interplaza Escuintla','053',1 FROM regions r WHERE r.code='CC_REGION_9'
UNION ALL SELECT r.id,'CC_PARK_364','Pradera Escuintla','025',1 FROM regions r WHERE r.code='CC_REGION_9'
UNION ALL SELECT r.id,'CC_PARK_459','Santa Lucia','054',1 FROM regions r WHERE r.code='CC_REGION_9'
UNION ALL SELECT r.id,'CC_PARK_476','Telares','058',1 FROM regions r WHERE r.code='CC_REGION_9'
UNION ALL SELECT r.id,'CC_PARK_516','Plaza Villa Lobos','073',1 FROM regions r WHERE r.code='CC_REGION_9';

-- Usuario administrador base. Acceso exclusivamente por OTP.
INSERT INTO users(role_id,access_type,email,full_name,status)
SELECT id,'INTERNAL','luis@carrousel.com.gt','Luis Fernando Zuniga','ACTIVE'
FROM roles WHERE code='ADMIN';

-- Equipo IT: administrador inicial como miembro y alcance global.
SET @it_team_id := (SELECT id FROM support_teams WHERE code='IT' LIMIT 1);
INSERT INTO support_team_members(team_id,user_id,is_active,joined_at,ended_at)
SELECT @it_team_id,u.id,1,NOW(),NULL
FROM users u JOIN roles r ON r.id=u.role_id
WHERE @it_team_id IS NOT NULL AND u.deleted_at IS NULL AND u.access_type='INTERNAL'
  AND r.code IN('ADMIN','SEMIADMIN','TECHNICIAN');

INSERT INTO support_scopes(team_id,park_id,area_id,scope_type,is_active)
SELECT @it_team_id,NULL,NULL,'GLOBAL',1 WHERE @it_team_id IS NOT NULL;

INSERT INTO schema_migrations(version,name) VALUES
('2026-09-09-clean-schema-v2.4','Esquema canonico Helpdesk Carrousel para instalacion limpia');

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'OK - INSTALACION CANONICA HELPDESK CARROUSEL V2' AS resultado,
       DATABASE() AS base_actual,
       (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE') AS tablas,
       (SELECT COUNT(*) FROM users WHERE deleted_at IS NULL) AS usuarios,
       (SELECT COUNT(*) FROM regions WHERE is_active=1) AS regiones,
       (SELECT COUNT(*) FROM parks WHERE is_active=1) AS parques;
