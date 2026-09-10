# Helpdesk Carrousel 360

Aplicación de Service Desk de Corporación Carrousel. La rama `v2-rebuild` es la reconstrucción actual para PC TEST y utiliza MariaDB, PHP 8+ y acceso por OTP.

## Estado actual

- Carpeta recomendada: `C:\xampp\htdocs\HelpdeskCarrousel`
- Base canónica única: `helpdesk_carrousel`
- Entrada web: `http://localhost/HelpdeskCarrousel/public/`
- Configuración local: `config\local.php` (no se versiona)
- Correo en PC TEST: `mail_mode => 'log'`
- Administrador inicial: `luis@carrousel.com.gt`

TEST y Producción utilizan el mismo nombre de base de datos. La separación de ambientes depende de la máquina/servidor, no de nombres diferentes de BD.

## Instalación limpia

La instalación nueva debe partir del esquema actual y nunca de una cadena de migraciones históricas. El objetivo canónico es que `database\INSTALAR.sql` pueda recrear por sí mismo el esquema final sobre `helpdesk_carrousel`.

Durante la consolidación actual existe temporalmente `database\FINALIZAR_ESQUEMA_V2.sql`; se utiliza una sola vez para materializar el último estado y será retirado cuando ese estado quede absorbido en `INSTALAR.sql`.

Los catálogos organizacionales se cargan desde `database\CATALOGOS_CARROUSEL.sql` mientras se completa esa consolidación. `database\VERIFICAR_INSTALACION.sql` valida estrictamente el resultado y `database\VERIFICAR_ESTABILIDAD_V2.sql` realiza diagnósticos de integridad sin modificar datos.

## Qué contiene el esquema vigente

La estructura actual incluye seguridad, OTP, sesiones, usuarios, perfiles, permisos, organización, equipos y alcances, tickets, conversaciones, adjuntos, resoluciones, NPS, problemas conocidos, conocimiento, colaboradores externos, notificaciones, auditoría, SLA y control de versión del esquema.

Una instalación limpia no importa tickets ni usuarios históricos de aplicaciones anteriores.

## Base de datos

La verificación canónica exige exactamente las tablas que utiliza la aplicación vigente y falla si falta una tabla requerida o aparece una tabla extra/legacy.

Los perfiles vigentes son `ADMIN`, `SEMIADMIN`, `TECHNICIAN`, `MANAGEMENT`, `SUPERVISOR`, `REQUESTER` y `EXTERNAL`. Gerencia y Supervisor son perfiles de consulta y no deben convertirse automáticamente en Solicitante.

Los parques activos deben tener una región válida porque el flujo actual de asignaciones y alcance depende de esa relación.

## Acceso y OTP

No se utilizan contraseñas permanentes. El usuario escribe su correo y recibe un código OTP temporal. En PC TEST, con `mail_mode => 'log'`, el código se registra localmente en:

`storage\logs\mail.log`

Para SMTP real se configura únicamente `config\local.php`; nunca deben subirse credenciales al repositorio.

## Seguridad y archivos locales

`.gitignore` excluye `config/local.php`, logs, adjuntos, exportaciones, caché, sesiones, temporales, backups, `vendor/` y otros archivos regenerables o sensibles.

Antes de subir cambios usa `HELPDESK_ADMIN.bat` para revisar estado, diferencias y archivos sensibles.

## Regla de mantenimiento de BD

No se agregan nuevamente scripts SQL históricos al directorio `database`. Cualquier cambio nuevo debe terminar consolidado en el esquema canónico y acompañado de una verificación reproducible para instalaciones nuevas.
