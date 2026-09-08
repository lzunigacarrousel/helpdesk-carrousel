USE helpdesk_carrousel_test;

SELECT DATABASE() AS base_actual;

SELECT 'roles' AS tabla, COUNT(*) AS registros FROM roles
UNION ALL SELECT 'permissions', COUNT(*) FROM permissions
UNION ALL SELECT 'users', COUNT(*) FROM users
UNION ALL SELECT 'regions', COUNT(*) FROM regions
UNION ALL SELECT 'parks', COUNT(*) FROM parks
UNION ALL SELECT 'areas', COUNT(*) FROM areas
UNION ALL SELECT 'user_assignments', COUNT(*) FROM user_assignments
UNION ALL SELECT 'support_teams', COUNT(*) FROM support_teams
UNION ALL SELECT 'support_team_members', COUNT(*) FROM support_team_members
UNION ALL SELECT 'support_scopes', COUNT(*) FROM support_scopes
UNION ALL SELECT 'ticket_categories', COUNT(*) FROM ticket_categories
UNION ALL SELECT 'sla_policies', COUNT(*) FROM sla_policies
UNION ALL SELECT 'tickets', COUNT(*) FROM tickets
UNION ALL SELECT 'ticket_events', COUNT(*) FROM ticket_events
UNION ALL SELECT 'ticket_comments', COUNT(*) FROM ticket_comments
UNION ALL SELECT 'ticket_attachments', COUNT(*) FROM ticket_attachments
UNION ALL SELECT 'external_ticket_access', COUNT(*) FROM external_ticket_access
UNION ALL SELECT 'known_problems', COUNT(*) FROM known_problems
UNION ALL SELECT 'problem_occurrences', COUNT(*) FROM problem_occurrences
UNION ALL SELECT 'knowledge_articles', COUNT(*) FROM knowledge_articles
UNION ALL SELECT 'notification_events', COUNT(*) FROM notification_events
UNION ALL SELECT 'notification_deliveries', COUNT(*) FROM notification_deliveries
UNION ALL SELECT 'audit_logs', COUNT(*) FROM audit_logs;

SELECT u.id, u.email, u.full_name, u.access_type, u.status, r.code AS role_code
FROM users u
JOIN roles r ON r.id = u.role_id
ORDER BY u.id;

SELECT r.code AS role_code, COUNT(rp.permission_id) AS permisos
FROM roles r
LEFT JOIN role_permissions rp ON rp.role_id = r.id
GROUP BY r.id, r.code
ORDER BY r.id;

SELECT code, name, default_priority, is_active
FROM ticket_categories
ORDER BY id;

SELECT name, priority, first_response_minutes, resolution_minutes, is_active
FROM sla_policies
ORDER BY id;

SELECT 'OK - Helpdesk Carrousel V2' AS resultado;
