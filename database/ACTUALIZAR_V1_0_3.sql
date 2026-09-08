-- Helpdesk Carrousel 360 · v1.0.3
-- Ajuste no destructivo para instalaciones existentes.
USE helpdesk360_test;

UPDATE users
SET name='Luis Fernando Zuniga', updated_at=NOW()
WHERE LOWER(email)='luis@carrousel.com.gt';

SELECT u.id,u.email,u.name,r.code AS role_code,r.name AS role_name,u.account_status,u.active
FROM users u
JOIN roles r ON r.id=u.role_id
WHERE LOWER(u.email)='luis@carrousel.com.gt';
