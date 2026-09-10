# Helpdesk Carrousel 360

Aplicación de Service Desk de Corporación Carrousel. La etapa activa de desarrollo trabaja sobre la rama `ui-normalization-working`, que conserva la normalización visual ya completada y entra ahora en maduración funcional de Helpdesk Carrousel V2. La rama `v2-rebuild` queda como baseline anterior de la reconstrucción y no debe usarse como fuente de verdad para esta etapa.

## Fuente de verdad

La fuente de verdad de la etapa actual es:

`ui-normalization-working`

No reconstruir la aplicación ni repetir una normalización visual general. Los cambios deben mejorar lo existente sin romper lógica, permisos, seguridad, trazabilidad ni experiencia ya aprobada.

El nuevo Helpdesk trabaja con una sola base de datos propia:

`carrousel_helpdesk`

La base histórica del sistema anterior es:

`helpdesk_carrousel`

**La base histórica está protegida y no debe ser eliminada, recreada ni modificada por el instalador V2.** Permanecerá disponible durante la transición hasta que el nuevo Helpdesk la sustituya operativamente.

TEST y Producción del nuevo Helpdesk usan el mismo nombre `carrousel_helpdesk`. La separación de ambientes depende de la máquina/servidor y de `config/local.php`, no de nombres distintos de BD.

La instalación limpia se construye desde un único SQL maestro:

`database\INSTALAR.sql`

Ese archivo contiene todo el esquema actual, relaciones, trigger de resolución, perfiles, permisos, SLA, regiones, parques, categorías, puestos, soporte, administrador inicial, NPS, notificaciones, problemas conocidos y conocimiento.

No se usan scripts históricos de actualización, fases antiguas, importadores ni parches para una instalación nueva.

## SQL vigentes

La carpeta `database\` contiene únicamente:

- `INSTALAR.sql` — crea `carrousel_helpdesk` y el esquema canónico completo.
- `VERIFICAR_INSTALACION.sql` — valida tablas, columnas, catálogos, permisos, administrador y trigger de `carrousel_helpdesk`.
- `VERIFICAR_ESTABILIDAD_V2.sql` — diagnóstico de integridad en modo solo lectura sobre `carrousel_helpdesk`.

Los quality gates fallan si vuelve a aparecer otro `.sql` dentro de `database\` o si el nuevo sistema vuelve a apuntar a la base histórica.

## Instalación limpia en PC TEST

Requisitos: XAMPP con MariaDB/MySQL activo, PHP de XAMPP y Composer.

Flujo recomendado:

1. Clonar o actualizar la rama de trabajo en `C:\xampp\htdocs\HelpdeskCarrousel`.
2. Confirmar `git status` limpio.
3. Confirmar que `config\local.php` tenga `db_name => carrousel_helpdesk`.
4. Ejecutar `INSTALAR_PC_TEST.bat` y escribir `REINSTALAR` cuando se solicite.
5. El instalador respalda únicamente `carrousel_helpdesk` si existe, elimina únicamente esa base y ejecuta `database\INSTALAR.sql`.
6. Después instala dependencias y ejecuta los quality gates y `database\VERIFICAR_INSTALACION.sql`.

El instalador contiene una protección explícita para impedir que `DB_NAME` sea `helpdesk_carrousel`.

También puede hacerse manualmente desde CMD, siempre sobre la base nueva:

```bat
C:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE IF EXISTS carrousel_helpdesk;"
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 < database\INSTALAR.sql
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 < database\VERIFICAR_INSTALACION.sql
```

**Nunca ejecutar `DROP DATABASE helpdesk_carrousel` como parte de la instalación V2.**

Una instalación válida debe dejar `carrousel_helpdesk` con exactamente 36 tablas canónicas, 29 parques activos con región, 4 regiones y 1 administrador inicial. La base histórica `helpdesk_carrousel` puede coexistir y no forma parte de las validaciones V2.

## Configuración local

Copiar:

`config\local.php.example` → `config\local.php`

`config/local.php` no se versiona. Valores base:

- `db_host`: `127.0.0.1`
- `db_name`: `carrousel_helpdesk`
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

## Maduración funcional V2

La etapa actual está documentada en:

- `docs/superpowers/specs/2026-09-10-helpdesk-functional-maturation-design.md`
- `docs/superpowers/plans/2026-09-10-helpdesk-functional-maturation-implementation.md`

Principio central: completar la mayor parte de la etapa con **cero cambios estructurales de BD**. Si una capacidad requiere nueva estructura, debe justificarse antes de modificar `database/INSTALAR.sql` y sus verificadores. No se permiten `ALTER TABLE` sueltos, migraciones manuales olvidadas ni estructuras preparadas “por si acaso”.

La línea visual vigente está definida en `docs/ESTANDAR_VISUAL_CARROUSEL.md` y se considera congelada salvo defectos reales o necesidades funcionales puntuales.

## Calidad y seguridad

Antes de considerar válida una versión deben pasar:

```bat
C:\xampp\php\php.exe tests\static_checks.php
C:\xampp\php\php.exe tests\project_quality.php
C:\xampp\php\php.exe tests\xlsx_smoke.php
```

GitHub Actions también crea una MariaDB vacía y ejecuta `database\INSTALAR.sql` desde cero para comprobar que el repositorio siga siendo instalable sin depender de una base anterior.

`.gitignore` excluye configuración local, logs, adjuntos, exportaciones, caché, sesiones, temporales, backups y `vendor/`.
