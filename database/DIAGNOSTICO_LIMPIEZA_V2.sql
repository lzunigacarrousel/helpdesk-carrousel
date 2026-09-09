-- Helpdesk Carrousel V2
-- DIAGNÓSTICO DE LIMPIEZA - SOLO LECTURA
-- Snapshot revisado: 2026-09-09 16:47 GT
-- No elimina ni modifica información.

USE helpdesk_carrousel_test;
SET NAMES utf8mb4;

SELECT DATABASE() AS base_actual,
       'SOLO LECTURA - no se modifica información' AS modo;

-- 1) Resumen de datos operativos principales.
SELECT 'usuarios' entidad, COUNT(*) total FROM users WHERE deleted_at IS NULL
UNION ALL SELECT 'tickets', COUNT(*) FROM tickets WHERE deleted_at IS NULL
UNION ALL SELECT 'problemas_conocidos', COUNT(*) FROM known_problems
UNION ALL SELECT 'articulos_conocimiento', COUNT(*) FROM knowledge_articles
UNION ALL SELECT 'comentarios', COUNT(*) FROM ticket_comments WHERE deleted_at IS NULL
UNION ALL SELECT 'resoluciones', COUNT(*) FROM ticket_resolutions
UNION ALL SELECT 'feedback_nps', COUNT(*) FROM ticket_feedback
UNION ALL SELECT 'parques_activos', COUNT(*) FROM parks WHERE is_active=1
UNION ALL SELECT 'areas_activas', COUNT(*) FROM areas WHERE is_active=1
UNION ALL SELECT 'asignaciones_activas', COUNT(*) FROM user_assignments WHERE status='ACTIVE' AND ends_at IS NULL;

-- 2) Tickets identificados como datos de prueba en el dump del 09/09/2026.
-- Se muestran antes de cualquier limpieza para revisión humana.
SELECT id,ticket_number,requester_email,requester_name,subject,description,status,created_at
FROM tickets
WHERE id IN (1,2,3,4,5,7)
ORDER BY id;

-- 3) Casos que NO están marcados para limpiar. Deben conservarse.
SELECT id,ticket_number,requester_email,requester_name,subject,status,created_at
FROM tickets
WHERE deleted_at IS NULL
  AND id NOT IN (1,2,3,4,5,7)
ORDER BY id;

-- 4) Problemas conocidos de prueba detectados.
SELECT id,problem_number,title,description,status,created_at
FROM known_problems
WHERE id=1;

-- 5) Áreas importadas antiguas, inactivas y sin uso actual.
SELECT a.id,a.code,a.name,a.is_active,
       (SELECT COUNT(*) FROM user_assignments ua WHERE ua.area_id=a.id) referencias_asignaciones,
       (SELECT COUNT(*) FROM tickets t WHERE t.area_id=a.id AND t.deleted_at IS NULL) referencias_tickets
FROM areas a
WHERE a.id BETWEEN 5 AND 12
ORDER BY a.id;

-- 6) Salud del catálogo de usuarios. No se eliminan usuarios importados automáticamente.
SELECT r.code perfil,u.status,COUNT(*) total
FROM users u
JOIN roles r ON r.id=u.role_id
WHERE u.deleted_at IS NULL
GROUP BY r.code,u.status
ORDER BY r.code,u.status;

-- 7) Verificación de huérfanos básicos.
SELECT 'ticket_feedback_sin_ticket' verificacion,COUNT(*) total
FROM ticket_feedback tf LEFT JOIN tickets t ON t.id=tf.ticket_id
WHERE t.id IS NULL
UNION ALL
SELECT 'asignaciones_sin_usuario',COUNT(*)
FROM user_assignments ua LEFT JOIN users u ON u.id=ua.user_id
WHERE u.id IS NULL
UNION ALL
SELECT 'miembros_soporte_sin_usuario',COUNT(*)
FROM support_team_members stm LEFT JOIN users u ON u.id=stm.user_id
WHERE u.id IS NULL;

SELECT 'OK - diagnóstico completado; no se modificó la base' AS resultado;
