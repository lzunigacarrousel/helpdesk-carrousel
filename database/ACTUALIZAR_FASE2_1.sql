-- Helpdesk Carrousel 360 · Fase 2.1
-- Migración NO destructiva. Crea el núcleo inicial de tickets.
USE helpdesk360_test;

CREATE TABLE IF NOT EXISTS ticket_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  description VARCHAR(255) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order SMALLINT NOT NULL DEFAULT 100,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_ticket_categories_name(name),
  KEY idx_ticket_categories_active(active,sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tickets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_number VARCHAR(30) NULL,
  requester_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NOT NULL,
  park_id BIGINT UNSIGNED NULL,
  area_id BIGINT UNSIGNED NULL,
  assigned_to BIGINT UNSIGNED NULL,
  assigned_team_id BIGINT UNSIGNED NULL,
  subject VARCHAR(220) NOT NULL,
  description TEXT NOT NULL,
  priority ENUM('BAJA','MEDIA','ALTA','CRITICA') NOT NULL DEFAULT 'MEDIA',
  status ENUM('NUEVO','ASIGNADO','EN_PROCESO','PENDIENTE_USUARIO','PENDIENTE_TERCERO','RESUELTO','CERRADO','REABIERTO','CANCELADO') NOT NULL DEFAULT 'NUEVO',
  source ENUM('WEB','MIGRACION','ADMIN') NOT NULL DEFAULT 'WEB',
  sla_due_at DATETIME NULL,
  first_response_at DATETIME NULL,
  resolved_at DATETIME NULL,
  closed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  UNIQUE KEY uq_tickets_number(ticket_number),
  KEY idx_tickets_requester(requester_id,status,created_at),
  KEY idx_tickets_assigned(assigned_to,status,created_at),
  KEY idx_tickets_park(park_id,status,created_at),
  KEY idx_tickets_category(category_id,status,created_at),
  KEY idx_tickets_status_priority(status,priority,created_at),
  CONSTRAINT fk_tickets_requester FOREIGN KEY(requester_id) REFERENCES users(id),
  CONSTRAINT fk_tickets_category FOREIGN KEY(category_id) REFERENCES ticket_categories(id),
  CONSTRAINT fk_tickets_park FOREIGN KEY(park_id) REFERENCES parks(id) ON DELETE SET NULL,
  CONSTRAINT fk_tickets_area FOREIGN KEY(area_id) REFERENCES areas(id) ON DELETE SET NULL,
  CONSTRAINT fk_tickets_assigned FOREIGN KEY(assigned_to) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_tickets_team FOREIGN KEY(assigned_team_id) REFERENCES support_teams(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ticket_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_id BIGINT UNSIGNED NOT NULL,
  actor_user_id BIGINT UNSIGNED NULL,
  event_type VARCHAR(80) NOT NULL,
  old_values JSON NULL,
  new_values JSON NULL,
  note VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ticket_events_ticket FOREIGN KEY(ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
  CONSTRAINT fk_ticket_events_actor FOREIGN KEY(actor_user_id) REFERENCES users(id) ON DELETE SET NULL,
  KEY idx_ticket_events_ticket(ticket_id,created_at),
  KEY idx_ticket_events_type(event_type,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ticket_comments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  visibility ENUM('PUBLIC','INTERNAL') NOT NULL DEFAULT 'PUBLIC',
  body TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_ticket_comments_ticket FOREIGN KEY(ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
  CONSTRAINT fk_ticket_comments_user FOREIGN KEY(user_id) REFERENCES users(id),
  KEY idx_ticket_comments_ticket(ticket_id,visibility,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ticket_attachments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_id BIGINT UNSIGNED NOT NULL,
  comment_id BIGINT UNSIGNED NULL,
  uploaded_by BIGINT UNSIGNED NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(255) NOT NULL,
  mime_type VARCHAR(120) NULL,
  file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
  sha256 CHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ticket_attach_ticket FOREIGN KEY(ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
  CONSTRAINT fk_ticket_attach_comment FOREIGN KEY(comment_id) REFERENCES ticket_comments(id) ON DELETE SET NULL,
  CONSTRAINT fk_ticket_attach_user FOREIGN KEY(uploaded_by) REFERENCES users(id),
  KEY idx_ticket_attach_ticket(ticket_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ticket_categories(name,description,sort_order) VALUES
('Sistemas / Aplicaciones','Incidencias o solicitudes relacionadas con aplicaciones internas',10),
('Equipos de cómputo','PC, laptop, monitor, periféricos y hardware',20),
('Red / Internet','Conectividad, Wi-Fi, enlaces e Internet',30),
('Impresoras','Impresión, drivers, consumibles y configuración',40),
('POS / Semnox','Puntos de venta, Parafait/Semnox y periféricos POS',50),
('Accesos / Usuarios','Usuarios, permisos, credenciales y accesos',60),
('Telefonía / Comunicaciones','Telefonía y otros medios de comunicación',70),
('Otro','Solicitud que no corresponde a una categoría anterior',999)
ON DUPLICATE KEY UPDATE description=VALUES(description),sort_order=VALUES(sort_order),active=1;

SELECT 'FASE2_1_OK' AS resultado,
       (SELECT COUNT(*) FROM ticket_categories WHERE active=1) AS categorias,
       (SELECT COUNT(*) FROM tickets) AS tickets;
