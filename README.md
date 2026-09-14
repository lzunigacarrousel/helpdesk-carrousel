# Helpdesk Carrousel 360

Aplicación interna de Service Desk / Helpdesk de Corporación Carrousel.

## Estado canónico

La rama estable y fuente de verdad es:

`main`

Las nuevas fases deben iniciar desde `main` en una rama temporal específica, validarse en PC TEST y solo después integrarse nuevamente a `main`.

El repositorio fue depurado antes de iniciar Fase 5. El trabajo histórico que no forma parte de la rama estable se conserva mediante tags:

- `archive/legacy-v1`
- `archive/v2-rebuild-20260913`

No usar esos tags como ramas de desarrollo. Son puntos de recuperación y consulta histórica.

## Bases de datos

Base activa del Helpdesk V2:

`carrousel_helpdesk`

Base histórica protegida:

`helpdesk_carrousel`

La base histórica no debe eliminarse, recrearse ni modificarse como parte de la instalación o actualización del Helpdesk V2.

TEST y Producción utilizan el mismo nombre lógico `carrousel_helpdesk`; la separación depende del equipo/servidor y de `config/local.php`.

## SQL vigente

La carpeta `database/` contiene el esquema canónico, verificadores y migraciones incrementales vigentes.

Archivos base:

- `INSTALAR.sql` — instalación limpia del esquema actual.
- `VERIFICAR_INSTALACION.sql` — valida una instalación limpia.
- `VERIFICAR_ESTABILIDAD_V2.sql` — diagnóstico de integridad en modo lectura.

Migraciones incrementales actualmente conservadas para instalaciones existentes:

- `MIGRAR_TICKET_WORK_REPORTS_20260912.sql`
- `MIGRAR_EXTERNAL_REPORT_TEMPLATES_20260912.sql`

Verificadores asociados:

- `VERIFICAR_TICKET_WORK_REPORTS_20260912.sql`
- `VERIFICAR_EXTERNAL_REPORT_TEMPLATES_20260912.sql`

Una instalación nueva debe construirse desde `database/INSTALAR.sql`. Las migraciones incrementales existen para actualizar una instalación previa sin reconstruirla.

## Instalación en PC TEST

Requisitos:

- XAMPP con Apache y MariaDB/MySQL.
- PHP de XAMPP.
- Composer.

Flujo recomendado:

1. Confirmar que el repositorio está limpio con `git status`.
2. Trabajar desde la rama de fase aprobada, creada desde `main`.
3. Confirmar `config/local.php` con `db_name => carrousel_helpdesk`.
4. Para instalación limpia, ejecutar `INSTALAR_PC_TEST.bat` y seguir sus confirmaciones.
5. Ejecutar los verificadores y pruebas relacionadas antes de considerar válida la instalación.

También puede validarse manualmente una instalación limpia sobre la base V2:

```bat
C:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE IF EXISTS carrousel_helpdesk;"
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 < database\INSTALAR.sql
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 < database\VERIFICAR_INSTALACION.sql
```

**Nunca ejecutar `DROP DATABASE helpdesk_carrousel` como parte de la instalación V2.**

## Configuración local

Copiar:

`config/local.php.example` → `config/local.php`

`config/local.php` no se versiona.

Valores base de PC TEST:

- `db_host`: `127.0.0.1`
- `db_name`: `carrousel_helpdesk`
- `db_user`: `root`
- `mail_mode`: `log`

Administrador principal:

`luis@carrousel.com.gt`

El acceso utiliza OTP, no contraseña permanente. En PC TEST con `mail_mode => 'log'`, el código queda registrado en:

`storage/logs/mail.log`

## Capacidades actuales

La base estable incluye, entre otras funciones:

- acceso OTP, sesiones, perfiles, permisos y auditoría;
- usuarios, jerarquía organizacional y alcances;
- tickets, clasificación ITSM, SLA y estados de espera;
- conversación pública, notas internas y adjuntos;
- resoluciones, confirmación del solicitante, reapertura y feedback;
- cola de soporte, búsqueda, informes y exportación XLSX;
- notificaciones internas y correo;
- problemas conocidos y conocimiento;
- proveedores/colaboradores externos y control de acceso por caso;
- documentación estructurada de trabajo externo por tipo de servicio;
- manual y ayuda integrada.

Perfiles principales:

- `ADMIN`
- `SEMIADMIN`
- `TECHNICIAN`
- `MANAGEMENT`
- `SUPERVISOR`
- `REQUESTER`
- `EXTERNAL`

Gerencia y Supervisor son perfiles de consulta y seguimiento; no operan tickets como equipo de soporte.

## Roadmap funcional de 12 fases

El roadmap de maduración se mantiene como guía funcional. El estado operativo antes de iniciar Fase 5 es:

| Fase | Tema | Estado |
|---|---|---|
| 1 | Baseline y documentación | Cerrada |
| 2 | UX del solicitante | Cerrada funcionalmente |
| 3 | Operación IT y SLA | Cerrada funcionalmente |
| 4 | Feedback del solicitante | Cerrada |
| 5 | Actividades / visitas | **Actual — en diseño** |
| 6 | Agenda | Pendiente; depende de Fase 5 |
| 7 | Proveedores | Parcialmente adelantada; falta cierre formal |
| 8 | Calidad IT → proveedor | Pendiente |
| 9 | Conocimiento | Parcialmente adelantada; falta cierre formal |
| 10 | Reportes | Parcialmente adelantada; falta consolidación final |
| 11 | Manual | Parcialmente adelantada; consolidación final pendiente |
| 12 | Validación integral | Pendiente |

La validación responsive acumulada, revisión claro/oscuro y cierre transversal se consolidan en Fase 12 aunque las fases previas ya tengan validaciones parciales.

### Fase 5 — Actividades / visitas

La Fase 5 tiene hard gate de base de datos. No debe modificarse el esquema hasta aprobar el diseño completo de la entidad de actividades.

Acuerdos de diseño ya aprobados:

- una actividad pertenece a un ticket;
- un ticket puede tener varias actividades simultáneas;
- tipos iniciales: visita en sitio, soporte remoto, seguimiento, intervención de proveedor y otra;
- estados iniciales: programada, en curso, finalizada y cancelada;
- una reprogramación mantiene la actividad y deja historial de fecha anterior, nueva fecha, motivo y actor;
- no se crearán tablas distintas por tipo de actividad;
- `ticket_events` seguirá siendo bitácora/auditoría, no sustituto de la entidad operativa.

## Flujo Git oficial

Regla general:

```text
main = versión estable
rama de fase = desarrollo + pruebas
PC TEST = validación funcional y visual
merge a main = solo después de aprobación
```

Antes de iniciar una fase:

```bat
git switch main
git pull --ff-only origin main
git status
```

La rama de fase debe crearse desde un `main` limpio. No desarrollar directamente sobre tags históricos ni reutilizar ramas antiguas para nuevas fases.

## Calidad y seguridad

Antes de integrar una fase deben ejecutarse, como mínimo, los quality gates base:

```bat
C:\xampp\php\php.exe tests\static_checks.php
C:\xampp\php\php.exe tests\project_quality.php
C:\xampp\php\php.exe tests\xlsx_smoke.php
```

Además deben ejecutarse las regresiones específicas de la fase y, cuando corresponda, los verificadores SQL.

Cada fase debe preservar:

- permisos y scopes;
- CSRF;
- auditoría;
- separación INTERNAL / EXTERNAL;
- trazabilidad de tickets;
- adjuntos y evidencias;
- funcionamiento claro/oscuro;
- experiencia responsive relevante.

`.gitignore` excluye configuración local, logs, adjuntos, exportaciones, caché, sesiones, temporales, backups y `vendor/`.

## Documentación de referencia

- `docs/superpowers/specs/2026-09-10-helpdesk-functional-maturation-design.md`
- `docs/superpowers/plans/2026-09-10-helpdesk-functional-maturation-implementation.md`
- `docs/ESTANDAR_VISUAL_CARROUSEL.md`
- `CHANGELOG.md`

Algunas referencias históricas dentro de esos documentos pueden mencionar ramas ya archivadas. Para operación actual, este README y `main` son la referencia canónica.
