-- Helpdesk Carrousel 360 · Fase 1.1
-- NO borra tablas ni tickets. Solo corrige el administrador principal en la BD de TEST ya instalada.
USE helpdesk360_test;

SET @admin_role := (SELECT id FROM roles WHERE code='ADMINISTRADOR' LIMIT 1);
SET @user_id := (SELECT id FROM users WHERE LOWER(email)='luis@carrousel.com.gt' LIMIT 1);

-- Si Luis aún no existe, se crea como administrador activo.
INSERT INTO users(email,name,role_id,account_status,active,is_demo,registered_at,created_at,updated_at)
SELECT 'luis@carrousel.com.gt','Luis Zúñiga',@admin_role,'ACTIVE',1,0,NOW(),NOW(),NOW()
WHERE @user_id IS NULL;

SET @user_id := (SELECT id FROM users WHERE LOWER(email)='luis@carrousel.com.gt' LIMIT 1);

-- Cierra el rol histórico anterior si lo hubiera y eleva el usuario existente a Administrador.
UPDATE user_role_history
SET ended_at=NOW()
WHERE user_id=@user_id AND ended_at IS NULL AND role_id<>@admin_role;

UPDATE users
SET role_id=@admin_role,
    account_status='ACTIVE',
    active=1,
    is_demo=0,
    updated_at=NOW()
WHERE id=@user_id;

INSERT INTO user_role_history(user_id,role_id,started_at,reason,changed_by,created_at)
SELECT @user_id,@admin_role,NOW(),'Administrador principal de Helpdesk Carrousel 360',@user_id,NOW()
WHERE NOT EXISTS(
    SELECT 1 FROM user_role_history
    WHERE user_id=@user_id AND role_id=@admin_role AND ended_at IS NULL
);

-- Alcance global explícito para el administrador (aunque Auth ya concede permisos totales por rol).
INSERT INTO user_scopes(user_id,scope_type,started_at,reason,created_by,created_at)
SELECT @user_id,'GLOBAL',NOW(),'Alcance global del administrador principal',@user_id,NOW()
WHERE NOT EXISTS(
    SELECT 1 FROM user_scopes
    WHERE user_id=@user_id AND scope_type='GLOBAL' AND ended_at IS NULL
);

SELECT u.id,u.email,u.name,r.code AS role_code,r.name AS role_name,u.account_status,u.active
FROM users u JOIN roles r ON r.id=u.role_id
WHERE u.id=@user_id;
