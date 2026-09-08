USE helpdesk360_test;
SELECT 'roles' tabla,COUNT(*) registros FROM roles UNION ALL SELECT 'permissions',COUNT(*) FROM permissions UNION ALL SELECT 'users',COUNT(*) FROM users UNION ALL SELECT 'regions',COUNT(*) FROM regions UNION ALL SELECT 'areas',COUNT(*) FROM areas UNION ALL SELECT 'positions',COUNT(*) FROM positions UNION ALL SELECT 'support_teams',COUNT(*) FROM support_teams;
SELECT u.id,u.email,u.name,r.code rol,u.account_status,u.is_demo FROM users u JOIN roles r ON r.id=u.role_id ORDER BY u.id;
SELECT r.code rol,COUNT(rp.permission_id) permisos FROM roles r LEFT JOIN role_permissions rp ON rp.role_id=r.id GROUP BY r.id,r.code ORDER BY r.sort_order;
