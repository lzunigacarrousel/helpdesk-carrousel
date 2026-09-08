-- Helpdesk Carrousel V2
-- Diagnostico de integridad y seguridad. SOLO LECTURA.
USE helpdesk_carrousel_test;

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

SELECT 'TOTAL_TICKETS' prueba, COUNT(*) hallazgos FROM tickets WHERE deleted_at IS NULL;
SELECT 'TOTAL_USUARIOS' prueba, COUNT(*) hallazgos FROM users WHERE deleted_at IS NULL;
SELECT 'TOTAL_EXTERNOS' prueba, COUNT(*) hallazgos FROM users WHERE deleted_at IS NULL AND access_type='EXTERNAL';
SELECT 'ACCESOS_EXTERNOS_ACTIVOS' prueba, COUNT(*) hallazgos FROM external_ticket_access WHERE revoked_at IS NULL;
