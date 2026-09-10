# Helpdesk Carrousel 360

Aplicación de Service Desk de Corporación Carrousel. La rama `v2-rebuild` es la reconstrucción actual y utiliza PHP 8+, MariaDB y acceso por OTP.

## Fuente de verdad

La aplicación trabaja con **una sola base de datos**:

`helpdesk_carrousel`

TEST y Producción usan el mismo nombre. La separación de ambientes depende de la máquina/servidor y de `config/local.php`, no de nombres distintos de BD.

La instalación limpia se construye desde **un único SQL maestro**:

`database\INSTALAR.sql`

Ese archivo contiene todo el esquema actual, relaciones, trigger de resolución, perfiles, permisos, SLA, regiones, parques, categorías, puestos, soporte, administrador inicial, NPS, notificaciones, problemas conocidos y conocimiento.

No se usan scripts `ACTUALIZAR_*`, fases antiguas, importadores ni parches para una instalación nueva.

## SQL vigentes

La carpeta `database\` contiene únicamente:

- `INSTALAR.sql` — crea `helpdesk_carrousel` y el esquema canónico completo.
- `VERIFICAR_INSTALACION.sql` — valida tablas, columnas, catálogos, permisos, administrador y trigger.
- `VERIFICAR_ESTABILIDAD_V2.sql` — diagnóstico de integridad en modo solo lectura.

Los quality gates fallan si vuelve a aparecer otro `.sql` dentro de `database\`.

## Instalación limpia en PC TEST

Requisitos: XAMPP con MariaDB/MySQL activo, PHP de XAMPP y Composer.

Flujo recomendado:

1. Clonar `v2-rebuild` en `C:\xampp\htdocs\HelpdeskCarrousel`.
2. Confirmar `git status` limpio.
3. Ejecutar `INSTALAR_PC_TEST.bat` y escribir `REINSTALAR` cuando se solicite.
4. El instalador respalda `helpdesk_carrousel` si existe, la elimina y ejecuta únicamente `database\INSTALAR.sql`.
5. Después instala dependencias y ejecuta los quality gates y `database\VERIFICAR_INSTALACION.sql`.

También puede hacerse manualmente desde CMD:

```bat
C:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE IF EXISTS helpdesk_carrousel;"
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 < database\INSTALAR.sql
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 < database\VERIFICAR_INSTALACION.sql
```

Una instalación válida debe dejar una sola base `helpdesk_carrousel`, exactamente 36 tablas canónicas, 29 parques activos con región, 4 regiones y 1 administrador inicial.

## Configuración local

Copiar:

`config\local.php.example` → `config\local.php`

`config/local.php` no se versiona. Valores base:

- `db_host`: `127.0.0.1`
- `db_name`: `helpdesk_carrousel`
- `db_user`: `root`
- `mail_mode`: `log` en PC TEST

Administrador inicial:

`luis@carrousel.com.gt`

El acceso no usa contraseña permanente; se realiza con OTP. En PC TEST, con `mail_mode => 'log'`, el código queda en:

`storage\logs\mail.log`

## Esquema actual

La base canónica cubre seguridad/OTP/sesiones, usuarios y perfiles, asignaciones organizacionales, equipos y alcances, tickets, conversaciones, adjuntos, resoluciones, confirmación del solicitante y NPS, SLA, problemas conocidos, conocimiento, colaboradores externos, notificaciones, auditoría y control de versión del esquema.

Los perfiles vigentes son `ADMIN`, `SEMIADMIN`, `TECHNICIAN`, `MANAGEMENT`, `SUPERVISOR`, `REQUESTER` y `EXTERNAL`.

Gerencia y Supervisor son perfiles de consulta y seguimiento; no son operadores de soporte.

## Calidad y seguridad

Antes de considerar válida una versión deben pasar:

```bat
C:\xampp\php\php.exe tests\static_checks.php
C:\xampp\php\php.exe tests\project_quality.php
C:\xampp\php\php.exe tests\xlsx_smoke.php
```

GitHub Actions también crea una MariaDB vacía y ejecuta `database\INSTALAR.sql` desde cero para comprobar que el repositorio siga siendo instalable sin depender de una base anterior.

`.gitignore` excluye configuración local, logs, adjuntos, exportaciones, caché, sesiones, temporales, backups y `vendor/`.
