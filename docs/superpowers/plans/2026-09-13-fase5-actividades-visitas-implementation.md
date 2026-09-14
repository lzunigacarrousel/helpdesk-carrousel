# Fase 5 — Actividades / visitas Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Incorporar actividades operativas ligadas obligatoriamente a tickets —visita en sitio, soporte remoto, seguimiento, intervención de proveedor y otra— con programación, responsable, participantes, reprogramación, ejecución, resultado, evidencias, visibilidad segura al solicitante y trazabilidad completa, sin cerrar ni cambiar automáticamente el ticket.

**Architecture:** Se agregan `ticket_activities` y `ticket_activity_participants` como estado actual consultable; `ticket_events` sigue siendo el historial inmutable. `ticket_attachments` recibe `activity_id` opcional y el almacenamiento físico existente se reutiliza mediante un servicio compartido. La lógica de negocio se concentra en `TicketActivityService`; `TicketActivityController` expone acciones POST pequeñas y `TicketController::show()` solo prepara datos para la vista. Fase 6 consumirá estas tablas y no crea otra entidad de agenda.

**Tech Stack:** PHP 8.2+, MariaDB 10.4+, PDO, MVC PHP existente, JavaScript vanilla, CSS existente, GitHub Actions, scripts de regresión PHP del repositorio.

**Spec:** `docs/superpowers/specs/2026-09-13-fase5-actividades-visitas-design.md`

## Global Constraints

- Baseline aprobada: `main @ 855455f`; implementación únicamente en `fase5-activities` hasta validación final.
- Base activa: `carrousel_helpdesk`; `helpdesk_carrousel` continúa protegida y no debe tocarse.
- Toda actividad requiere `ticket_id`; no existen actividades huérfanas.
- Un ticket puede tener múltiples actividades simultáneas.
- Tipos únicos: `VISITA_EN_SITIO`, `SOPORTE_REMOTO`, `SEGUIMIENTO`, `INTERVENCION_PROVEEDOR`, `OTRA`.
- Estados únicos: `PROGRAMADA`, `EN_CURSO`, `FINALIZADA`, `CANCELADA`.
- Resultados únicos: `RESUELTA`, `PARCIAL`, `SIN_RESOLVER`, `REQUIERE_SEGUIMIENTO`.
- Crear, reprogramar, iniciar, finalizar o cancelar una actividad nunca cambia automáticamente el estado del ticket.
- `VISITA_EN_SITIO` requiere parque; `SOPORTE_REMOTO` requiere `is_remote=1`; `INTERVENCION_PROVEEDOR` requiere proveedor externo con acceso vigente al ticket.
- Responsable principal: usuario interno activo con rol `ADMIN`, `SEMIADMIN` o `TECHNICIAN` y acceso operativo al ticket.
- Actividades internas por defecto; el solicitante solo ve `requester_summary` si `requester_visible=1`.
- Proveedor externo no administra estados de actividad.
- No crear tablas de historial adicionales: reutilizar `ticket_events` + `audit_logs`.
- No crear almacenamiento de archivos paralelo: reutilizar `ticket_attachments` con `activity_id` opcional.
- No implementar calendario global, drag & drop, GPS, viáticos, inventario ni valoración de proveedor; eso queda fuera de Fase 5.
- Mutaciones de BD + `ticket_events` deben ser transaccionales. Auditoría y notificaciones se emiten después del commit; un fallo de correo no revierte una actividad ya registrada.

---

## File map

### Nuevos archivos

- `app/Services/TicketActivityService.php` — reglas de negocio, consultas y transiciones de actividades.
- `app/Services/TicketAttachmentService.php` — almacenamiento reutilizable de adjuntos del ticket y asociación opcional a actividad.
- `app/Controllers/TicketActivityController.php` — endpoints POST de crear, reprogramar, iniciar, finalizar, cancelar y participantes.
- `database/MIGRAR_FASE5_ACTIVIDADES_20260913.sql` — actualización incremental de PC TEST.
- `database/VERIFICAR_FASE5_ACTIVIDADES_20260913.sql` — verificación específica de la migración.
- `public/assets/css/ticket-activities.css` — presentación del módulo de actividades sin alterar la normalización visual global.
- `public/assets/js/ticket-activities.js` — campos progresivos y confirmaciones de UI; nunca reemplaza validación backend.
- `tests/phase5_activities_schema_regression.php` — gate de estructura, permisos y migración.
- `tests/phase5_activities_service_regression.php` — gate de reglas y transiciones.
- `tests/phase5_activities_ui_regression.php` — gate de rutas, formularios, visibilidad y assets.

### Archivos a modificar

- `database/INSTALAR.sql` — esquema canónico, permisos y roles.
- `database/VERIFICAR_INSTALACION.sql` — validar tablas/columnas/permisos nuevos.
- `database/VERIFICAR_ESTABILIDAD_V2.sql` — detectar actividades inconsistentes.
- `.github/workflows/helpdesk-ci.yml` — ejecutar CI en `main` y ramas `fase*`, correr regresiones Fase 5 y actualizar conteo canónico de tablas.
- `app/Services/ScopeService.php` — resolver alcance para un usuario objetivo, no solo para el usuario autenticado.
- `app/Controllers/ConversationController.php` — delegar almacenamiento de archivos al servicio compartido sin cambiar la UX existente.
- `app/Controllers/TicketController.php` — cargar actividades/opciones para soporte y resumen seguro para solicitante.
- `public/index.php` — registrar rutas Fase 5.
- `app/Views/tickets/show.php` — módulo interno de actividades + resumen visible al solicitante.
- `app/Views/help/manual.php` — documentar programación, reprogramación, ejecución y visibilidad.
- `README.md` — marcar Fase 5 como implementada cuando cierre el gate.
- `CHANGELOG.md` — registrar el cierre funcional de Fase 5 sin presentar trabajo archivado como integrado.

---

### Task 1: Gate RED de esquema, permisos, migración y CI

**Files:**
- Create: `tests/phase5_activities_schema_regression.php`
- Modify later in this task: `.github/workflows/helpdesk-ci.yml`
- Inputs inspected: `database/INSTALAR.sql`, `database/VERIFICAR_INSTALACION.sql`, `database/VERIFICAR_ESTABILIDAD_V2.sql`

**Interfaces:**
- Consumes: archivos SQL canónicos y workflow actual.
- Produces: regresión que exige `ticket_activities`, `ticket_activity_participants`, `ticket_attachments.activity_id`, cuatro permisos `activities.*`, migración/verificador y CI activo en `main`/`fase*`.

- [ ] **Step 1: Crear la regresión RED**

Crear `tests/phase5_activities_schema_regression.php` con el patrón de las regresiones existentes:

```php
<?php
declare(strict_types=1);
$root=dirname(__DIR__);$fails=0;
function ok(bool $cond,string $msg):void{global $fails;echo ($cond?'[OK] ':'[FALLO] ').$msg.PHP_EOL;if(!$cond)$fails++;}
function body(string $path):string{$v=@file_get_contents($path);return is_string($v)?$v:'';}

$install=body($root.'/database/INSTALAR.sql');
$migration=body($root.'/database/MIGRAR_FASE5_ACTIVIDADES_20260913.sql');
$verify=body($root.'/database/VERIFICAR_FASE5_ACTIVIDADES_20260913.sql');
$ci=body($root.'/.github/workflows/helpdesk-ci.yml');

ok(str_contains($install,'CREATE TABLE ticket_activities'),'Instalación canónica crea ticket_activities');
ok(str_contains($install,'CREATE TABLE ticket_activity_participants'),'Instalación canónica crea participantes');
ok(str_contains($install,'activity_id BIGINT UNSIGNED NULL'),'Adjuntos admiten relación opcional a actividad');
foreach(['activities.view','activities.create','activities.manage','activities.cancel'] as $permission){
    ok(str_contains($install,$permission),'Permiso canónico '.$permission);
}
foreach(['VISITA_EN_SITIO','SOPORTE_REMOTO','SEGUIMIENTO','INTERVENCION_PROVEEDOR','OTRA'] as $type){
    ok(str_contains($install,$type),'Tipo de actividad '.$type);
}
foreach(['PROGRAMADA','EN_CURSO','FINALIZADA','CANCELADA'] as $status){
    ok(str_contains($install,$status),'Estado de actividad '.$status);
}
ok($migration!==''&&str_contains($migration,'2026-09-13-fase5-actividades'),'Existe migración incremental idempotente');
ok($verify!==''&&str_contains($verify,'ticket_activities'),'Existe verificador específico');
ok(str_contains($ci,"main")&&str_contains($ci,"fase"),'CI cubre main y ramas de fase');
ok(str_contains($ci,'phase5_activities_schema_regression.php'),'CI ejecuta gate Fase 5');

if($fails){fwrite(STDERR,PHP_EOL."[ERROR] {$fails} validación(es) fallaron.".PHP_EOL);exit(1);} 
echo PHP_EOL."[OK] Gate de esquema Fase 5 completado.".PHP_EOL;
```

- [ ] **Step 2: Ejecutar y confirmar RED**

Run:

```bat
C:\xampp\php\php.exe tests\phase5_activities_schema_regression.php
```

Expected: múltiples `[FALLO]` por tablas, permisos, migración y CI inexistentes.

- [ ] **Step 3: Corregir únicamente triggers del CI**

Modificar `.github/workflows/helpdesk-ci.yml`:

```yaml
on:
  push:
    branches: [main, 'fase*']
  pull_request:
    branches: [main]
```

Agregar temporalmente el nuevo test al job `application-quality`:

```yaml
      - name: Phase 5 activities schema regression
        run: php tests/phase5_activities_schema_regression.php
```

No cambiar aún el conteo de tablas; ese ajuste pertenece a Task 2 cuando existan las tablas.

- [ ] **Step 4: Ejecutar la regresión y confirmar que sigue RED por BD**

Run:

```bat
C:\xampp\php\php.exe tests\phase5_activities_schema_regression.php
```

Expected: checks de CI en verde; checks de BD continúan fallando.

- [ ] **Step 5: Commit del gate RED**

```bat
git add tests\phase5_activities_schema_regression.php .github\workflows\helpdesk-ci.yml
git commit -m "test: definir gate de esquema fase 5"
```

---

### Task 2: Esquema canónico + migración incremental + permisos

**Files:**
- Create: `database/MIGRAR_FASE5_ACTIVIDADES_20260913.sql`
- Create: `database/VERIFICAR_FASE5_ACTIVIDADES_20260913.sql`
- Modify: `database/INSTALAR.sql`
- Modify: `database/VERIFICAR_INSTALACION.sql`
- Modify: `database/VERIFICAR_ESTABILIDAD_V2.sql`
- Modify: `.github/workflows/helpdesk-ci.yml`
- Test: `tests/phase5_activities_schema_regression.php`

**Interfaces:**
- Produces tablas `ticket_activities`, `ticket_activity_participants`; columna `ticket_attachments.activity_id`; permisos `activities.view/create/manage/cancel`.
- Later tasks rely on exact column names defined here.

- [ ] **Step 1: Añadir tablas a `database/INSTALAR.sql` después de `ticket_attachments`**

Usar exactamente este núcleo de columnas:

```sql
CREATE TABLE ticket_activities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    activity_type ENUM('VISITA_EN_SITIO','SOPORTE_REMOTO','SEGUIMIENTO','INTERVENCION_PROVEEDOR','OTRA') NOT NULL,
    status ENUM('PROGRAMADA','EN_CURSO','FINALIZADA','CANCELADA') NOT NULL DEFAULT 'PROGRAMADA',
    responsible_user_id BIGINT UNSIGNED NOT NULL,
    provider_user_id BIGINT UNSIGNED NULL,
    park_id BIGINT UNSIGNED NULL,
    is_remote TINYINT(1) NOT NULL DEFAULT 0,
    objective TEXT NOT NULL,
    internal_preparation_notes TEXT NULL,
    scheduled_start_at DATETIME NOT NULL,
    scheduled_end_at DATETIME NOT NULL,
    started_at DATETIME NULL,
    finished_at DATETIME NULL,
    result_code ENUM('RESUELTA','PARCIAL','SIN_RESOLVER','REQUIERE_SEGUIMIENTO') NULL,
    work_performed TEXT NULL,
    result_summary TEXT NULL,
    pending_items TEXT NULL,
    requester_visible TINYINT(1) NOT NULL DEFAULT 0,
    requester_summary VARCHAR(500) NULL,
    cancel_reason VARCHAR(500) NULL,
    cancelled_at DATETIME NULL,
    cancelled_by BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ta_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_ta_responsible FOREIGN KEY (responsible_user_id) REFERENCES users(id),
    CONSTRAINT fk_ta_provider FOREIGN KEY (provider_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_ta_park FOREIGN KEY (park_id) REFERENCES parks(id) ON DELETE SET NULL,
    CONSTRAINT fk_ta_cancelled_by FOREIGN KEY (cancelled_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_ta_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_ta_ticket_status_schedule (ticket_id,status,scheduled_start_at),
    INDEX idx_ta_responsible_status_schedule (responsible_user_id,status,scheduled_start_at),
    INDEX idx_ta_park_status_schedule (park_id,status,scheduled_start_at),
    INDEX idx_ta_provider_status (provider_user_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ticket_activity_participants (
    activity_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (activity_id,user_id),
    CONSTRAINT fk_tap_activity FOREIGN KEY (activity_id) REFERENCES ticket_activities(id) ON DELETE CASCADE,
    CONSTRAINT fk_tap_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_tap_created_by FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Añadir a `ticket_attachments`:

```sql
activity_id BIGINT UNSIGNED NULL,
CONSTRAINT fk_ticket_attachment_activity FOREIGN KEY (activity_id) REFERENCES ticket_activities(id) ON DELETE SET NULL,
INDEX idx_ticket_attachments_activity (activity_id),
```

Ordenar el DDL para que `ticket_activities` exista antes de crear la FK desde `ticket_attachments`; si la tabla de adjuntos ya aparece antes, mover únicamente la declaración de `ticket_attachments` debajo de las dos tablas nuevas sin modificar sus otras columnas.

- [ ] **Step 2: Agregar permisos y asignaciones canónicas**

Añadir a `INSERT INTO permissions`:

```sql
('activities.view','Ver actividades','activities','Consulta actividades operativas dentro del alcance'),
('activities.create','Programar actividades','activities','Crear actividades ligadas a tickets'),
('activities.manage','Gestionar actividades','activities','Reprogramar, iniciar, finalizar y administrar participantes'),
('activities.cancel','Cancelar actividades','activities','Cancelar actividades con motivo auditable'),
```

Asignación:

```text
ADMIN       -> todos por regla existente
SEMIADMIN   -> activities.view/create/manage/cancel
TECHNICIAN  -> activities.view/create/manage/cancel
MANAGEMENT  -> activities.view
SUPERVISOR  -> activities.view
REQUESTER   -> ninguno
EXTERNAL    -> ninguno
```

- [ ] **Step 3: Crear migración incremental idempotente**

`database/MIGRAR_FASE5_ACTIVIDADES_20260913.sql` debe:

1. `USE carrousel_helpdesk; SET NAMES utf8mb4;`
2. `CREATE TABLE IF NOT EXISTS ticket_activities (...)`.
3. `CREATE TABLE IF NOT EXISTS ticket_activity_participants (...)`.
4. `ALTER TABLE ticket_attachments ADD COLUMN IF NOT EXISTS activity_id BIGINT UNSIGNED NULL AFTER comment_id;`
5. `ALTER TABLE ticket_attachments ADD INDEX IF NOT EXISTS idx_ticket_attachments_activity (activity_id);`
6. Consultar `information_schema.REFERENTIAL_CONSTRAINTS`; agregar `fk_ticket_attachment_activity` con SQL preparado solo si no existe.
7. Insertar permisos con `INSERT ... ON DUPLICATE KEY UPDATE name=VALUES(name),module=VALUES(module),description=VALUES(description)`.
8. Insertar `role_permissions` con `INSERT IGNORE` según la matriz aprobada.
9. Registrar:

```sql
INSERT IGNORE INTO schema_migrations(version,name,applied_at)
VALUES('2026-09-13-fase5-actividades','Fase 5 - Actividades y visitas',NOW());
```

- [ ] **Step 4: Crear verificador específico**

`database/VERIFICAR_FASE5_ACTIVIDADES_20260913.sql` debe devolver filas de diagnóstico para:

```sql
SELECT COUNT(*) AS tablas_fase5
FROM information_schema.TABLES
WHERE TABLE_SCHEMA='carrousel_helpdesk'
  AND TABLE_NAME IN ('ticket_activities','ticket_activity_participants');

SELECT COUNT(*) AS activity_id_en_adjuntos
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA='carrousel_helpdesk'
  AND TABLE_NAME='ticket_attachments'
  AND COLUMN_NAME='activity_id';

SELECT code FROM carrousel_helpdesk.permissions
WHERE code IN ('activities.view','activities.create','activities.manage','activities.cancel')
ORDER BY code;
```

Agregar también verificaciones de roles esperados y FK `fk_ticket_attachment_activity`.

- [ ] **Step 5: Actualizar verificadores canónicos y estabilidad**

`VERIFICAR_INSTALACION.sql` debe exigir las dos tablas, la nueva columna, FKs y cuatro permisos.

`VERIFICAR_ESTABILIDAD_V2.sql` debe detectar:

```sql
-- Finalizadas sin datos mínimos
SELECT id,ticket_id FROM ticket_activities
WHERE status='FINALIZADA'
AND (started_at IS NULL OR finished_at IS NULL OR result_code IS NULL
     OR NULLIF(TRIM(work_performed),'') IS NULL
     OR NULLIF(TRIM(result_summary),'') IS NULL);

-- Canceladas sin motivo
SELECT id,ticket_id FROM ticket_activities
WHERE status='CANCELADA'
AND NULLIF(TRIM(cancel_reason),'') IS NULL;

-- Fechas inválidas
SELECT id,ticket_id FROM ticket_activities
WHERE scheduled_end_at<=scheduled_start_at
   OR (finished_at IS NOT NULL AND started_at IS NOT NULL AND finished_at<started_at);

-- Resumen público incoherente
SELECT id,ticket_id FROM ticket_activities
WHERE requester_visible=1 AND NULLIF(TRIM(requester_summary),'') IS NULL;
```

- [ ] **Step 6: Actualizar conteo del CI**

En `.github/workflows/helpdesk-ci.yml`, cambiar el conteo canónico de `36` a `38` tablas.

Agregar al job de base fresca:

```yaml
      - name: Verify Phase 5 schema
        env:
          MYSQL_PWD: root
        run: mariadb -h127.0.0.1 -uroot --default-character-set=utf8mb4 < database/VERIFICAR_FASE5_ACTIVIDADES_20260913.sql
```

- [ ] **Step 7: Ejecutar gate**

```bat
C:\xampp\php\php.exe tests\phase5_activities_schema_regression.php
```

Expected: `[OK] Gate de esquema Fase 5 completado.`

- [ ] **Step 8: Ejecutar migración dos veces en PC TEST temporal/BD de prueba**

```bat
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 < database\MIGRAR_FASE5_ACTIVIDADES_20260913.sql
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 < database\MIGRAR_FASE5_ACTIVIDADES_20260913.sql
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 < database\VERIFICAR_FASE5_ACTIVIDADES_20260913.sql
```

Expected: segunda ejecución sin error y verificaciones presentes.

- [ ] **Step 9: Commit**

```bat
git add database .github\workflows\helpdesk-ci.yml tests\phase5_activities_schema_regression.php
git commit -m "feat: agregar esquema y permisos de actividades"
```

---

### Task 3: Alcance de usuario objetivo + reglas puras de actividad

**Files:**
- Modify: `app/Services/ScopeService.php`
- Create: `app/Services/TicketActivityService.php`
- Create: `tests/phase5_activities_service_regression.php`

**Interfaces:**
- `ScopeService::userCanAccessTicket(int $userId,int $ticketId): bool`
- `TicketActivityService::types(): array`
- `TicketActivityService::results(): array`
- `TicketActivityService::listForTicket(int $ticketId): array`
- `TicketActivityService::requesterVisibleForTicket(int $ticketId): array`
- `TicketActivityService::responsibleOptionsForTicket(int $ticketId): array`
- `TicketActivityService::providerOptionsForTicket(int $ticketId): array`
- `TicketActivityService::create(array $input): int`
- `TicketActivityService::reschedule(int $activityId,array $input): array`
- `TicketActivityService::start(int $activityId): array`
- `TicketActivityService::complete(int $activityId,array $input): array`
- `TicketActivityService::cancel(int $activityId,string $reason): array`
- `TicketActivityService::addParticipant(int $activityId,int $userId): array`
- `TicketActivityService::removeParticipant(int $activityId,int $userId): array`

- [ ] **Step 1: Escribir gate RED de servicio**

`tests/phase5_activities_service_regression.php` debe inspeccionar `TicketActivityService.php` y exigir:

```text
VISITA_EN_SITIO / SOPORTE_REMOTO / SEGUIMIENTO / INTERVENCION_PROVEEDOR / OTRA
PROGRAMADA / EN_CURSO / FINALIZADA / CANCELADA
RESUELTA / PARCIAL / SIN_RESOLVER / REQUIERE_SEGUIMIENTO
scheduled_start_at < scheduled_end_at
requester_visible -> requester_summary requerido
INTERVENCION_PROVEEDOR -> provider requerido
VISITA_EN_SITIO -> park requerido
SOPORTE_REMOTO -> is_remote
FINALIZADA -> result_code/work_performed/result_summary/started_at/finished_at
CANCELADA -> cancel_reason
```

También exigir strings de eventos `ACTIVITY_CREATED`, `ACTIVITY_RESCHEDULED`, `ACTIVITY_STARTED`, `ACTIVITY_COMPLETED`, `ACTIVITY_CANCELLED`.

- [ ] **Step 2: Ejecutar RED**

```bat
C:\xampp\php\php.exe tests\phase5_activities_service_regression.php
```

Expected: fallo porque el servicio no existe.

- [ ] **Step 3: Hacer `ScopeService` independiente del usuario autenticado para consultas objetivo**

Agregar:

```php
public function userCanAccessTicket(int $userId,int $ticketId): bool
{
    if($userId<=0||$ticketId<=0)return false;
    $pdo=Database::pdo();
    $u=$pdo->prepare("SELECT u.id,u.email,u.access_type,r.code role_code FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND u.status='ACTIVE' AND u.deleted_at IS NULL LIMIT 1");
    $u->execute([$userId]);$user=$u->fetch();if(!$user||$user['access_type']!=='INTERNAL')return false;
    $role=(string)$user['role_code'];
    if(in_array($role,['ADMIN','SEMIADMIN','MANAGEMENT'],true))return true;
    $ticket=$pdo->prepare('SELECT park_id,area_id FROM tickets WHERE id=? AND deleted_at IS NULL LIMIT 1');
    $ticket->execute([$ticketId]);$t=$ticket->fetch();if(!$t)return false;
    if($role==='TECHNICIAN'){
        $scopes=$this->supportScopes($userId);
        if(!$scopes)return true;
        foreach($scopes as $scope){
            $type=(string)$scope['scope_type'];
            if($type==='GLOBAL')return true;
            if($type==='PARK'&&(int)$scope['park_id']===(int)$t['park_id'])return true;
            if($type==='AREA'&&(int)$scope['area_id']===(int)$t['area_id'])return true;
            if($type==='PARK_AREA'&&(int)$scope['park_id']===(int)$t['park_id']&&(int)$scope['area_id']===(int)$t['area_id'])return true;
        }
    }
    return false;
}
```

`MANAGEMENT` aparece como lectura global en el servicio genérico; `TicketActivityService` además exige rol operativo para responsables.

- [ ] **Step 4: Crear `TicketActivityService` con constantes y validadores privados**

Definir:

```php
private const TYPES=['VISITA_EN_SITIO','SOPORTE_REMOTO','SEGUIMIENTO','INTERVENCION_PROVEEDOR','OTRA'];
private const STATUSES=['PROGRAMADA','EN_CURSO','FINALIZADA','CANCELADA'];
private const RESULTS=['RESUELTA','PARCIAL','SIN_RESOLVER','REQUIERE_SEGUIMIENTO'];
```

Validadores mínimos:

```php
private function requireScheduledWindow(string $start,string $end): array
private function requireOperationalUser(int $userId,int $ticketId): array
private function requireProvider(int $userId,int $ticketId): array
private function requireActivity(int $activityId): array
private function normalizeVisibleSummary(bool $visible,string $summary): ?string
private function insertEvent(PDO $pdo,int $ticketId,string $eventType,array $newValue,array $metadata=[]): void
```

`requireOperationalUser()` consulta rol `ADMIN|SEMIADMIN|TECHNICIAN`, usuario activo/interno y `ScopeService::userCanAccessTicket()`.

- [ ] **Step 5: Implementar `create()` transaccional**

Entrada esperada:

```php
[
  'ticket_id'=>7,
  'activity_type'=>'VISITA_EN_SITIO',
  'responsible_user_id'=>3,
  'provider_user_id'=>null,
  'park_id'=>5,
  'is_remote'=>false,
  'objective'=>'Revisar conectividad del kiosco',
  'internal_preparation_notes'=>'Llevar adaptador USB-Ethernet',
  'scheduled_start_at'=>'2026-09-16 10:00:00',
  'scheduled_end_at'=>'2026-09-16 12:00:00',
  'requester_visible'=>true,
  'requester_summary'=>'Visita programada para revisar el equipo.',
  'participant_user_ids'=>[4,8],
]
```

Dentro de `Database::transaction()` insertar actividad, participantes únicos y `ACTIVITY_CREATED`. No tocar `tickets.status`.

Después del commit ejecutar `Audit::log('ACTIVITY_CREATED','ticket_activity',$activityId,...)`.

- [ ] **Step 6: Implementar transiciones con bloqueo de estado**

Usar `SELECT ... FOR UPDATE` dentro de transacción.

`reschedule()` solo acepta `PROGRAMADA`, exige motivo y guarda fechas anteriores/nuevas en `metadata_json`.

`start()` solo acepta `PROGRAMADA`, fija `started_at=NOW(), status='EN_CURSO'`.

`complete()` solo acepta `EN_CURSO`; exige `result_code`, `work_performed`, `result_summary`; fija `finished_at` y `FINALIZADA`.

`cancel()` solo acepta `PROGRAMADA|EN_CURSO`, motivo mínimo 5 caracteres; fija `CANCELADA`, `cancelled_at`, `cancelled_by`.

Ningún método actualiza `tickets.status`.

- [ ] **Step 7: Implementar participantes**

`addParticipant()`:
- actividad no finalizada/cancelada;
- usuario interno activo;
- no duplicar responsable;
- `INSERT IGNORE`;
- evento `ACTIVITY_PARTICIPANT_ADDED`.

`removeParticipant()`:
- actividad no finalizada/cancelada;
- DELETE por PK;
- evento `ACTIVITY_PARTICIPANT_REMOVED`.

- [ ] **Step 8: Implementar consultas**

`listForTicket()` devuelve actividad + responsable + proveedor + parque + participantes ordenados por `FIELD(status,'EN_CURSO','PROGRAMADA','FINALIZADA','CANCELADA'), scheduled_start_at`.

`requesterVisibleForTicket()` selecciona únicamente:

```sql
id,activity_type,status,park_id,scheduled_start_at,scheduled_end_at,requester_summary
```

con `requester_visible=1`; no devolver responsable, proveedor, notas, resultado técnico ni auditoría.

- [ ] **Step 9: Ejecutar test**

```bat
C:\xampp\php\php.exe tests\phase5_activities_service_regression.php
C:\xampp\php\php.exe -l app\Services\TicketActivityService.php
C:\xampp\php\php.exe -l app\Services\ScopeService.php
```

Expected: todos OK y sin errores de sintaxis.

- [ ] **Step 10: Commit**

```bat
git add app\Services\ScopeService.php app\Services\TicketActivityService.php tests\phase5_activities_service_regression.php
git commit -m "feat: implementar dominio de actividades"
```

---

### Task 4: Endpoints, permisos, CSRF, auditoría y notificaciones

**Files:**
- Create: `app/Controllers/TicketActivityController.php`
- Modify: `public/index.php`
- Modify: `tests/phase5_activities_ui_regression.php` (crear en esta task)
- Modify: `app/Services/NotificationService.php` solo si se necesita helper; preferir `publishTicket()` existente.

**Interfaces:**
- POST `/tickets/activities/create` -> `create()`
- POST `/tickets/activities/reschedule` -> `reschedule()`
- POST `/tickets/activities/start` -> `start()`
- POST `/tickets/activities/complete` -> `complete()`
- POST `/tickets/activities/cancel` -> `cancel()`
- POST `/tickets/activities/participants/add` -> `addParticipant()`
- POST `/tickets/activities/participants/remove` -> `removeParticipant()`

- [ ] **Step 1: Crear regresión RED de rutas/controlador**

`tests/phase5_activities_ui_regression.php` debe exigir las siete rutas anteriores, `TicketActivityController`, `Csrf::verify`, permisos `activities.create/manage/cancel`, y redirección `#actividades`.

- [ ] **Step 2: Ejecutar RED**

```bat
C:\xampp\php\php.exe tests\phase5_activities_ui_regression.php
```

Expected: fallos de rutas/controlador.

- [ ] **Step 3: Registrar rutas en `public/index.php`**

Añadir `TicketActivityController` al `use` agrupado y registrar los siete POST.

- [ ] **Step 4: Implementar controlador delgado**

Patrón por método:

```php
Auth::requirePermission('activities.manage');
Csrf::verify($_POST['_csrf']??null);
$activityId=(int)Http::post('activity_id');
$result=(new TicketActivityService())->start($activityId);
$this->notifyStarted($result);
Flash::set('Actividad iniciada.','success');
header('Location: '.APP_BASE_URL.'/tickets/view?id='.(int)$result['ticket_id'].'#actividades');exit;
```

Permisos:

```text
create            -> activities.create
reschedule/start/complete/add/remove participant -> activities.manage
cancel            -> activities.cancel
```

`TicketActivityService` sigue validando scope y estado aunque el permiso exista.

- [ ] **Step 5: Notificaciones post-commit**

Usar `NotificationService::notifyUser()` para el responsable cuando otra persona crea/reprograma/cancela una actividad.

Usar `publishTicket()` para solicitante **solo** cuando `requester_visible=1`, con texto derivado exclusivamente de `requester_summary`, fechas y parque seguro.

Para `INTERVENCION_PROVEEDOR`, notificar al proveedor con `notifyUser()` después de comprobar que sigue teniendo acceso externo al ticket.

No notificar por `ACTIVITY_PARTICIPANT_ADDED/REMOVED` al solicitante.

No incluir `internal_preparation_notes`, proveedor, participantes ni motivos internos en mensajes del solicitante.

- [ ] **Step 6: Ejecutar gate**

```bat
C:\xampp\php\php.exe tests\phase5_activities_ui_regression.php
C:\xampp\php\php.exe -l app\Controllers\TicketActivityController.php
C:\xampp\php\php.exe -l public\index.php
```

Expected: rutas/controlador OK.

- [ ] **Step 7: Commit**

```bat
git add app\Controllers\TicketActivityController.php public\index.php tests\phase5_activities_ui_regression.php
git commit -m "feat: agregar endpoints de actividades"
```

---

### Task 5: Servicio compartido de adjuntos + evidencia de actividad

**Files:**
- Create: `app/Services/TicketAttachmentService.php`
- Modify: `app/Controllers/ConversationController.php`
- Modify: `app/Controllers/TicketActivityController.php`
- Modify: `tests/phase5_activities_service_regression.php`
- Regression: `tests/navigation_ux_smoke.php`

**Interfaces:**

```php
TicketAttachmentService::storeUploadedFile(
    PDO $pdo,
    int $ticketId,
    ?int $commentId,
    ?int $activityId,
    string $visibility,
    array $file
): int
```

- [ ] **Step 1: Extender regresión RED**

Exigir que `TicketAttachmentService` centralice `MAX_FILE_SIZE=10485760`, MIME permitidos actuales y que el INSERT incluya `activity_id`.

Exigir que `ConversationController` ya no contenga un segundo `move_uploaded_file` propio.

- [ ] **Step 2: Ejecutar RED**

```bat
C:\xampp\php\php.exe tests\phase5_activities_service_regression.php
```

Expected: fallos por servicio inexistente/refactor pendiente.

- [ ] **Step 3: Extraer almacenamiento sin cambiar comportamiento existente**

Mover la validación de archivo y almacenamiento desde `ConversationController::storeUpload()` a `TicketAttachmentService::storeUploadedFile()`.

El servicio debe verificar, si `$activityId!==null`, que:

```sql
SELECT COUNT(*) FROM ticket_activities WHERE id=? AND ticket_id=?
```

sea `1`; si no, lanzar `RuntimeException('La evidencia no corresponde a esta actividad.')`.

- [ ] **Step 4: Adaptar conversación existente**

Reemplazar la llamada privada por:

```php
$attachmentId=(new TicketAttachmentService())->storeUploadedFile(
    $pdo,$ticketId,$commentId,null,$visibility,$_FILES['attachment']
);
```

La conversación debe seguir guardando exactamente los mismos tipos de archivo, visibilidad, SHA y rutas.

- [ ] **Step 5: Permitir evidencia en finalización**

En `TicketActivityController::complete()`, después de validar el estado pero dentro de la misma transacción de finalización, si existe `$_FILES['evidence']`, guardar con:

```php
(new TicketAttachmentService())->storeUploadedFile(
    $pdo,$ticketId,null,$activityId,'INTERNAL',$_FILES['evidence']
);
```

La evidencia de una actividad es interna por defecto. Publicarla al solicitante requiere el flujo normal de conversación pública; no convertir evidencia técnica en pública automáticamente.

- [ ] **Step 6: Ejecutar regresiones**

```bat
C:\xampp\php\php.exe tests\phase5_activities_service_regression.php
C:\xampp\php\php.exe tests\navigation_ux_smoke.php
C:\xampp\php\php.exe -l app\Services\TicketAttachmentService.php
C:\xampp\php\php.exe -l app\Controllers\ConversationController.php
```

Expected: todo verde; adjuntos existentes no regresan.

- [ ] **Step 7: Commit**

```bat
git add app\Services\TicketAttachmentService.php app\Controllers\ConversationController.php app\Controllers\TicketActivityController.php tests
git commit -m "refactor: reutilizar adjuntos como evidencia de actividades"
```

---

### Task 6: Cargar actividades y opciones en el workspace del ticket

**Files:**
- Modify: `app/Controllers/TicketController.php` en `show()`
- Modify: `tests/phase5_activities_ui_regression.php`

**Interfaces:**
- Vista soporte recibe: `activities`, `activityTypes`, `activityResults`, `activityResponsibleUsers`, `activityProviderUsers`, `activityParks`, `canCreateActivities`, `canManageActivities`, `canCancelActivities`.
- Vista solicitante recibe `requesterActivities` únicamente.

- [ ] **Step 1: Extender test RED**

Exigir que `TicketController::show()` invoque `TicketActivityService`, diferencie soporte/solicitante y no pase responsables/proveedores al requester.

- [ ] **Step 2: Ejecutar RED**

```bat
C:\xampp\php\php.exe tests\phase5_activities_ui_regression.php
```

- [ ] **Step 3: Integrar servicio en `show()`**

Después de calcular `$isSupport`:

```php
$activityService=new TicketActivityService();
$activities=$isSupport?$activityService->listForTicket($id):[];
$requesterActivities=!$isSupport?$activityService->requesterVisibleForTicket($id):[];
$activityResponsibleUsers=$isSupport&&Auth::can('activities.create')?$activityService->responsibleOptionsForTicket($id):[];
$activityProviderUsers=$isSupport&&Auth::can('activities.create')?$activityService->providerOptionsForTicket($id):[];
$activityParks=$isSupport?$pdo->query("SELECT id,name FROM parks WHERE is_active=1 ORDER BY name")->fetchAll():[];
```

Pasar flags con `Auth::can()`.

- [ ] **Step 4: Ejecutar sintaxis/test**

```bat
C:\xampp\php\php.exe -l app\Controllers\TicketController.php
C:\xampp\php\php.exe tests\phase5_activities_ui_regression.php
```

- [ ] **Step 5: Commit**

```bat
git add app\Controllers\TicketController.php tests\phase5_activities_ui_regression.php
git commit -m "feat: cargar actividades en workspace del ticket"
```

---

### Task 7: UI interna de actividades en el ticket

**Files:**
- Modify: `app/Views/tickets/show.php`
- Create: `public/assets/css/ticket-activities.css`
- Create: `public/assets/js/ticket-activities.js`
- Modify: `tests/phase5_activities_ui_regression.php`

**Interfaces:**
- Anchor estable: `id="actividades"`.
- Formularios POST usan las rutas de Task 4.
- JS usa `data-activity-type`, `data-requester-visible`, `data-activity-panel`; no crea transiciones de estado por sí solo.

- [ ] **Step 1: Definir RED visual**

Exigir en test:

```text
id="actividades"
Actividades del caso
+ Programar actividad
Próximas / activas
Historial
name="activity_type"
name="responsible_user_id"
name="scheduled_start_at"
name="scheduled_end_at"
name="objective"
name="requester_visible"
name="requester_summary"
Reprogramar
Iniciar
Finalizar
Cancelar
```

También exigir assets `ticket-activities.css/js`.

- [ ] **Step 2: Ejecutar RED**

```bat
C:\xampp\php\php.exe tests\phase5_activities_ui_regression.php
```

- [ ] **Step 3: Crear módulo interno**

Insertar antes de conversación/resolución una sección:

```php
<section class="card ticket-activities-card" id="actividades">
  <div class="card-body">
    <div class="case-section-head">
      <div><span class="ticket-kicker">Trabajo programado</span><h2>Actividades del caso</h2></div>
      <?php if($canCreateActivities): ?><button type="button" class="btn btn-primary" data-activity-open>+ Programar actividad</button><?php endif; ?>
    </div>
    ...
  </div>
</section>
```

Separar `EN_CURSO|PROGRAMADA` de `FINALIZADA|CANCELADA`.

Cada tarjeta muestra tipo legible, estado, responsable, fecha/hora, parque y resultado cuando exista.

- [ ] **Step 4: Formulario progresivo de alta**

Un único formulario con campos aprobados. `ticket_id` oculto, CSRF y `multipart/form-data` solo en finalizar por evidencia.

JS:
- `INTERVENCION_PROVEEDOR` muestra proveedor obligatorio.
- `VISITA_EN_SITIO` muestra parque y lo hace `required` en cliente.
- `SOPORTE_REMOTO` marca `is_remote=1` y permite parque vacío.
- `requester_visible` muestra `requester_summary` y lo hace requerido en cliente.

Backend continúa siendo fuente de verdad.

- [ ] **Step 5: Acciones por estado**

`PROGRAMADA`: reprogramar, iniciar, cancelar.

`EN_CURSO`: finalizar, cancelar.

`FINALIZADA`: solo lectura.

`CANCELADA`: solo lectura.

No mostrar botones si faltan permisos.

- [ ] **Step 6: CSS responsive y tema**

`ticket-activities.css` debe:
- usar variables existentes, no colores hardcodeados innecesarios;
- 2 columnas de tarjetas en escritorio amplio;
- 1 columna <= 900px;
- controles >= 44px en tablet/móvil;
- respetar `html[data-theme="dark"]` y variables ya existentes.

- [ ] **Step 7: Ejecutar checks**

```bat
C:\xampp\php\php.exe tests\phase5_activities_ui_regression.php
C:\xampp\php\php.exe tests\dark_theme_smoke.php
C:\xampp\php\php.exe tests\dark_theme_motion_smoke.php
node --check public\assets\js\ticket-activities.js
C:\xampp\php\php.exe -l app\Views\tickets\show.php
```

- [ ] **Step 8: Commit**

```bat
git add app\Views\tickets\show.php public\assets\css\ticket-activities.css public\assets\js\ticket-activities.js tests\phase5_activities_ui_regression.php
git commit -m "feat: agregar experiencia de actividades al ticket"
```

---

### Task 8: Resumen seguro para solicitante

**Files:**
- Modify: `app/Views/tickets/show.php`
- Modify: `tests/phase5_activities_ui_regression.php`
- Regression: `tests/requester_return_visibility_smoke.php`

**Interfaces:**
- Consume únicamente `requesterActivities` de `TicketActivityService::requesterVisibleForTicket()`.

- [ ] **Step 1: Extender RED de privacidad**

El test debe exigir texto `Próxima atención` y bloquear en la sección de requester cualquier impresión de:

```text
responsible_user_id
provider_user_id
internal_preparation_notes
cancel_reason
work_performed
result_summary
```

- [ ] **Step 2: Ejecutar RED**

```bat
C:\xampp\php\php.exe tests\phase5_activities_ui_regression.php
```

- [ ] **Step 3: Renderizar resumen mínimo**

Para cada actividad visible mostrar solo:

```text
tipo legible
estado legible
scheduled_start_at / scheduled_end_at
parque si existe
requester_summary
```

No mostrar controles de administración.

- [ ] **Step 4: Ejecutar regresiones de visibilidad**

```bat
C:\xampp\php\php.exe tests\phase5_activities_ui_regression.php
C:\xampp\php\php.exe tests\requester_return_visibility_smoke.php
```

Expected: no fuga de datos internos.

- [ ] **Step 5: Commit**

```bat
git add app\Views\tickets\show.php tests\phase5_activities_ui_regression.php
git commit -m "feat: mostrar actividades seguras al solicitante"
```

---

### Task 9: Manual, README, CHANGELOG y estado del roadmap

**Files:**
- Modify: `app/Views/help/manual.php`
- Modify: `README.md`
- Modify: `CHANGELOG.md`
- Modify: `docs/superpowers/plans/2026-09-10-helpdesk-functional-maturation-implementation.md`
- Test: `tests/phase5_activities_ui_regression.php`

**Interfaces:** documentación consistente con la implementación terminada.

- [ ] **Step 1: Añadir checks documentales al test**

Exigir que el manual contenga `Actividades`, `Programar`, `Reprogramar`, `Finalizar` y que explique que finalizar actividad no cierra el ticket.

- [ ] **Step 2: Actualizar Manual**

Soporte:
- cómo programar;
- tipos;
- reprogramación/cancelación con motivo;
- inicio/finalización;
- visibilidad al solicitante;
- independencia respecto al estado del ticket.

Solicitante:
- qué significa `Próxima atención`;
- que solo ve información publicada por soporte.

No enseñar controles internos a perfiles que no los tienen.

- [ ] **Step 3: Actualizar roadmap**

En README:

```text
Fase 5 — Actividades / visitas: IMPLEMENTADA / pendiente validación integral Fase 12
Fase 6 — Agenda: siguiente fase, dependiente de ticket_activities
```

En `2026-09-10-helpdesk-functional-maturation-implementation.md`, marcar los puntos de Fase 5 cumplidos y mantener Fase 6 pendiente.

- [ ] **Step 4: CHANGELOG**

Agregar arriba una entrada explícita de Fase 5 indicando:
- nueva entidad operativa;
- dos tablas nuevas + relación de adjuntos;
- permisos;
- sin cierre automático de ticket;
- solicitante con resumen seguro;
- Fase 6 todavía no implementada.

No modificar la nota que marca `v2.4.0-dev` archivado/no integrado.

- [ ] **Step 5: Ejecutar gate**

```bat
C:\xampp\php\php.exe tests\phase5_activities_ui_regression.php
```

- [ ] **Step 6: Commit**

```bat
git add README.md CHANGELOG.md app\Views\help\manual.php docs\superpowers\plans\2026-09-10-helpdesk-functional-maturation-implementation.md tests\phase5_activities_ui_regression.php
git commit -m "docs: cerrar documentacion funcional fase 5"
```

---

### Task 10: Integración final, fresh install y gate de no-regresión

**Files:**
- Modify if necessary: `tests/static_checks.php`
- Modify if necessary: `tests/project_quality.php`
- Modify: `.github/workflows/helpdesk-ci.yml` para ejecutar todos los tests Fase 5 si aún falta alguno.
- No crear nuevas funciones en esta task salvo correcciones derivadas de fallos reproducibles.

**Interfaces:** entrega final de Fase 5 validada.

- [ ] **Step 1: Añadir los tres tests Fase 5 al CI**

```yaml
      - name: Phase 5 schema regression
        run: php tests/phase5_activities_schema_regression.php
      - name: Phase 5 service regression
        run: php tests/phase5_activities_service_regression.php
      - name: Phase 5 UI regression
        run: php tests/phase5_activities_ui_regression.php
```

- [ ] **Step 2: Ejecutar sintaxis completa local**

```bat
for /r app %%F in (*.php) do @C:\xampp\php\php.exe -l "%%F" || exit /b 1
for /r public %%F in (*.php) do @C:\xampp\php\php.exe -l "%%F" || exit /b 1
node --check public\assets\js\ticket-activities.js
```

Expected: sin errores.

- [ ] **Step 3: Ejecutar regresiones Fase 5**

```bat
C:\xampp\php\php.exe tests\phase5_activities_schema_regression.php
C:\xampp\php\php.exe tests\phase5_activities_service_regression.php
C:\xampp\php\php.exe tests\phase5_activities_ui_regression.php
```

Expected: todos `[OK]`.

- [ ] **Step 4: Ejecutar gates existentes críticos**

```bat
C:\xampp\php\php.exe tests\static_checks.php
C:\xampp\php\php.exe tests\project_quality.php
C:\xampp\php\php.exe tests\xlsx_smoke.php
C:\xampp\php\php.exe tests\phase4_feedback_regression.php
C:\xampp\php\php.exe tests\itsm21_phase_closure_regression.php
C:\xampp\php\php.exe tests\external_work_report_regression.php
C:\xampp\php\php.exe tests\external_service_templates_regression.php
C:\xampp\php\php.exe tests\navigation_ux_smoke.php
C:\xampp\php\php.exe tests\requester_return_visibility_smoke.php
C:\xampp\php\php.exe tests\dark_theme_smoke.php
C:\xampp\php\php.exe tests\dark_theme_motion_smoke.php
```

Expected: todos verdes. Si alguno falla, usar `superpowers:systematic-debugging`; no parchear a ciegas.

- [ ] **Step 5: Fresh install en BD desechable/CI**

Ejecutar `database/INSTALAR.sql`, luego:

```sql
SELECT COUNT(*) FROM information_schema.TABLES
WHERE TABLE_SCHEMA='carrousel_helpdesk' AND TABLE_TYPE='BASE TABLE';
```

Expected: `38`.

Ejecutar `VERIFICAR_INSTALACION.sql` y `VERIFICAR_FASE5_ACTIVIDADES_20260913.sql` sin errores.

- [ ] **Step 6: Prueba funcional manual PC TEST**

Caso de prueba mínimo:

1. Abrir un ticket existente dentro del alcance.
2. Programar `VISITA_EN_SITIO` visible al solicitante.
3. Confirmar que el ticket conserva su estado previo.
4. Reprogramar con motivo y comprobar evento/auditoría.
5. Iniciar actividad.
6. Finalizar como `PARCIAL` con evidencia.
7. Confirmar que el ticket sigue sin cerrarse automáticamente.
8. Programar `INTERVENCION_PROVEEDOR` con proveedor autorizado.
9. Confirmar que proveedor no ve controles para iniciar/finalizar/cancelar.
10. Entrar como solicitante y comprobar que solo ve resumen, fecha, estado y parque publicados.
11. Cancelar otra actividad programada y confirmar que permanece en historial.
12. Validar claro, oscuro, 1366, iPad horizontal/vertical y móvil.

- [ ] **Step 7: Revisar diff y secretos**

```bat
git --no-pager diff --check
git status
git --no-pager diff main...HEAD --stat
git --no-pager grep -n -E "password\s*=|smtp.*pass|api[_-]?key|secret" -- . ":(exclude)vendor" ":(exclude).git"
```

Expected: diff limpio, sin secretos nuevos.

- [ ] **Step 8: Commit final de gate si hubo solo ajustes de integración**

```bat
git add .github\workflows\helpdesk-ci.yml tests README.md CHANGELOG.md docs app public database
git commit -m "test: cerrar gate integral fase 5"
```

Si no hubo cambios después del commit anterior, no crear commit vacío.

- [ ] **Step 9: No merge automático**

Al terminar, dejar `fase5-activities` publicada y validada. El merge a `main` se hace únicamente después de que el usuario confirme la prueba funcional en PC TEST.

---

## Self-review del plan

### Cobertura de especificación

- Entidad única + múltiples actividades: Tasks 2–3.
- Tipos/estados/resultados: Tasks 2–3.
- Responsable/participantes/proveedor: Tasks 3–4.
- Ubicación/remoto: Tasks 2–3, UI Task 7.
- Programación y tiempos reales: Tasks 2–3.
- Reprogramación/cancelación auditable: Tasks 3–4.
- Resultado obligatorio: Task 3 + UI Task 7.
- Evidencia reutilizando adjuntos: Task 5.
- Solicitante seguro: Task 8.
- Proveedor sin control de estados: Tasks 3–4 + prueba manual Task 10.
- Independencia del estado del ticket: Tasks 3–4 + gate manual Task 10.
- Notificaciones selectivas: Task 4.
- Permisos/scopes/CSRF: Tasks 2–4.
- Instalación limpia + migración incremental: Task 2 + Task 10.
- Manual/roadmap: Task 9.
- Fase 6 fuera de alcance: Global Constraints + Task 9.

### Consistencia de nombres

Los nombres canónicos usados por todo el plan son:

```text
ticket_activities
ticket_activity_participants
ticket_attachments.activity_id
TicketActivityService
TicketAttachmentService
TicketActivityController
activities.view
activities.create
activities.manage
activities.cancel
```

No se introducen sinónimos alternativos ni tablas de historial paralelas.
