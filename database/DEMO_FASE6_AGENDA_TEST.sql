USE carrousel_helpdesk;
SET NAMES utf8mb4;

-- PC TEST únicamente.
-- Reutiliza actividades existentes del ticket HD-2026-000001 para validar
-- Mes, Semana, Lista y filtros sin crear tickets nuevos.

SET @ticket_id := (SELECT id FROM tickets WHERE ticket_number='HD-2026-000001' LIMIT 1);
SET @base_user := (SELECT responsible_user_id FROM ticket_activities WHERE id=6 AND ticket_id=@ticket_id LIMIT 1);
SET @alt_user := (
    SELECT id
    FROM users
    WHERE status='ACTIVE'
      AND deleted_at IS NULL
      AND access_type='INTERNAL'
      AND id<>COALESCE(@base_user,0)
    ORDER BY id
    LIMIT 1
);
SET @base_park := (SELECT park_id FROM ticket_activities WHERE id=6 AND ticket_id=@ticket_id LIMIT 1);
SET @alt_park := (
    SELECT id
    FROM parks
    WHERE is_active=1
      AND id<>COALESCE(@base_park,0)
    ORDER BY id
    LIMIT 1
);

START TRANSACTION;

-- Histórica finalizada: sirve para Estado=Finalizada / Incluir historial.
UPDATE ticket_activities
SET activity_type='VISITA_EN_SITIO',
    status='FINALIZADA',
    scheduled_start_at='2026-09-10 14:00:00',
    scheduled_end_at='2026-09-10 15:30:00',
    started_at='2026-09-10 14:00:00',
    finished_at='2026-09-10 15:30:00',
    result_code='RESUELTA',
    work_performed='DEMO FASE 6 - visita finalizada para filtros',
    result_summary='DEMO FASE 6 - actividad finalizada visible con historial',
    pending_items=NULL,
    cancel_reason=NULL,
    cancelled_at=NULL,
    cancelled_by=NULL,
    updated_at=NOW()
WHERE id=3 AND ticket_id=@ticket_id;

-- Histórica cancelada: sirve para Estado=Cancelada / Incluir historial.
UPDATE ticket_activities
SET activity_type='OTRA',
    status='CANCELADA',
    scheduled_start_at='2026-09-12 09:00:00',
    scheduled_end_at='2026-09-12 10:00:00',
    started_at=NULL,
    finished_at=NULL,
    result_code=NULL,
    work_performed=NULL,
    result_summary=NULL,
    pending_items=NULL,
    cancel_reason='DEMO FASE 6 - cancelada para validar filtros',
    cancelled_at='2026-09-12 08:30:00',
    cancelled_by=created_by,
    park_id=COALESCE(@alt_park,park_id),
    updated_at=NOW()
WHERE id=4 AND ticket_id=@ticket_id;

-- Activa multiday: responsable y parque alternos cuando existan.
UPDATE ticket_activities
SET activity_type='SOPORTE_REMOTO',
    status='PROGRAMADA',
    responsible_user_id=COALESCE(@alt_user,responsible_user_id),
    park_id=COALESCE(@alt_park,park_id),
    is_remote=1,
    scheduled_start_at='2026-09-16 13:00:00',
    scheduled_end_at='2026-09-18 16:00:00',
    started_at=NULL,
    finished_at=NULL,
    result_code=NULL,
    work_performed=NULL,
    result_summary=NULL,
    pending_items=NULL,
    cancel_reason=NULL,
    cancelled_at=NULL,
    cancelled_by=NULL,
    updated_at=NOW()
WHERE id=5 AND ticket_id=@ticket_id;

-- Activa horaria: queda con responsable/parque original para contrastar filtros.
UPDATE ticket_activities
SET activity_type='SEGUIMIENTO',
    status='PROGRAMADA',
    is_remote=0,
    scheduled_start_at='2026-09-15 10:00:00',
    scheduled_end_at='2026-09-15 11:30:00',
    started_at=NULL,
    finished_at=NULL,
    result_code=NULL,
    work_performed=NULL,
    result_summary=NULL,
    pending_items=NULL,
    cancel_reason=NULL,
    cancelled_at=NULL,
    cancelled_by=NULL,
    updated_at=NOW()
WHERE id=6 AND ticket_id=@ticket_id;

COMMIT;

SELECT '=== DATASET DEMO FASE 6 ===' AS seccion;
SELECT
    a.id,
    t.ticket_number,
    a.activity_type,
    a.status,
    a.scheduled_start_at,
    a.scheduled_end_at,
    u.full_name AS responsable,
    COALESCE(p.name,'Sin parque') AS parque,
    CASE
        WHEN DATE(a.scheduled_start_at)=DATE(a.scheduled_end_at) THEN 'HORARIA'
        ELSE 'MULTIDAY'
    END AS render_calendario
FROM ticket_activities a
JOIN tickets t ON t.id=a.ticket_id
JOIN users u ON u.id=a.responsible_user_id
LEFT JOIN parks p ON p.id=a.park_id
WHERE a.id IN(3,4,5,6)
  AND a.ticket_id=@ticket_id
ORDER BY a.scheduled_start_at,a.id;

SELECT '=== VALIDACION FILTROS DEMO ===' AS seccion;
SELECT 'Activas' AS filtro, COUNT(*) AS filas
FROM ticket_activities
WHERE id IN(3,4,5,6)
  AND ticket_id=@ticket_id
  AND status IN('PROGRAMADA','EN_CURSO')
UNION ALL
SELECT 'Finalizada', COUNT(*)
FROM ticket_activities
WHERE id IN(3,4,5,6) AND ticket_id=@ticket_id AND status='FINALIZADA'
UNION ALL
SELECT 'Cancelada', COUNT(*)
FROM ticket_activities
WHERE id IN(3,4,5,6) AND ticket_id=@ticket_id AND status='CANCELADA'
UNION ALL
SELECT 'Seguimiento', COUNT(*)
FROM ticket_activities
WHERE id IN(3,4,5,6) AND ticket_id=@ticket_id AND activity_type='SEGUIMIENTO'
UNION ALL
SELECT 'Soporte remoto', COUNT(*)
FROM ticket_activities
WHERE id IN(3,4,5,6) AND ticket_id=@ticket_id AND activity_type='SOPORTE_REMOTO'
UNION ALL
SELECT 'Visita en sitio', COUNT(*)
FROM ticket_activities
WHERE id IN(3,4,5,6) AND ticket_id=@ticket_id AND activity_type='VISITA_EN_SITIO'
UNION ALL
SELECT 'Otra', COUNT(*)
FROM ticket_activities
WHERE id IN(3,4,5,6) AND ticket_id=@ticket_id AND activity_type='OTRA';

SELECT '=== RESPONSABLES DEMO ===' AS seccion;
SELECT u.id,u.full_name,COUNT(*) AS actividades
FROM ticket_activities a
JOIN users u ON u.id=a.responsible_user_id
WHERE a.id IN(3,4,5,6) AND a.ticket_id=@ticket_id
GROUP BY u.id,u.full_name
ORDER BY u.full_name;

SELECT '=== PARQUES DEMO ===' AS seccion;
SELECT COALESCE(p.id,0) AS id,COALESCE(p.name,'Sin parque') AS parque,COUNT(*) AS actividades
FROM ticket_activities a
LEFT JOIN parks p ON p.id=a.park_id
WHERE a.id IN(3,4,5,6) AND a.ticket_id=@ticket_id
GROUP BY p.id,p.name
ORDER BY parque;
