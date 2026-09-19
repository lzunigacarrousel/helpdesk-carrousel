-- Helpdesk Carrousel - Fase 12
-- Seguridad, perfiles, permisos y scopes. SOLO LECTURA.
USE carrousel_helpdesk;

SELECT 'ROLES_CANONICOS_ACTIVOS' control,COUNT(*) valor
FROM roles
WHERE code IN('ADMIN','SEMIADMIN','TECHNICIAN','MANAGEMENT','SUPERVISOR','REQUESTER','EXTERNAL')
  AND is_active=1;

SELECT 'ADMIN_PERMISOS_FALTANTES' control,COUNT(*) valor
FROM permissions p
LEFT JOIN roles r ON r.code='ADMIN'
LEFT JOIN role_permissions rp ON rp.role_id=r.id AND rp.permission_id=p.id
WHERE rp.permission_id IS NULL;

SELECT 'MANAGEMENT_OPERACION_PROHIBIDA' control,COUNT(*) valor
FROM role_permissions rp
JOIN roles r ON r.id=rp.role_id
JOIN permissions p ON p.id=rp.permission_id
WHERE r.code='MANAGEMENT'
  AND p.code IN(
    'tickets.view_queue','tickets.claim','tickets.reassign','tickets.change_status',
    'tickets.comment_internal','tickets.view_all','tickets.manage_special',
    'tickets.resolve','tickets.classify',
    'activities.create','activities.manage','activities.cancel',
    'external.manage','assignments.manage','catalogs.manage','sla.manage',
    'knowledge.draft_manage','knowledge.review','knowledge.publish_internal',
    'knowledge.publish_public','knowledge.history','knowledge.restore'
  );

SELECT 'SUPERVISOR_OPERACION_PROHIBIDA' control,COUNT(*) valor
FROM role_permissions rp
JOIN roles r ON r.id=rp.role_id
JOIN permissions p ON p.id=rp.permission_id
WHERE r.code='SUPERVISOR'
  AND p.code IN(
    'tickets.view_queue','tickets.claim','tickets.reassign','tickets.change_status',
    'tickets.comment_internal','tickets.view_all','tickets.manage_special',
    'tickets.resolve','tickets.classify',
    'activities.create','activities.manage','activities.cancel',
    'external.manage','assignments.manage','catalogs.manage','sla.manage',
    'knowledge.draft_manage','knowledge.review','knowledge.publish_internal',
    'knowledge.publish_public','knowledge.history','knowledge.restore'
  );

SELECT 'REQUESTER_PERMISOS_NO_CANONICOS' control,COUNT(*) valor
FROM role_permissions rp
JOIN roles r ON r.id=rp.role_id
JOIN permissions p ON p.id=rp.permission_id
WHERE r.code='REQUESTER'
  AND p.code NOT IN('tickets.view_own','tickets.comment_public','knowledge.view');

SELECT 'EXTERNAL_PERMISOS_NO_CANONICOS' control,COUNT(*) valor
FROM role_permissions rp
JOIN roles r ON r.id=rp.role_id
JOIN permissions p ON p.id=rp.permission_id
WHERE r.code='EXTERNAL'
  AND p.code<>'tickets.comment_public';

SELECT 'TECHNICIAN_ADMIN_PROHIBIDO' control,COUNT(*) valor
FROM role_permissions rp
JOIN roles r ON r.id=rp.role_id
JOIN permissions p ON p.id=rp.permission_id
WHERE r.code='TECHNICIAN'
  AND p.code IN(
    'tickets.reassign','tickets.view_all','tickets.manage_special',
    'users.view','assignments.view','assignments.manage','catalogs.manage',
    'management.view','external.manage','sla.manage',
    'knowledge.review','knowledge.publish_internal','knowledge.publish_public',
    'knowledge.history','knowledge.restore'
  );

SELECT 'MANAGEMENT_PERMISOS_CLAVE' control,COUNT(*) valor
FROM role_permissions rp
JOIN roles r ON r.id=rp.role_id
JOIN permissions p ON p.id=rp.permission_id
WHERE r.code='MANAGEMENT'
  AND p.code IN('management.view','reports.view','reports.global','problems.view','knowledge.view','activities.view');

SELECT 'SUPERVISOR_PERMISOS_CLAVE' control,COUNT(*) valor
FROM role_permissions rp
JOIN roles r ON r.id=rp.role_id
JOIN permissions p ON p.id=rp.permission_id
WHERE r.code='SUPERVISOR'
  AND p.code IN('tickets.view_own','management.view','reports.view','problems.view','knowledge.view','activities.view');

SELECT 'TECHNICIAN_PERMISOS_CLAVE' control,COUNT(*) valor
FROM role_permissions rp
JOIN roles r ON r.id=rp.role_id
JOIN permissions p ON p.id=rp.permission_id
WHERE r.code='TECHNICIAN'
  AND p.code IN(
    'tickets.view_queue','tickets.claim','tickets.change_status','tickets.resolve',
    'tickets.classify','tickets.comment_public','tickets.comment_internal',
    'activities.view','activities.create','activities.manage','activities.cancel',
    'knowledge.view','knowledge.draft_manage'
  );

SELECT 'REQUESTER_PERMISOS_TOTAL' control,COUNT(*) valor
FROM role_permissions rp
JOIN roles r ON r.id=rp.role_id
WHERE r.code='REQUESTER';

SELECT 'EXTERNAL_PERMISOS_TOTAL' control,COUNT(*) valor
FROM role_permissions rp
JOIN roles r ON r.id=rp.role_id
WHERE r.code='EXTERNAL';

SELECT 'SUPERVISORES_SIN_SCOPE_ACTIVO' control,COUNT(*) valor
FROM (
    SELECT u.id
    FROM users u
    JOIN roles r ON r.id=u.role_id AND r.code='SUPERVISOR'
    LEFT JOIN user_assignments ua
      ON ua.user_id=u.id
     AND ua.status='ACTIVE'
     AND ua.ends_at IS NULL
    WHERE u.deleted_at IS NULL
      AND u.status='ACTIVE'
    GROUP BY u.id
    HAVING COUNT(ua.id)=0
) x;

SELECT 'SUPERVISORES_SCOPE_VACIO' control,COUNT(*) valor
FROM user_assignments ua
JOIN users u ON u.id=ua.user_id AND u.deleted_at IS NULL AND u.status='ACTIVE'
JOIN roles r ON r.id=u.role_id AND r.code='SUPERVISOR'
WHERE ua.status='ACTIVE' AND ua.ends_at IS NULL
  AND ua.region_id IS NULL AND ua.park_id IS NULL AND ua.area_id IS NULL;

SELECT 'EXTERNOS_CON_ACCESO_INTERNO' control,COUNT(*) valor
FROM users u
JOIN roles r ON r.id=u.role_id
WHERE u.deleted_at IS NULL
  AND u.access_type='EXTERNAL'
  AND r.code<>'EXTERNAL';

SELECT 'ACCESO_EXTERNO_NO_ESPECIAL' control,COUNT(*) valor
FROM external_ticket_access eta
JOIN users u ON u.id=eta.user_id AND u.deleted_at IS NULL
JOIN tickets t ON t.id=eta.ticket_id AND t.deleted_at IS NULL
WHERE eta.revoked_at IS NULL
  AND (u.access_type<>'EXTERNAL' OR t.case_type<>'SPECIAL' OR t.visibility_mode<>'EXTERNAL_ALLOWED');

SELECT 'OVERRIDES_NO_DENY' control,COUNT(*) valor
FROM user_permission_overrides
WHERE effect<>'DENY';

SELECT CASE
  WHEN (SELECT COUNT(*) FROM roles
        WHERE code IN('ADMIN','SEMIADMIN','TECHNICIAN','MANAGEMENT','SUPERVISOR','REQUESTER','EXTERNAL')
          AND is_active=1)=7
   AND (SELECT COUNT(*) FROM permissions p
        LEFT JOIN roles r ON r.code='ADMIN'
        LEFT JOIN role_permissions rp ON rp.role_id=r.id AND rp.permission_id=p.id
        WHERE rp.permission_id IS NULL)=0
   AND (SELECT COUNT(*) FROM role_permissions rp JOIN roles r ON r.id=rp.role_id JOIN permissions p ON p.id=rp.permission_id
        WHERE r.code='MANAGEMENT' AND p.code IN(
          'tickets.view_queue','tickets.claim','tickets.reassign','tickets.change_status',
          'tickets.comment_internal','tickets.view_all','tickets.manage_special','tickets.resolve','tickets.classify',
          'activities.create','activities.manage','activities.cancel','external.manage','assignments.manage',
          'catalogs.manage','sla.manage','knowledge.draft_manage','knowledge.review',
          'knowledge.publish_internal','knowledge.publish_public','knowledge.history','knowledge.restore'))=0
   AND (SELECT COUNT(*) FROM role_permissions rp JOIN roles r ON r.id=rp.role_id JOIN permissions p ON p.id=rp.permission_id
        WHERE r.code='SUPERVISOR' AND p.code IN(
          'tickets.view_queue','tickets.claim','tickets.reassign','tickets.change_status',
          'tickets.comment_internal','tickets.view_all','tickets.manage_special','tickets.resolve','tickets.classify',
          'activities.create','activities.manage','activities.cancel','external.manage','assignments.manage',
          'catalogs.manage','sla.manage','knowledge.draft_manage','knowledge.review',
          'knowledge.publish_internal','knowledge.publish_public','knowledge.history','knowledge.restore'))=0
   AND (SELECT COUNT(*) FROM role_permissions rp JOIN roles r ON r.id=rp.role_id JOIN permissions p ON p.id=rp.permission_id
        WHERE r.code='REQUESTER' AND p.code NOT IN('tickets.view_own','tickets.comment_public','knowledge.view'))=0
   AND (SELECT COUNT(*) FROM role_permissions rp JOIN roles r ON r.id=rp.role_id JOIN permissions p ON p.id=rp.permission_id
        WHERE r.code='EXTERNAL' AND p.code<>'tickets.comment_public')=0
   AND (SELECT COUNT(*) FROM role_permissions rp JOIN roles r ON r.id=rp.role_id JOIN permissions p ON p.id=rp.permission_id
        WHERE r.code='TECHNICIAN' AND p.code IN(
          'tickets.reassign','tickets.view_all','tickets.manage_special','users.view','assignments.view',
          'assignments.manage','catalogs.manage','management.view','external.manage','sla.manage',
          'knowledge.review','knowledge.publish_internal','knowledge.publish_public','knowledge.history','knowledge.restore'))=0
   AND (SELECT COUNT(*) FROM role_permissions rp JOIN roles r ON r.id=rp.role_id JOIN permissions p ON p.id=rp.permission_id
        WHERE r.code='MANAGEMENT' AND p.code IN(
          'management.view','reports.view','reports.global','problems.view','knowledge.view','activities.view'))=6
   AND (SELECT COUNT(*) FROM role_permissions rp JOIN roles r ON r.id=rp.role_id JOIN permissions p ON p.id=rp.permission_id
        WHERE r.code='SUPERVISOR' AND p.code IN(
          'tickets.view_own','management.view','reports.view','problems.view','knowledge.view','activities.view'))=6
   AND (SELECT COUNT(*) FROM role_permissions rp JOIN roles r ON r.id=rp.role_id JOIN permissions p ON p.id=rp.permission_id
        WHERE r.code='TECHNICIAN' AND p.code IN(
          'tickets.view_queue','tickets.claim','tickets.change_status','tickets.resolve','tickets.classify',
          'tickets.comment_public','tickets.comment_internal','activities.view','activities.create',
          'activities.manage','activities.cancel','knowledge.view','knowledge.draft_manage'))=13
   AND (SELECT COUNT(*) FROM role_permissions rp JOIN roles r ON r.id=rp.role_id WHERE r.code='REQUESTER')=3
   AND (SELECT COUNT(*) FROM role_permissions rp JOIN roles r ON r.id=rp.role_id WHERE r.code='EXTERNAL')=1
   AND (SELECT COUNT(*) FROM (
          SELECT u.id
          FROM users u
          JOIN roles r ON r.id=u.role_id AND r.code='SUPERVISOR'
          LEFT JOIN user_assignments ua
            ON ua.user_id=u.id AND ua.status='ACTIVE' AND ua.ends_at IS NULL
          WHERE u.deleted_at IS NULL AND u.status='ACTIVE'
          GROUP BY u.id
          HAVING COUNT(ua.id)=0
        ) sx)=0
   AND (SELECT COUNT(*) FROM user_assignments ua
        JOIN users u ON u.id=ua.user_id AND u.deleted_at IS NULL AND u.status='ACTIVE'
        JOIN roles r ON r.id=u.role_id AND r.code='SUPERVISOR'
        WHERE ua.status='ACTIVE' AND ua.ends_at IS NULL
          AND ua.region_id IS NULL AND ua.park_id IS NULL AND ua.area_id IS NULL)=0
   AND (SELECT COUNT(*) FROM users u
        JOIN roles r ON r.id=u.role_id
        WHERE u.deleted_at IS NULL AND u.access_type='EXTERNAL' AND r.code<>'EXTERNAL')=0
   AND (SELECT COUNT(*) FROM external_ticket_access eta
        JOIN users u ON u.id=eta.user_id AND u.deleted_at IS NULL
        JOIN tickets t ON t.id=eta.ticket_id AND t.deleted_at IS NULL
        WHERE eta.revoked_at IS NULL
          AND (u.access_type<>'EXTERNAL' OR t.case_type<>'SPECIAL' OR t.visibility_mode<>'EXTERNAL_ALLOWED'))=0
   AND (SELECT COUNT(*) FROM user_permission_overrides WHERE effect<>'DENY')=0
  THEN 'PASS'
  ELSE 'FAIL'
END AS fase12_security_gate;
