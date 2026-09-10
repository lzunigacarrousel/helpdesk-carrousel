# Helpdesk Carrousel 360

Aplicación de Service Desk de Corporación Carrousel. La rama `v2-rebuild` es la reconstrucción actual para PC TEST y utiliza MariaDB, PHP 8+ y acceso por OTP.

## Estado actual

La instalación vigente usa:

- Carpeta recomendada: `C:\xampp\htdocs\HelpdeskCarrousel`
- Base de TEST: `helpdesk_carrousel_test`
- Entrada web: `http://localhost/HelpdeskCarrousel/public/`
- Configuración local: `config\local.php` (no se versiona)
- Correo en PC TEST: `mail_mode => 'log'`
- Administrador inicial: `luis@carrousel.com.gt`

La base histórica `helpdesk_carrousel` no forma parte de la instalación limpia y no debe eliminarse ni modificarse desde los instaladores de TEST.

## Instalación limpia recomendada

La instalación limpia no se construye ejecutando todas las migraciones antiguas. El flujo canónico es:

1. Clonar o actualizar la rama `v2-rebuild` en `C:\xampp\htdocs\HelpdeskCarrousel`.
2. Ejecutar `INSTALAR_PC_TEST.bat`.
3. Escribir `REINSTALAR` cuando el instalador solicite confirmación.
4. El instalador respalda `helpdesk_carrousel_test` si existe y luego recrea únicamente esa base.
5. El instalador ejecuta, en este orden:
   - `database\INSTALAR.sql` — núcleo V2.
   - `database\FINALIZAR_ESQUEMA_V2.sql` — estructura que requiere la aplicación actual.
   - `database\CATALOGOS_CARROUSEL.sql` — regiones y parques base.
   - `database\VERIFICAR_INSTALACION.sql` — validación estricta del resultado.
6. También instala dependencias Composer y ejecuta los quality gates del proyecto.

Si Composer no está disponible y `vendor\autoload.php` tampoco existe, el instalador se detiene sin considerar válida la instalación.

## Qué crea una instalación nueva

Una instalación nueva conserva solo la estructura que usa la aplicación actual: seguridad/OTP/sesiones, usuarios y perfiles, organización, equipos y alcances, tickets y conversaciones, resoluciones, NPS, problemas conocidos, conocimiento, colaboradores externos, notificaciones, auditoría, SLA y control de versión del esquema.

No se importan automáticamente usuarios, tickets ni datos históricos de Caja Chica u otras versiones. `database\IMPORTAR_DESDE_CAJA_CHICA.sql` queda únicamente como herramienta histórica/opcional y no forma parte del proceso limpio.

## Base de datos

La instalación canónica espera exactamente las tablas definidas por `database\VERIFICAR_INSTALACION.sql`. La verificación falla si falta una tabla requerida o si aparece una tabla extra/legacy en una instalación que debe estar limpia.

Los perfiles `ADMIN`, `SEMIADMIN`, `TECHNICIAN`, `MANAGEMENT`, `SUPERVISOR`, `REQUESTER` y `EXTERNAL` son perfiles vigentes. Gerencia y Supervisor son perfiles de consulta y no deben convertirse automáticamente en Solicitante.

Los parques activos se cargan con una región válida porque el flujo actual de asignaciones y alcance depende de esa relación.

## Acceso y OTP

No se utilizan contraseñas permanentes. El usuario escribe su correo y recibe un código OTP temporal. En PC TEST, con `mail_mode => 'log'`, el código se registra localmente en:

`storage\logs\mail.log`

Para SMTP real se configura únicamente `config\local.php`; nunca deben subirse credenciales al repositorio.

## Seguridad y archivos locales

`.gitignore` excluye `config/local.php`, logs, adjuntos, exportaciones, caché, sesiones, temporales, backups, `vendor/` y otros archivos regenerables o sensibles.

Antes de subir cambios usa `HELPDESK_ADMIN.bat` para revisar estado, diferencias y auditoría de archivos sensibles.

## Scripts históricos

Los archivos `ACTUALIZAR_*`, `INSTALAR_FASE1.sql`, `ACTUALIZAR_FASE1_1.sql`, `ACTUALIZAR_FASE2_1.sql`, `ACTUALIZAR_V1_0_3.sql` y otros scripts de transición documentan etapas anteriores. No deben ejecutarse sobre una instalación limpia actual salvo que una tarea de migración específica lo requiera.

La fuente de verdad para una PC nueva es `INSTALAR_PC_TEST.bat` y los cuatro scripts de instalación/verificación indicados arriba.
