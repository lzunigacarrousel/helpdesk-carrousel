-- Helpdesk Carrousel V2
-- Diagnostico de integridad y seguridad. SOLO LECTURA.
USE carrousel_helpdesk;

SELECT 'USUARIOS_EXTERNOS_SIN_PERFIL' prueba, COUNT(*) hallazgos
FROM users u
JOIN roles r ON r.id=u.role_id AND r.code='EXTERNAL'
LEFT JOIN external_profiles ep ON ep.user_id=u.id
WHERE u.deleted_at IS NULL AND u.access_type='EXTERNAL' AND ep.user_id IS NULL;

SELECT 'EXTERNOS_CON_ROL_INCORRECTO' prueba, COUNT(*) hallazgos
FROM users u JOIN roles r ON r.id=u.role_id
WHERE u.deleted_at IS NULL AND u.access_type='EXTERNAL' AND r.code<>'EXTERNAL';

SELECT 'ACCESOS_EXTERNOS_TICKET_NO_ESPECIAL' prueba, COUNT(*) hallazgos
FROM external_ticket_access eta
JOIN tickets t ON t.id=eta.ticket_id
WHERE eta.revoked_at IS NULL
  AND (t.case_type<>'SPECIAL' OR t.visibility_mode<>'EXTERNAL_ALLOWED');

SELECT 'TICKETS_ASIGNADOS_A_USUARIO_EXTERNO' prueba, COUNT(*) hallazgos
FROM tickets t
JOIN users u ON u.id=t.assigned_to
WHERE t.deleted_at IS NULL AND u.access_type='EXTERNAL';

SELECT 'ASIGNACIONES_ORGANIZACION_ACTIVAS_DUPLICADAS' prueba, COUNT(*) hallazgos
FROM (
    SELECT user_id
    FROM user_assignments
    WHERE status='ACTIVE' AND ends_at IS NULL
    GROUP BY user_id
    HAVING COUNT(*)>1
) x;

SELECT 'TICKETS_CERRADOS_SIN_FECHA_CIERRE' prueba, COUNT(*) hallazgos
FROM tickets
WHERE deleted_at IS NULL AND status='CLOSED' AND closed_at IS NULL;

SELECT 'TICKETS_RESUELTOS_SIN_FECHA_RESOLUCION' prueba, COUNT(*) hallazgos
FROM tickets
WHERE deleted_at IS NULL AND status='RESOLVED' AND resolved_at IS NULL;

SELECT 'TICKETS_ABIERTOS_CON_FECHA_CIERRE' prueba, COUNT(*) hallazgos
FROM tickets
WHERE deleted_at IS NULL AND status IN('NEW','AVAILABLE','IN_PROGRESS','PENDING','REOPENED') AND closed_at IS NOT NULL;

SELECT 'TICKETS_SIN_CATEGORIA' prueba, COUNT(*) hallazgos
FROM tickets
WHERE deleted_at IS NULL AND category_id IS NULL;

SELECT 'TICKETS_EN_ESPERA_SIN_MOTIVO' prueba, COUNT(*) hallazgos
FROM tickets
WHERE deleted_at IS NULL AND status='PENDING' AND (pending_reason_code IS NULL OR pending_reason_code='');

SELECT 'TICKETS_NO_ESPERA_CON_MOTIVO' prueba, COUNT(*) hallazgos
FROM tickets
WHERE deleted_at IS NULL AND status<>'PENDING' AND (pending_reason_code IS NOT NULL OR pending_note IS NOT NULL);

SELECT 'FEEDBACK_NPS_FUERA_DE_RANGO' prueba, COUNT(*) hallazgos
FROM ticket_feedback
WHERE nps_score<0 OR nps_score>10;

SELECT 'FEEDBACK_EN_TICKET_NO_CERRADO' prueba, COUNT(*) hallazgos
FROM ticket_feedback tf
JOIN tickets t ON t.id=tf.ticket_id
WHERE t.deleted_at IS NULL AND t.status<>'CLOSED';

SELECT 'PROBLEMAS_NUMERO_VACIO' prueba, COUNT(*) hallazgos
FROM known_problems
WHERE problem_number IS NULL OR problem_number='';

SELECT 'PROBLEMAS_REURRENCIA_DESFASADA' prueba, COUNT(*) hallazgos
FROM known_problems kp
LEFT JOIN (
    SELECT po.problem_id,COUNT(*) occurrences,MIN(t.created_at) first_seen,MAX(t.created_at) last_seen
    FROM problem_occurrences po
    JOIN tickets t ON t.id=po.ticket_id AND t.deleted_at IS NULL
    GROUP BY po.problem_id
) x ON x.problem_id=kp.id
WHERE kp.occurrence_count<>COALESCE(x.occurrences,0)
   OR NOT (kp.first_seen_at <=> x.first_seen)
   OR NOT (kp.last_seen_at <=> x.last_seen);

SELECT 'PROBLEMAS_CON_VARIAS_SOLUCIONES_PRINCIPALES' prueba, COUNT(*) hallazgos
FROM (
    SELECT problem_id
    FROM problem_solutions
    WHERE is_primary=1
    GROUP BY problem_id
    HAVING COUNT(*)>1
) x;

SELECT 'ARTICULOS_NUMERO_VACIO' prueba, COUNT(*) hallazgos
FROM knowledge_articles
WHERE article_number IS NULL OR article_number='';

SELECT 'ARTICULOS_PUBLICADOS_SIN_FECHA' prueba, COUNT(*) hallazgos
FROM knowledge_articles
WHERE status='PUBLISHED' AND published_at IS NULL;

SELECT 'NOTIFICACIONES_INAPP_SIN_USUARIO' prueba, COUNT(*) hallazgos
FROM notification_deliveries
WHERE channel='IN_APP' AND recipient_user_id IS NULL;

SELECT 'NOTIFICACIONES_INAPP_SIN_CONTENIDO' prueba, COUNT(*) hallazgos
FROM notification_deliveries
WHERE channel='IN_APP' AND (title IS NULL OR title='' OR action_url IS NULL OR action_url='');

SELECT 'CORREOS_ENVIADOS_SIN_FECHA' prueba, COUNT(*) hallazgos
FROM notification_deliveries
WHERE channel='EMAIL' AND status='SENT' AND sent_at IS NULL;

SELECT 'CORREOS_FALLIDOS_SIN_MOTIVO' prueba, COUNT(*) hallazgos
FROM notification_deliveries
WHERE channel='EMAIL' AND status='FAILED' AND (last_error IS NULL OR last_error='');

SELECT 'CORREOS_PENDIENTES_MAS_10_MIN' prueba, COUNT(*) hallazgos
FROM notification_deliveries
WHERE channel='EMAIL' AND status='PENDING' AND created_at<DATE_SUB(NOW(),INTERVAL 10 MINUTE);

SELECT 'MIGRACIONES_REGISTRADAS' prueba, COUNT(*) hallazgos FROM schema_migrations;
SELECT 'TOTAL_TICKETS' prueba, COUNT(*) hallazgos FROM tickets WHERE deleted_at IS NULL;
SELECT 'TOTAL_USUARIOS' prueba, COUNT(*) hallazgos FROM users WHERE deleted_at IS NULL;
SELECT 'TOTAL_EXTERNOS' prueba, COUNT(*) hallazgos FROM users WHERE deleted_at IS NULL AND access_type='EXTERNAL';
SELECT 'ACCESOS_EXTERNOS_ACTIVOS' prueba, COUNT(*) hallazgos FROM external_ticket_access WHERE revoked_at IS NULL;
SELECT 'TOTAL_PROBLEMAS_CONOCIDOS' prueba, COUNT(*) hallazgos FROM known_problems;
SELECT 'TOTAL_ARTICULOS_CONOCIMIENTO' prueba, COUNT(*) hallazgos FROM knowledge_articles;
SELECT 'TOTAL_RESPUESTAS_NPS' prueba, COUNT(*) hallazgos FROM ticket_feedback;
SELECT 'NPS_PROMOTORES' prueba, COUNT(*) hallazgos FROM ticket_feedback WHERE nps_score>=9;
SELECT 'NPS_PASIVOS' prueba, COUNT(*) hallazgos FROM ticket_feedback WHERE nps_score BETWEEN 7 AND 8;
SELECT 'NPS_DETRACTORES' prueba, COUNT(*) hallazgos FROM ticket_feedback WHERE nps_score<=6;
SELECT 'TOTAL_NOTIFICACIONES_INAPP' prueba, COUNT(*) hallazgos FROM notification_deliveries WHERE channel='IN_APP';
SELECT 'NOTIFICACIONES_NO_LEIDAS' prueba, COUNT(*) hallazgos FROM notification_deliveries WHERE channel='IN_APP' AND read_at IS NULL;
SELECT 'CORREOS_ENVIADOS' prueba, COUNT(*) hallazgos FROM notification_deliveries WHERE channel='EMAIL' AND status='SENT';
SELECT 'CORREOS_NOTIFICACION_FALLIDOS' prueba, COUNT(*) hallazgos FROM notification_deliveries WHERE channel='EMAIL' AND status='FAILED';
SELECT 'CORREOS_PENDIENTES' prueba, COUNT(*) hallazgos FROM notification_deliveries WHERE channel='EMAIL' AND status='PENDING';
SELECT 'CORREOS_MODO_PRUEBA' prueba, COUNT(*) hallazgos FROM notification_deliveries WHERE channel='EMAIL' AND status='SKIPPED';
