# Fase 6 — Agenda Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementar `/agenda` como vista operativa de lectura sobre `ticket_activities`, con Calendario + Lista, filtros, alcance backend, atrasadas, conflictos y navegación al ticket, sin duplicar las operaciones de Fase 5.

**Architecture:** `AgendaController` controla acceso y normaliza GET; `AgendaService` hace consultas de solo lectura sobre `ticket_activities` + `tickets` + `users` + `parks`, aplicando `ScopeService` sobre `tickets`. La misma colección alimenta Calendario y Lista; JavaScript solo mejora presentación.

**Tech Stack:** PHP 8+, MariaDB/MySQL, HTML server-rendered, CSS Carrousel existente, JavaScript vanilla progresivo, XAMPP, tests PHP CLI, GitHub Actions.

**Spec:** `docs/superpowers/specs/2026-09-14-fase6-agenda-design.md`

## Global Constraints

- Rama: `fase6-agenda`.
- Cero cambios estructurales de BD.
- Reutilizar `ticket_activities`; no crear tabla de calendario.
- No crear permisos nuevos.
- Scope aplicado en backend mediante `ScopeService` sobre `tickets`.
- `REQUESTER` y `EXTERNAL`: sin acceso.
- `MANAGEMENT` y `SUPERVISOR`: consulta únicamente.
- `TECHNICIAN`: `scope_mode=mine` por defecto; puede cambiar a `all` sin salir de su scope.
- Estados activos por defecto: `PROGRAMADA`, `EN_CURSO`.
- Historial explícito habilita `FINALIZADA`, `CANCELADA`.
- `ATRASADA` y `has_conflict` son derivados; no se persisten.
- Rango personalizado máximo: 90 días.
- Sin frameworks JS ni librerías de calendario grandes.
- Fase 5 debe permanecer verde durante toda la implementación.

---

## File Map

**Create**
- `app/Controllers/AgendaController.php` — acceso, filtros/rango y ViewModel.
- `app/Services/AgendaService.php` — consultas scoped, atrasadas, conflictos, catálogos y ticket search.
- `app/Views/agenda/index.php` — Calendario, Lista, filtros y búsqueda de ticket.
- `public/assets/css/agenda.css` — layout/estados/responsive.
- `public/assets/js/agenda.js` — posicionamiento visual y disclosure progresivo.
- `tests/phase6_agenda_service_regression.php` — contrato/servicio.
- `tests/phase6_agenda_ui_regression.php` — ruta/controller/UI/nav/docs.

**Modify**
- `public/index.php`
- `app/Views/shared/app_start.php`
- `app/Views/shared/app_end.php`
- `app/Views/help/manual.php`
- `.github/workflows/helpdesk-ci.yml`
- `README.md`
- `CHANGELOG.md`
- `docs/superpowers/plans/2026-09-10-helpdesk-functional-maturation-implementation.md`

**Do not modify**
- `database/INSTALAR.sql`
- `database/MIGRAR_*.sql`
- esquema de `ticket_activities`
- transiciones de `TicketActivityService`

---

### Task 1: Contrato de AgendaService y cálculos puros

**Files:**
- Create: `app/Services/AgendaService.php`
- Create: `tests/phase6_agenda_service_regression.php`

**Interfaces:**
- Produces:
  - `activities(array $filters): array`
  - `overdueBefore(string $from,array $filters): array`
  - `filterOptions(array $filters): array`
  - `searchTickets(string $query,int $limit=10): array`
  - `static markConflicts(array $rows): array`
  - `static hourWindow(array $rows): array`

- [ ] **Step 1: Write the failing test**

Create `tests/phase6_agenda_service_regression.php`:

```php
<?php
declare(strict_types=1);
$root=dirname(__DIR__);$path=$root.'/app/Services/AgendaService.php';
$body=is_file($path)?file_get_contents($path):'';$errors=0;
function ok(bool $c,string $m):void{global $errors;echo($c?'[OK] ':'[FALLO] ').$m.PHP_EOL;if(!$c)$errors++;}
ok($body!=='','Existe AgendaService');
ok(str_contains($body,'function activities('),'Expone activities');
ok(str_contains($body,'function overdueBefore('),'Expone overdueBefore');
ok(str_contains($body,'function filterOptions('),'Expone filterOptions');
ok(str_contains($body,'function searchTickets('),'Expone searchTickets');
ok(str_contains($body,'function markConflicts('),'Expone markConflicts');
ok(str_contains($body,'function hourWindow('),'Expone hourWindow');
ok(!str_contains($body,'INSERT INTO ticket_activities'),'Agenda no crea actividades');
ok(!str_contains($body,'UPDATE ticket_activities'),'Agenda no modifica actividades');
if($body!==''){
    require_once $path;
    $rows=[
        ['activity_id'=>1,'responsible_user_id'=>10,'status'=>'PROGRAMADA','scheduled_start_at'=>'2026-09-14 09:00:00','scheduled_end_at'=>'2026-09-14 10:00:00'],
        ['activity_id'=>2,'responsible_user_id'=>10,'status'=>'PROGRAMADA','scheduled_start_at'=>'2026-09-14 09:30:00','scheduled_end_at'=>'2026-09-14 10:30:00'],
        ['activity_id'=>3,'responsible_user_id'=>10,'status'=>'PROGRAMADA','scheduled_start_at'=>'2026-09-14 10:30:00','scheduled_end_at'=>'2026-09-14 11:00:00'],
        ['activity_id'=>4,'responsible_user_id'=>11,'status'=>'PROGRAMADA','scheduled_start_at'=>'2026-09-14 09:30:00','scheduled_end_at'=>'2026-09-14 10:30:00'],
    ];
    $marked=\App\Services\AgendaService::markConflicts($rows);
    ok($marked[0]['has_conflict']===true,'Solapamiento marca A');
    ok($marked[1]['has_conflict']===true,'Solapamiento marca B');
    ok($marked[2]['has_conflict']===false,'Límite exacto no es conflicto');
    ok($marked[3]['has_conflict']===false,'Responsable distinto no es conflicto');
    ok(\App\Services\AgendaService::hourWindow([])===['start_hour'=>8,'end_hour'=>18],'Ventana vacía 08–18');
    $w=\App\Services\AgendaService::hourWindow([['scheduled_start_at'=>'2026-09-14 06:20:00','scheduled_end_at'=>'2026-09-14 19:15:00']]);
    ok($w===['start_hour'=>5,'end_hour'=>21],'Ventana se expande y redondea');
}
if($errors){fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);exit(1);}echo '[OK] Contrato base AgendaService.'.PHP_EOL;
```

- [ ] **Step 2: Verify RED**

```bat
C:\xampp\php\php.exe tests\phase6_agenda_service_regression.php
```

Expected: FAIL because `AgendaService.php` is absent.

- [ ] **Step 3: Implement minimal service contract + pure calculations**

Create `app/Services/AgendaService.php` with constants `ACTIVE_STATUSES`, `ALL_STATUSES`, `TYPES`; implement `markConflicts()` using strict overlap `startA < endB && endA > startB`; implement `hourWindow()` with base `08:00–18:00`, one-hour padding, ceiling on end minutes, limits `0..24`.

Use these exact temporary DB-backed method bodies in Task 1 so the pure contract can pass without exposing a route yet:

```php
public function activities(array $filters):array{return[];}
public function overdueBefore(string $from,array $filters):array{return[];}
public function filterOptions(array $filters):array{return['responsibles'=>[],'parks'=>[]];}
public function searchTickets(string $query,int $limit=10):array{return[];}
```

- [ ] **Step 4: Verify GREEN**

```bat
C:\xampp\php\php.exe -l app\Services\AgendaService.php
C:\xampp\php\php.exe tests\phase6_agenda_service_regression.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bat
git add app\Services\AgendaService.php tests\phase6_agenda_service_regression.php
git commit -m "test: definir contrato de agenda"
```

---

### Task 2: Consultas scoped, estados, atrasadas y ticket search

**Files:**
- Modify: `app/Services/AgendaService.php`
- Modify: `tests/phase6_agenda_service_regression.php`

**Interfaces:**
- Consumes: `ScopeService::ticketConstraint('t')`, `Auth::id()`, `Auth::role()`, `Database::pdo()`.
- Produces normalized activity rows with `activity_id`, `ticket_id`, `ticket_code`, `ticket_subject`, `activity_type`, `status`, `scheduled_start_at`, `scheduled_end_at`, `responsible_user_id`, `responsible_name`, `park_id`, `park_name`, `is_overdue`, `has_conflict`, `ticket_url`.

- [ ] **Step 1: Add failing service/SQL gates**

Add to service regression:

```php
ok(str_contains($body,'new ScopeService'),'Usa ScopeService');
ok(str_contains($body,'JOIN tickets t ON t.id=a.ticket_id'),'Une actividades con tickets');
ok(str_contains($body,'t.deleted_at IS NULL'),'Excluye tickets eliminados');
ok(str_contains($body,'a.responsible_user_id'),'Filtra responsable');
ok(str_contains($body,'a.park_id'),'Filtra parque');
ok(str_contains($body,'a.activity_type'),'Filtra tipo');
ok(str_contains($body,'PROGRAMADA'),'Contempla estado PROGRAMADA');
ok(str_contains($body,'EN_CURSO'),'Contempla estado EN_CURSO');
ok(str_contains($body,'FINALIZADA'),'Contempla historial FINALIZADA');
ok(str_contains($body,'CANCELADA'),'Contempla historial CANCELADA');
ok(str_contains($body,'#actividades'),'Construye URL al bloque de actividades');
ok(str_contains($body,'LIMIT')&&str_contains($body,'searchTickets'),'Búsqueda de tickets es limitada');
```

- [ ] **Step 2: Verify RED**

```bat
C:\xampp\php\php.exe tests\phase6_agenda_service_regression.php
```

Expected: FAIL on scoped query gates.

- [ ] **Step 3: Implement `activities()` and shared query builder**

Build parameterized SQL from:

```php
[$scopeSql,$scopeParams]=(new ScopeService())->ticketConstraint('t');
$where=['t.deleted_at IS NULL'];$params=[];
if($scopeSql!=='1=1'){$where[]=$scopeSql;array_push($params,...$scopeParams);}
```

Effective state rules:

```php
$history=!empty($filters['history']);
$status=(string)($filters['status']??'active');
$allowed=$history?self::ALL_STATUSES:self::ACTIVE_STATUSES;
$states=$status==='all'&&$history
    ?self::ALL_STATUSES
    :($status==='active'||!in_array($status,$allowed,true)?self::ACTIVE_STATUSES:[$status]);
```

Date overlap for visible range:

```sql
a.scheduled_start_at < DATE_ADD(?,INTERVAL 1 DAY)
AND a.scheduled_end_at >= ?
```

Main SELECT must resolve all display data without N+1:

```sql
SELECT a.id activity_id,a.ticket_id,t.ticket_number ticket_code,t.subject ticket_subject,
       a.activity_type,a.status,a.scheduled_start_at,a.scheduled_end_at,
       a.responsible_user_id,u.full_name responsible_name,
       a.park_id,p.name park_name
FROM ticket_activities a
JOIN tickets t ON t.id=a.ticket_id
JOIN users u ON u.id=a.responsible_user_id
LEFT JOIN parks p ON p.id=a.park_id
```

`TECHNICIAN + scope_mode=mine` must append `a.responsible_user_id=Auth::id()` server-side. `responsible_user_id`, `park_id`, and `activity_type` may only narrow after the scope condition.

After fetch:

```php
$now=time();
foreach($rows as &$row){
    $row['is_overdue']=(string)$row['status']==='PROGRAMADA'&&strtotime((string)$row['scheduled_end_at'])<$now;
    $row['ticket_url']=APP_BASE_URL.'/tickets/view?id='.(int)$row['ticket_id'].'#actividades';
}
unset($row);
return self::markConflicts($rows);
```

- [ ] **Step 4: Implement `overdueBefore()`**

Use the same scope + narrowing filters, but force:

```sql
a.status='PROGRAMADA'
AND a.scheduled_end_at < ?
```

with `$from.' 00:00:00'`. If the selected explicit status excludes `PROGRAMADA`, return `[]` so the overdue block respects the state filter.

- [ ] **Step 5: Implement scoped `filterOptions()`**

Return only visible support users and parks. Use the same ticket scope in SQL; never query unrestricted global catalogs for Agenda filters.

Return shape:

```php
['responsibles'=>[['id'=>1,'full_name'=>'...']], 'parks'=>[['id'=>1,'name'=>'...']]]
```

- [ ] **Step 6: Implement scoped `searchTickets()`**

Normalize:

```php
$query=trim($query);if(mb_strlen($query)<2)return[];
$limit=max(1,min(10,$limit));
```

Search visible non-deleted tickets by ticket number or subject, return max 10 rows with `ticket_url` ending `#actividades`.

- [ ] **Step 7: Verify GREEN + Fase 5 service**

```bat
C:\xampp\php\php.exe -l app\Services\AgendaService.php
C:\xampp\php\php.exe tests\phase6_agenda_service_regression.php
C:\xampp\php\php.exe tests\phase5_activities_service_regression.php
```

- [ ] **Step 8: Commit**

```bat
git add app\Services\AgendaService.php tests\phase6_agenda_service_regression.php
git commit -m "feat: consultar agenda con alcance y filtros"
```

---

### Task 3: AgendaController y GET `/agenda`

**Files:**
- Create: `app/Controllers/AgendaController.php`
- Modify: `public/index.php`
- Create: `tests/phase6_agenda_ui_regression.php`

**Interfaces:**
- Produces ViewModel: `filters`, `activities`, `overdue`, `options`, `ticketMatches`, `hourWindow`, `canProgram`, `scopeLabel`, `notice`.

- [ ] **Step 1: Write failing route/controller gates**

Create `tests/phase6_agenda_ui_regression.php` checking:

```php
$root=dirname(__DIR__);$router=file_get_contents($root.'/public/index.php');
$cp=$root.'/app/Controllers/AgendaController.php';$controller=is_file($cp)?file_get_contents($cp):'';$errors=0;
function ok(bool $c,string $m):void{global $errors;echo($c?'[OK] ':'[FALLO] ').$m.PHP_EOL;if(!$c)$errors++;}
ok(str_contains($router,'AgendaController'),'Router conoce AgendaController');
ok(str_contains($router,"['GET','/agenda'"),'Existe GET /agenda');
ok($controller!=='','Existe AgendaController');
ok(str_contains($controller,"['ADMIN','SEMIADMIN','TECHNICIAN','MANAGEMENT','SUPERVISOR']"),'Roles permitidos explícitos');
ok(str_contains($controller,'http_response_code(403)'),'No autorizado recibe 403');
ok(str_contains($controller,'AgendaService'),'Controller delega al servicio');
ok(!str_contains($controller,'SELECT '),'Controller no ejecuta SQL');
if($errors)exit(1);
```

- [ ] **Step 2: Verify RED**

```bat
C:\xampp\php\php.exe tests\phase6_agenda_ui_regression.php
```

- [ ] **Step 3: Implement controller**

`AgendaController::index()` must:

1. `Auth::requireLogin()`.
2. Reject external or roles outside `[ADMIN, SEMIADMIN, TECHNICIAN, MANAGEMENT, SUPERVISOR]` with HTTP 403 and `errors/friendly`.
3. Normalize GET in private `filters()`.
4. Call `AgendaService` methods only.
5. Render `agenda/index` with the ViewModel listed above.

Normalization rules:

```text
view: calendar|list; default calendar
calendar default: Monday..Sunday current week
list default: today..today+6
custom range: both dates valid, from<=to, max 90 calendar days
history: only "1" is true
status: active|all|PROGRAMADA|EN_CURSO|FINALIZADA|CANCELADA
history=0 + historical status => active
TECHNICIAN default scope_mode=mine; other allowed roles => all
only TECHNICIAN may keep mine
positive IDs else 0
activity_type only Fase 5 enum
program=1 only ADMIN/SEMIADMIN/TECHNICIAN
ticket_q trimmed, max 100 chars
```

Use `DateTimeImmutable::createFromFormat('!Y-m-d',$value)` plus round-trip equality for strict dates. Invalid range resets to default and sets a friendly notice.

- [ ] **Step 4: Register route**

Import `AgendaController` in `public/index.php` and add:

```php
['GET','/agenda',[AgendaController::class,'index']],
```

near dashboard/search routes.

- [ ] **Step 5: Verify GREEN**

```bat
C:\xampp\php\php.exe -l app\Controllers\AgendaController.php
C:\xampp\php\php.exe -l public\index.php
C:\xampp\php\php.exe tests\phase6_agenda_ui_regression.php
```

- [ ] **Step 6: Commit**

```bat
git add app\Controllers\AgendaController.php public\index.php tests\phase6_agenda_ui_regression.php
git commit -m "feat: agregar ruta y controlador de agenda"
```

---

### Task 4: Lista, filtros y atrasadas server-rendered

**Files:**
- Create: `app/Views/agenda/index.php`
- Create: `public/assets/css/agenda.css`
- Modify: `tests/phase6_agenda_ui_regression.php`

- [ ] **Step 1: Add failing UI gates**

Require view/CSS plus strings/controls:

```php
ok(str_contains($view,'Calendario'),'Selector Calendario');
ok(str_contains($view,'Lista'),'Selector Lista');
ok(str_contains($view,'name="responsible_user_id"'),'Filtro Responsable');
ok(str_contains($view,'name="park_id"'),'Filtro Parque');
ok(str_contains($view,'name="activity_type"'),'Filtro Tipo');
ok(str_contains($view,'name="status"'),'Filtro Estado');
ok(str_contains($view,'name="from"')&&str_contains($view,'name="to"'),'Filtro rango');
ok(str_contains($view,'Pendientes atrasadas'),'Bloque atrasadas');
ok(str_contains($view,'#actividades'),'Enlaces al ticket');
```

- [ ] **Step 2: Verify RED**

```bat
C:\xampp\php\php.exe tests\phase6_agenda_ui_regression.php
```

- [ ] **Step 3: Implement `app/Views/agenda/index.php`**

Set:

```php
$pageTitle='Agenda';$pageSection='Agenda';$activeNav='agenda';$helpContext='agenda';
require APP_ROOT.'/app/Views/shared/app_start.php';
```

Define human type/status label arrays. Render one GET filter form and quick links `Hoy`, `Esta semana`, `Próximos 30 días`, `Historial`. Preserve current filter state explicitly.

Group activities by `Y-m-d` and render list rows with time, type, ticket code, park, responsible, status and conflict. Render `$overdue` first under `Pendientes atrasadas`. Only use escaped service fields; never show internal preparation, technical result, or notes.

Finish with `app_end.php`.

- [ ] **Step 4: Implement base CSS**

Minimum contract:

```css
.agenda-shell{display:grid;gap:16px}
.agenda-toolbar{display:flex;flex-wrap:wrap;justify-content:space-between;gap:12px}
.agenda-filters{display:grid;grid-template-columns:repeat(5,minmax(150px,1fr));gap:10px}
.agenda-list,.agenda-day{display:grid;gap:12px}
.agenda-item{display:grid;grid-template-columns:minmax(88px,auto) minmax(0,1fr) auto;gap:12px;align-items:center}
.agenda-item.is-programada{border-left:4px solid var(--brand)}
.agenda-item.is-overdue{border-left-color:var(--warning,#b7791f)}
```

Use existing variables; no separate hard-coded theme system.

- [ ] **Step 5: Verify GREEN**

```bat
C:\xampp\php\php.exe -l app\Views\agenda\index.php
C:\xampp\php\php.exe tests\phase6_agenda_ui_regression.php
```

- [ ] **Step 6: Commit**

```bat
git add app\Views\agenda\index.php public\assets\css\agenda.css tests\phase6_agenda_ui_regression.php
git commit -m "feat: agregar lista y filtros de agenda"
```

---

### Task 5: Calendario semanal + responsive

**Files:**
- Modify: `app/Views/agenda/index.php`
- Modify: `public/assets/css/agenda.css`
- Create: `public/assets/js/agenda.js`
- Modify: `tests/phase6_agenda_ui_regression.php`

- [ ] **Step 1: Add failing calendar gates**

Require `.agenda-calendar`, `.agenda-calendar-grid`, seven-day container, `Semana anterior`, `Hoy`, `Semana siguiente`, conflict copy, media queries `1023px` and `760px`.

- [ ] **Step 2: Verify RED**

```bat
C:\xampp\php\php.exe tests\phase6_agenda_ui_regression.php
```

- [ ] **Step 3: Render week/calendar from the same `$activities`**

Build seven day keys from `filters[from]`; render navigation preserving filters. Create 30-minute slots between `hourWindow.start_hour` and `end_hour`.

For each event compute integer `data-start-slot` and `data-span-slots`; the anchor still contains readable text and href when JS is absent.

- [ ] **Step 4: Implement progressive `agenda.js`**

```js
(() => {
  document.querySelectorAll('.agenda-event[data-start-slot][data-span-slots]').forEach((event) => {
    const start = Number.parseInt(event.dataset.startSlot || '0', 10);
    const span = Math.max(1, Number.parseInt(event.dataset.spanSlots || '1', 10));
    if (Number.isFinite(start)) event.style.setProperty('--agenda-start-slot', String(start));
    event.style.setProperty('--agenda-span-slots', String(span));
  });
})();
```

No filtering/access logic in JS.

- [ ] **Step 5: Implement calendar/responsive CSS**

Desktop/iPad horizontal: hourly 7-day grid. `max-width:1023px`: hide hourly grid and show day-card representation. `max-width:760px`: one column filters/cards, no mandatory horizontal scrolling.

- [ ] **Step 6: Verify GREEN**

```bat
C:\xampp\php\php.exe -l app\Views\agenda\index.php
C:\xampp\php\php.exe tests\phase6_agenda_ui_regression.php
```

If Node exists:

```bat
node --check public\assets\js\agenda.js
```

If Node is not installed on PC TEST, record that local limitation; CI/source regression remains mandatory.

- [ ] **Step 7: Commit**

```bat
git add app\Views\agenda\index.php public\assets\css\agenda.css public\assets\js\agenda.js tests\phase6_agenda_ui_regression.php
git commit -m "feat: agregar calendario semanal responsive"
```

---

### Task 6: Navegación, assets y Programar actividad

**Files:**
- Modify: `app/Views/shared/app_start.php`
- Modify: `app/Views/shared/app_end.php`
- Modify: `app/Views/agenda/index.php`
- Modify: `tests/phase6_agenda_ui_regression.php`

- [ ] **Step 1: Add failing shell/program gates**

Check for `activeNav==='agenda'`, `/agenda`, conditional `agenda.css`, conditional `agenda.js`, `Programar actividad`, `ticket_q`, and `ticketMatches`.

- [ ] **Step 2: Verify RED**

```bat
C:\xampp\php\php.exe tests\phase6_agenda_ui_regression.php
```

- [ ] **Step 3: Add explicit nav eligibility**

In `app_start.php`:

```php
$canAgenda=!$isExternal&&in_array((string)Auth::role(),['ADMIN','SEMIADMIN','TECHNICIAN','MANAGEMENT','SUPERVISOR'],true);
```

Support operators see Agenda in Soporte; management viewers see Agenda in Gestión. Requester/external branches get no link.

Load `agenda.css` only when `activeNav=agenda`; load `agenda.js` similarly in `app_end.php` with `defer`.

- [ ] **Step 4: Add Programar actividad ticket selector**

Only when `$canProgram===true`. GET form uses hidden `program=1`, preserves current view, and sends `ticket_q` (`maxlength=100`). Render max 10 `$ticketMatches`; each link uses `ticket_url` ending in `#actividades`. MANAGEMENT/SUPERVISOR never see this control.

- [ ] **Step 5: Verify GREEN + Fase 5 UI**

```bat
C:\xampp\php\php.exe -l app\Views\shared\app_start.php
C:\xampp\php\php.exe -l app\Views\shared\app_end.php
C:\xampp\php\php.exe tests\phase6_agenda_ui_regression.php
C:\xampp\php\php.exe tests\phase5_activities_ui_regression.php
```

- [ ] **Step 6: Commit**

```bat
git add app\Views\shared\app_start.php app\Views\shared\app_end.php app\Views\agenda\index.php tests\phase6_agenda_ui_regression.php
git commit -m "feat: integrar agenda en navegación y tickets"
```

---

### Task 7: Manual, CI y cierre documental

**Files:**
- Modify: `app/Views/help/manual.php`
- Modify: `.github/workflows/helpdesk-ci.yml`
- Modify: `README.md`
- Modify: `CHANGELOG.md`
- Modify: `docs/superpowers/plans/2026-09-10-helpdesk-functional-maturation-implementation.md`
- Modify: `tests/phase6_agenda_ui_regression.php`

- [ ] **Step 1: Add failing documentation/CI gates**

Require Manual `Agenda/Calendario/Lista`, README Agenda, CHANGELOG Agenda, roadmap `Fase 6` + `IMPLEMENTADA`, and both phase6 tests in CI.

- [ ] **Step 2: Verify RED**

```bat
C:\xampp\php\php.exe tests\phase6_agenda_ui_regression.php
```

- [ ] **Step 3: Update Manual**

Document profile-specific usage:
- Técnico: Mis actividades, Todo mi alcance, filtros, atrasadas/conflictos, abrir ticket, programar vía ticket.
- Admin/Semiadmin: consulta global visible y filtros.
- Gerencia/Supervisión: consulta sin operar.
- Do not expose Admin instructions in requester/external manual sections.

- [ ] **Step 4: Update CI**

Add:

```yaml
- name: Phase 6 agenda service regression
  run: php tests/phase6_agenda_service_regression.php
- name: Phase 6 agenda UI regression
  run: php tests/phase6_agenda_ui_regression.php
```

Preserve all prior jobs/steps.

- [ ] **Step 5: Update README/CHANGELOG/roadmap**

README row:

```markdown
| 6 | Agenda | **Implementada — pendiente validación integral Fase 12** |
```

Document: Calendario + Lista, scope backend, active/history, overdue/conflicts derived, no new DB table, ticket remains operational workspace. CHANGELOG explicitly says `BD: sin cambios`. Roadmap marks Fase 6 complete and Fase 7 next.

- [ ] **Step 6: Verify GREEN**

```bat
C:\xampp\php\php.exe tests\phase6_agenda_ui_regression.php
```

- [ ] **Step 7: Commit**

```bat
git add app\Views\help\manual.php .github\workflows\helpdesk-ci.yml README.md CHANGELOG.md docs\superpowers\plans\2026-09-10-helpdesk-functional-maturation-implementation.md tests\phase6_agenda_ui_regression.php
git commit -m "docs: cerrar documentación funcional fase 6"
```

---

### Task 8: Quality gates y validación PC TEST

**Files:** no functional changes unless a verified regression requires a separate approved fix.

- [ ] **Step 1: Sync and verify clean branch**

```bat
cd /d C:\xampp\htdocs\HelpdeskCarrousel
git status
git pull --ff-only origin fase6-agenda
git status
```

- [ ] **Step 2: Lint Phase 6 PHP**

```bat
C:\xampp\php\php.exe -l app\Services\AgendaService.php
C:\xampp\php\php.exe -l app\Controllers\AgendaController.php
C:\xampp\php\php.exe -l app\Views\agenda\index.php
C:\xampp\php\php.exe -l app\Views\shared\app_start.php
C:\xampp\php\php.exe -l app\Views\shared\app_end.php
```

- [ ] **Step 3: Run Phase 6 regressions**

```bat
C:\xampp\php\php.exe tests\phase6_agenda_service_regression.php
C:\xampp\php\php.exe tests\phase6_agenda_ui_regression.php
```

- [ ] **Step 4: Re-run critical Phase 5 regressions**

```bat
C:\xampp\php\php.exe tests\phase5_activities_schema_regression.php
C:\xampp\php\php.exe tests\phase5_activities_service_regression.php
C:\xampp\php\php.exe tests\phase5_activities_ui_regression.php
C:\xampp\php\php.exe tests\requester_return_visibility_smoke.php
```

- [ ] **Step 5: Run global quality gates**

```bat
C:\xampp\php\php.exe tests\static_checks.php
C:\xampp\php\php.exe tests\project_quality.php
C:\xampp\php\php.exe tests\xlsx_smoke.php
```

- [ ] **Step 6: Verify zero Phase 6 DB diff**

```bat
git --no-pager diff origin/main...HEAD -- database
```

Expected: empty.

- [ ] **Step 7: Manual access matrix**

Verify:

```text
ADMIN       allowed, global, Programar visible
SEMIADMIN   allowed, Programar visible
TECHNICIAN  defaults Mine; All respects scope
MANAGEMENT  allowed, consultation only
SUPERVISOR  scoped consultation only
REQUESTER   HTTP 403 friendly
EXTERNAL    HTTP 403 friendly and no nav
```

- [ ] **Step 8: Manual data matrix**

Verify:

```text
PROGRAMADA         visible default
EN_CURSO           visible default
FINALIZADA         hidden default; visible Historial
CANCELADA          hidden default; visible Historial
overdue PROGRAMADA appears in Pendientes atrasadas
same-tech overlap  Conflicto de horario
end=start boundary no conflict
different tech     no conflict
activity click      opens #actividades
```

- [ ] **Step 9: Manual visual matrix**

Validate light/dark as applicable:

```text
1920x1080       hourly Calendar + List
1366x768        hourly Calendar usable
iPad horizontal hourly Calendar compact
iPad vertical   day-card representation
mobile <=760    one column, no mandatory horizontal scroll
```

Capture desktop Calendar, desktop List, iPad vertical/day cards, mobile and one dark-theme screenshot.

- [ ] **Step 10: Diff hygiene**

```bat
git --no-pager diff --check origin/main...HEAD
git --no-pager diff --stat origin/main...HEAD
git status
```

Expected: clean working tree; no whitespace errors.

---

## Final Acceptance Checklist

- [ ] `/agenda` is GET/read-only.
- [ ] No new DB tables, columns, migrations or permissions.
- [ ] Agenda uses `ticket_activities` joined to scoped tickets.
- [ ] TECHNICIAN defaults to Mine and All never widens scope.
- [ ] MANAGEMENT/SUPERVISOR cannot operate.
- [ ] REQUESTER/EXTERNAL receive 403 and no nav.
- [ ] Active default = PROGRAMADA + EN_CURSO.
- [ ] Historial explicitly enables FINALIZADA + CANCELADA.
- [ ] Overdue active work before visible range remains visible.
- [ ] Conflict detection marks strict overlaps only.
- [ ] Calendar and List share the same filtered collection.
- [ ] Every activity opens its ticket at `#actividades`.
- [ ] Programar activity searches scoped tickets and reuses Fase 5.
- [ ] 1920, 1366, iPad horizontal, iPad vertical, mobile usable.
- [ ] Light/dark legible.
- [ ] Fase 5 regressions green.
- [ ] Static checks, project quality and XLSX smoke green.
- [ ] README, Manual, CHANGELOG, roadmap and CI updated.
- [ ] Keep branch `fase6-agenda`; no merge to `main` before PC TEST approval.
