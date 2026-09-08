-- Helpdesk Carrousel V2
-- Conversacion publica/interna y archivos por ticket

USE helpdesk_carrousel_test;

CREATE TABLE IF NOT EXISTS ticket_comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    author_user_id BIGINT UNSIGNED NULL,
    author_name VARCHAR(180) NULL,
    author_email VARCHAR(190) NULL,
    visibility ENUM('PUBLIC','INTERNAL') NOT NULL DEFAULT 'PUBLIC',
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_comment_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_comment_author FOREIGN KEY (author_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_comments_ticket_visibility (ticket_id, visibility, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ticket_attachments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    comment_id BIGINT UNSIGNED NULL,
    uploaded_by_user_id BIGINT UNSIGNED NULL,
    visibility ENUM('PUBLIC','INTERNAL') NOT NULL DEFAULT 'PUBLIC',
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
) ENGINE=InnoDB;

SELECT 'OK - Conversacion V2' AS resultado;
