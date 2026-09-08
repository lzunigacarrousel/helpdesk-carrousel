# Helpdesk Carrousel 360 — Fase 1

Base nueva para PC TEST. No modifica el Helpdesk legado ni su base de datos.

## Incluido
- Autorregistro por correo.
- OTP hasheado, vencimiento, intentos y rate limiting.
- Sesión persistente con validador rotado.
- Roles y permisos granulares.
- Usuario nuevo en `PENDING_ASSIGNMENT`.
- Organización separada de rol: regiones, parques, áreas, puestos y asignaciones.
- `user_scopes` separado de rol para autorización operativa futura.
- Equipos de soporte.
- Auditoría.
- Configuración local fuera de Git.
- Modo `mail_mode=log` para PC TEST.
- Pantalla administrativa mínima para activar/asignar usuarios.
- Diseño preparado para que historial técnico, resolución y conocimiento sean núcleo del Helpdesk.

## Instalación PC TEST
1. Copiar esta carpeta como `C:\xampp\htdocs\Helpdesk360`.
2. Copiar `config\local.php.example` como `config\local.php`.
3. Mantener `mail_mode => 'log'` durante las primeras pruebas.
4. Ejecutar `database\INSTALAR_FASE1.sql` en MariaDB de TEST.
5. Abrir `http://localhost/Helpdesk360/public/`.
6. Usuario inicial DEMO: `admin.helpdesk@carrousel.local`.
7. Solicitar OTP y leerlo en `storage\logs\mail.log`.
8. Probar autorregistro de un correo nuevo: debe quedar `PENDING_ASSIGNMENT`.
9. Entrar como admin DEMO y asignar ese usuario desde **Usuarios**.

## Seguridad
`config/local.php`, logs, attachments y exports están excluidos de Git. Nunca copiar credenciales reales al repositorio.

## Importante
`database/INSTALAR_FASE1.sql` elimina y recrea SOLO las tablas dentro de `helpdesk360_test`. Usar únicamente en PC TEST. No ejecutar contra la BD histórica `helpdesk_carrousel`.
