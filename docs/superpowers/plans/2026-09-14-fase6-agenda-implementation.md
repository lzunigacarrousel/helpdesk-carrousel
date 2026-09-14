# Fase 6 — Agenda Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementar `/agenda` como una vista operativa de lectura sobre `ticket_activities`, con Calendario + Lista, alcance backend, filtros, atrasadas, conflictos y navegación al ticket, sin crear tablas ni duplicar operaciones de Fase 5.

**Architecture:** `AgendaController` normaliza acceso y filtros GET; `AgendaService` consulta `ticket_activities` unida a `tickets`, `users` y `parks`, aplicando `ScopeService` sobre `tickets`. La UI server-rendered comparte una única colección para Calendario y Lista; JavaScript solo mejora interacción. Toda operación de actividad sigue ocurriendo en `TicketActivityController` dentro del ticket.

**Tech Stack:** PHP 8+, MariaDB/MySQL, HTML server-rendered, CSS existente de Carrousel, JavaScript vanilla progresivo, XAMPP, tests PHP CLI, GitHub Actions.

**Spec:** `docs/superpowers/specs/2026-09-14-fase6-agenda-design.md`

## Global Constraints

- Trabajar únicamente en `fase6-agenda`.
- No crear tabla de calendario ni modificar estructura de BD.
- Reutilizar obligatoriamente `ticket_activities`.
- No crear permisos nuevos.
- Aplicar alcance en backend mediante `ScopeService` sobre `tickets`.
- `REQUESTER` y `EXTERNAL` no acceden a Agenda.
- `MANAGEMENT` y `SUPERVISOR` consultan pero no operan actividades.
- `TECHNICIAN` inicia en `scope_mode=mine`; `ADMIN`, `SEMIADMIN`, `MANAGEMENT`, `SUPERVISOR` inician en `all`.
- Estados activos por defecto: `PROGRAMADA`, `EN_CURSO`.
- Historial explícito habilita además `FINALIZADA`, `CANCELADA`.
- `ATRASADA` es derivada; no se persiste.
- Conflictos se advierten; no bloquean.
- Rango personalizado máximo: 90 días.
- Sin frameworks JS ni librerías de calendario grandes.
- La seguridad no depende de JavaScript ni del viewport.
- Cada cambio debe preservar Fase 5 y sus regresiones.

---

## File Map

### Nuevos

- `app/Controllers/AgendaController.php` — gate de acceso, normalización GET, rango, preparación de ViewModel.
- `app/Services/AgendaService.php` — lectura scoped de actividades, filtros, atrasadas, conflictos, catálogos y búsqueda de tickets.
- `app/Views/agenda/index.php` — render único de Agenda, Lista, Calendario, filtros y flujo de selección de ticket.
- `public/assets/css/agenda.css` — layout calendario/lista, estados, responsive y dark-compatible usando variables existentes.
- `public/assets/js/agenda.js` — mejora progresiva del selector de vista y panel de programación; ninguna regla de seguridad.
- `tests/phase6_agenda_service_regression.php` — contrato del servicio y cálculos puros.
- `tests/phase6_agenda_ui_regression.php` — ruta, controller, navegación, perfiles y contrato UI.

### Modificados

- `public/index.php` — importar `AgendaController` y registrar `GET /agenda`.
- `app/Views/shared/app_start.php` — enlace Agenda por perfil y CSS específico cuando `activeNav=agenda`.
- `app/Views/shared/app_end.php` — cargar `agenda.js` únicamente en Agenda.
- `app/Views/help/manual.php` — sección Agenda por perfil.
- `.github/workflows/helpdesk-ci.yml` — ejecutar regresiones Fase 6.
- `README.md` — marcar Fase 6 implementada al cierre.
- `CHANGELOG.md` — registrar Agenda.
- `docs/superpowers/plans/2026-09-10-helpdesk-functional-maturation-implementation.md` — actualizar estado Fase 6.

### No tocar

- `database/INSTALAR.sql`
- `database/MIGRAR_*.sql`
- esquema `ticket_activities`
- transiciones de `TicketActivityService`

---

### Task 1: Contrato de AgendaService y cálculos derivados

**Files:**
- Create: `app/Services/AgendaService.php`
- Create: `tests/phase6_agenda_service_regression.php`

**Interfaces:**
- Consumes: `App\Core\{Auth,Database}`, `App\Services\ScopeService`, `APP_BASE_URL`.
- Produces:
  - `public function activities(array $filters): array`
  - `public function overdueBefore(string $from, array $filters): array`
  - `public function filterOptions(array $filters): array`
  - `public function searchTickets(string $query, int $limit=10): array`
  - `public static function markConflicts(array $rows): array`
  - `public static function hourWindow(array $rows): array`

- [ ] **Step 1: Write the failing regression test for service shape and pure conflict/window rules**

Create `tests/phase6_agenda_service_regression.php` with executable checks. Include at least these assertions:

```php
<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$servicePath=$root.'/app/Services/AgendaService.php';
$body=is_file($servicePath)?file_get_contents($servicePath):'';
$errors=0;
function ok(bool $condition,string $label):void{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$label.PHP_EOL;
    if(!$condition)$errors++;
}

ok($body!=='','Existe AgendaService');
ok(str_contains($body,'function activities('),'AgendaService expone activities');
ok(str_contains($body,'function overdueBefore('),'AgendaService expone overdueBefore');
ok(str_contains($body,'function filterOptions('),'AgendaService expone filterOptions');
ok(str_contains($body,'function searchTickets('),'AgendaService expone searchTickets');
ok(str_contains($body,'function markConflicts('),'AgendaService expone markConflicts');
ok(str_contains($body,'function hourWindow('),'AgendaService expone hourWindow');
ok(str_contains($body,'new ScopeService'),'AgendaService aplica ScopeService');
ok(str_contains($body,"scheduled_end_at < NOW()")||str_contains($body,'scheduled_end_at<NOW()'),'Atrasada se deriva por fecha y PROGRAMADA');
ok(!str_contains($body,'INSERT INTO ticket_activities'),'AgendaService no crea actividades');
ok(!str_contains($body,'UPDATE ticket_activities'),'AgendaService no cambia actividades');

if($body!==''){
    require_once $servicePath;
    $rows=[
        ['activity_id'=>1,'responsible_user_id'=>10,'status'=>'PROGRAMADA','scheduled_start_at'=>'2026-09-14 09:00:00','scheduled_end_at'=>'2026-09-14 10:00:00'],
        ['activity_id'=>2,'responsible_user_id'=>10,'status'=>'PROGRAMADA','scheduled_start_at'=>'2026-09-14 09:30:00','scheduled_end_at'=>'2026-09-14 10:30:00'],
        ['activity_id'=>3,'responsible_user_id'=>10,'status'=>'PROGRAMADA','scheduled_start_at'=>'2026-09-14 10:30:00','scheduled_end_at'=>'2026-09-14 11:00:00'],
        ['activity_id'=>4,'responsible_user_id'=>11,'status'=>'PROGRAMADA','scheduled_start_at'=>'2026-09-14 09:30:00','scheduled_end_at'=>'2026-09-14 10:30:00'],
    ];
    $marked=\App\Services\AgendaService::markConflicts($rows);
    ok(($marked[0]['has_conflict']??false)===true,'Solapamiento marca primera actividad');
    ok(($marked[1]['has_conflict']??false)===true,'Solapamiento marca segunda actividad');
    ok(($marked[2]['has_conflict']??true)===false,'Intervalos que solo tocan límite no chocan');
    ok(($marked[3]['has_conflict']??true)===false,'Responsables distintos no chocan');

    $window=\App\Services\AgendaService::hourWindow([
        ['scheduled_start_at'=>'2026-09-14 06:20:00','scheduled_end_at'=>'2026-09-14 19:15:00'],
    ]);
    ok($window['start_hour']===5,'Ventana expande una hora antes');
    ok($window['end_hour']===21,'Ventana redondea y expande una hora después');
    $base=\App\Services\AgendaService::hourWindow([]);
    ok($base===['start_hour'=>8,'end_hour'=>18],'Ventana vacía usa 08:00–18:00');
}

if($errors){fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);exit(1);}
echo '[OK] Contrato base de AgendaService completado.'.PHP_EOL;
```

- [ ] **Step 2: Run the test to verify RED**

Run:

```bat
C:\xampp\php\php.exe tests\phase6_agenda_service_regression.php
```

Expected: FAIL because `AgendaService.php` does not exist.

- [ ] **Step 3: Implement the minimal AgendaService skeleton and pure calculations**

Create `app/Services/AgendaService.php` with this public surface and pure calculations:

```php
<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth,Database};
use DateTimeImmutable;
use PDO;

final class AgendaService
{
    private const ACTIVE_STATUSES=['PROGRAMADA','EN_CURSO'];
    private const ALL_STATUSES=['PROGRAMADA','EN_CURSO','FINALIZADA','CANCELADA'];
    private const TYPES=['VISITA_EN_SITIO','SOPORTE_REMOTO','SEGUIMIENTO','INTERVENCION_PROVEEDOR','OTRA'];

    public static function markConflicts(array $rows): array
    {
        foreach($rows as &$row)$row['has_conflict']=false;
        unset($row);
        $byResponsible=[];
        foreach($rows as $index=>$row){
            if(!in_array((string)($row['status']??''),self::ACTIVE_STATUSES,true))continue;
            $uid=(int)($row['responsible_user_id']??0);
            if($uid>0)$byResponsible[$uid][]=$index;
        }
        foreach($byResponsible as $indexes){
            $count=count($indexes);
            for($i=0;$i<$count;$i++)for($j=$i+1;$j<$count;$j++){
                $a=$rows[$indexes[$i]];$b=$rows[$indexes[$j]];
                $aStart=strtotime((string)$a['scheduled_start_at']);
                $aEnd=strtotime((string)$a['scheduled_end_at']);
                $bStart=strtotime((string)$b['scheduled_start_at']);
                $bEnd=strtotime((string)$b['scheduled_end_at']);
                if($aStart<$bEnd&&$aEnd>$bStart){
                    $rows[$indexes[$i]]['has_conflict']=true;
                    $rows[$indexes[$j]]['has_conflict']=true;
                }
            }
        }
        return $rows;
    }

    public static function hourWindow(array $rows): array
    {
        if(!$rows)return['start_hour'=>8,'end_hour'=>18];
        $start=8;$end=18;
        foreach($rows as $row){
            $s=new DateTimeImmutable((string)$row['scheduled_start_at']);
            $e=new DateTimeImmutable((string)$row['scheduled_end_at']);
            $start=min($start,max(0,(int)$s->format('G')-1));
            $endHour=(int)$e->format('G')+((int)$e->format('i')>0?1:0)+1;
            $end=max($end,min(24,$endHour));
        }
        return['start_hour'=>$start,'end_hour'=>$end];
    }

    public function activities(array $filters): array { return $this->queryActivities($filters,false); }
    public function overdueBefore(string $from,array $filters): array { return $this->queryActivities($filters,true,$from); }
    public function filterOptions(array $filters): array { return ['responsibles'=>[],'parks'=>[]]; }
    public function searchTickets(string $query,int $limit=10): array { return []; }

    private function queryActivities(array $filters,bool $overdueOnly=false,?string $before=null): array
    {
        new ScopeService();
        return [];
    }
}
```

The empty DB-backed methods are temporary only within this task; the next task replaces them before any feature route is added.

- [ ] **Step 4: Run lint and service regression**

```bat
C:\xampp\php\php.exe -l app\Services\AgendaService.php
C:\xampp\php\php.exe tests\phase6_agenda_service_regression.php
```

Expected: PASS for pure conflict/window contract and no syntax errors.

- [ ] **Step 5: Commit**

```bat
git add app\Services\AgendaService.php tests\phase6_agenda_service_regression.php
git commit -m "test: definir contrato de agenda"
```

---

### Task 2: Consultas scoped, filtros, atrasadas, catálogos y búsqueda de tickets

**Files:**
- Modify: `app/Services/AgendaService.php`
- Modify: `tests/phase6_agenda_service_regression.php`

**Interfaces:**
- Consumes: `ScopeService::ticketConstraint('t')`, `Auth::id()`, `Auth::role()`.
- Produces: filas normalizadas con `activity_id`, `ticket_id`, `ticket_code`, `ticket_subject`, `activity_type`, `status`, `scheduled_start_at`, `scheduled_end_at`, `responsible_user_id`, `responsible_name`, `park_id`, `park_name`, `is_overdue`, `has_conflict`, `ticket_url`.

- [ ] **Step 1: Extend regression with SQL/security gates**

Add checks that require all of these fragments/behaviors:

```php
ok(str_contains($body,'JOIN tickets t ON t.id=a.ticket_id'),'Agenda consulta actividades unidas a tickets');
ok(str_contains($body,"t.deleted_at IS NULL"),'Agenda excluye tickets eliminados');
ok(str_contains($body,"a.status IN"),'Agenda limita estados en SQL');
ok(str_contains($body,'responsible_user_id'),'Agenda permite filtrar responsable');
ok(str_contains($body,'a.park_id'),'Agenda permite filtrar parque');
ok(str_contains($body,'a.activity_type'),'Agenda permite filtrar tipo');
ok(str_contains($body,'LIMIT 10')||str_contains($body,'$limit'),'Búsqueda de tickets queda limitada');
ok(str_contains($body,"ticket_url"),'Servicio construye enlace a #actividades');
ok(str_contains($body,"#actividades"),'Enlace de Agenda apunta al bloque actividades');
```

Also require that `overdueBefore()` only returns `PROGRAMADA` before the visible range and that `scope_mode=mine` is enforced server-side for technicians.

- [ ] **Step 2: Run regression and verify RED**

```bat
C:\xampp\php\php.exe tests\phase6_agenda_service_regression.php
```

Expected: FAIL on DB query/security gates.

- [ ] **Step 3: Implement query builder with ScopeService**

Replace the temporary DB methods. `queryActivities()` must build conditions like:

```php
[$scopeSql,$scopeParams]=(new ScopeService())->ticketConstraint('t');
$where=['t.deleted_at IS NULL'];
$params=[];
if($scopeSql!=='1=1'){
    $where[]=$scopeSql;
    array_push($params,...$scopeParams);
}

if($overdueOnly){
    $where[]="a.status='PROGRAMADA'";
    $where[]='a.scheduled_end_at < ?';
    $params[]=$before.' 00:00:00';
}else{
    $where[]='a.scheduled_start_at < DATE_ADD(?,INTERVAL 1 DAY)';
    $params[]=(string)$filters['to'];
    $where[]='a.scheduled_end_at >= ?';
    $params[]=(string)$filters['from'].' 00:00:00';
}
```

Resolve effective states exactly:

```php
$history=!empty($filters['history']);
$status=(string)($filters['status']??'active');
$allowed=$history?self::ALL_STATUSES:self::ACTIVE_STATUSES;
$states=$status==='all'&&$history
    ?self::ALL_STATUSES
    :($status==='active'||!in_array($status,$allowed,true)
        ?self::ACTIVE_STATUSES
        :[$status]);
```

If `scope_mode=mine` and role is `TECHNICIAN`, append `a.responsible_user_id=?` using authenticated user id. If scope mode is `all`, do not add that constraint. A specific `responsible_user_id`, `park_id`, or `activity_type` may narrow results only after scope is applied.

The main SELECT must be one query and avoid N+1:

```sql
SELECT
    a.id activity_id,
    a.ticket_id,
    t.ticket_number ticket_code,
    t.subject ticket_subject,
    a.activity_type,
    a.status,
    a.scheduled_start_at,
    a.scheduled_end_at,
    a.responsible_user_id,
    u.full_name responsible_name,
    a.park_id,
    p.name park_name
FROM ticket_activities a
JOIN tickets t ON t.id=a.ticket_id
JOIN users u ON u.id=a.responsible_user_id
LEFT JOIN parks p ON p.id=a.park_id
```

After fetch:

```php
$now=time();
foreach($rows as &$row){
    $row['is_overdue']=(string)$row['status']==='PROGRAMADA'
        && strtotime((string)$row['scheduled_end_at'])<$now;
    $row['ticket_url']=APP_BASE_URL.'/tickets/view?id='.(int)$row['ticket_id'].'#actividades';
}
unset($row);
return self::markConflicts($rows);
```

- [ ] **Step 4: Implement scoped filterOptions()**

Use subqueries/joins based on the same ticket scope, not unrestricted global catalogs. Return:

```php
[
    'responsibles'=>[['id'=>1,'full_name'=>'...'], ...],
    'parks'=>[['id'=>1,'name'=>'...'], ...],
]
```

Only internal active `ADMIN`, `SEMIADMIN`, `TECHNICIAN` users who actually appear in scoped activities/tickets should be exposed as responsible options. Only scoped parks should be exposed.

- [ ] **Step 5: Implement searchTickets() with scope and limit**

Normalize query:

```php
$query=trim($query);
if(mb_strlen($query)<2)return[];
$limit=max(1,min(10,$limit));
```

Search visible tickets by `ticket_number`, `subject`, and optionally park name. Return at most 10 rows:

```php
[
  'id'=>(int)$row['id'],
  'ticket_number'=>(string)$row['ticket_number'],
  'subject'=>(string)$row['subject'],
  'park_name'=>(string)($row['park_name']??''),
  'ticket_url'=>APP_BASE_URL.'/tickets/view?id='.(int)$row['id'].'#actividades',
]
```

- [ ] **Step 6: Run service regression and Fase 5 service regression**

```bat
C:\xampp\php\php.exe -l app\Services\AgendaService.php
C:\xampp\php\php.exe tests\phase6_agenda_service_regression.php
C:\xampp\php\php.exe tests\phase5_activities_service_regression.php
```

Expected: all PASS.

- [ ] **Step 7: Commit**

```bat
git add app\Services\AgendaService.php tests\phase6_agenda_service_regression.php
git commit -m "feat: consultar agenda con alcance y filtros"
```

---

### Task 3: AgendaController, acceso 403, normalización GET y ruta

**Files:**
- Create: `app/Controllers/AgendaController.php`
- Modify: `public/index.php`
- Create: `tests/phase6_agenda_ui_regression.php`

**Interfaces:**
- Consumes: `AgendaService`, `Auth`, `View`.
- Produces: `GET /agenda`; ViewModel con `filters`, `activities`, `overdue`, `options`, `ticketMatches`, `hourWindow`, `canProgram`, `scopeLabel`, `notice`.

- [ ] **Step 1: Write failing route/controller/access regression**

Create `tests/phase6_agenda_ui_regression.php` with checks:

```php
<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$router=file_get_contents($root.'/public/index.php');
$controllerPath=$root.'/app/Controllers/AgendaController.php';
$controller=is_file($controllerPath)?file_get_contents($controllerPath):'';
$errors=0;
function ok(bool $c,string $m):void{global $errors;echo($c?'[OK] ':'[FALLO] ').$m.PHP_EOL;if(!$c)$errors++;}

ok(str_contains($router,'AgendaController'),'Router conoce AgendaController');
ok(str_contains($router,"['GET','/agenda'"),'Existe GET /agenda');
ok($controller!=='','Existe AgendaController');
ok(str_contains($controller,"['ADMIN','SEMIADMIN','TECHNICIAN','MANAGEMENT','SUPERVISOR']"),'Agenda limita roles internos permitidos');
ok(str_contains($controller,'http_response_code(403)'),'Agenda responde 403 a perfil no autorizado');
ok(str_contains($controller,"'mine'"),'Controller contempla Mis actividades');
ok(str_contains($controller,'90'),'Controller limita rango a 90 días');
ok(str_contains($controller,'AgendaService'),'Controller delega datos en AgendaService');
ok(!str_contains($controller,'SELECT '),'Controller no ejecuta SQL directo');

if($errors){fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);exit(1);}
echo '[OK] Contrato de ruta/controller Agenda completado.'.PHP_EOL;
```

- [ ] **Step 2: Run and verify RED**

```bat
C:\xampp\php\php.exe tests\phase6_agenda_ui_regression.php
```

Expected: FAIL because controller/route are absent.

- [ ] **Step 3: Implement AgendaController::index()**

Create `app/Controllers/AgendaController.php` with:

```php
<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth,View};
use App\Services\{AgendaService,ScopeService};
use DateTimeImmutable;

final class AgendaController
{
    private const ALLOWED_ROLES=['ADMIN','SEMIADMIN','TECHNICIAN','MANAGEMENT','SUPERVISOR'];

    public function index(): void
    {
        Auth::requireLogin();
        $user=Auth::user();
        if(($user['access_type']??'INTERNAL')==='EXTERNAL'||!in_array((string)Auth::role(),self::ALLOWED_ROLES,true)){
            http_response_code(403);
            View::render('errors/friendly',[
                'user'=>$user,
                'title'=>'Agenda no disponible',
                'message'=>'Esta sección está disponible para seguimiento interno autorizado.',
            ]);
            return;
        }

        [$filters,$notice]=$this->filters();
        $service=new AgendaService();
        $activities=$service->activities($filters);
        $overdue=$service->overdueBefore((string)$filters['from'],$filters);
        $options=$service->filterOptions($filters);
        $ticketMatches=!empty($filters['program'])
            ?$service->searchTickets((string)$filters['ticket_q'],10)
            :[];

        View::render('agenda/index',[
            'user'=>$user,
            'filters'=>$filters,
            'activities'=>$activities,
            'overdue'=>$overdue,
            'options'=>$options,
            'ticketMatches'=>$ticketMatches,
            'hourWindow'=>AgendaService::hourWindow($activities),
            'canProgram'=>in_array((string)Auth::role(),['ADMIN','SEMIADMIN','TECHNICIAN'],true),
            'scopeLabel'=>(new ScopeService())->scopeLabel(),
            'notice'=>$notice,
        ]);
    }
```

Implement private `filters()` so:

- `view` only `calendar|list`, default `calendar`.
- default calendar range = Monday through Sunday current week.
- default list range = today through +6 days.
- validate exact `Y-m-d` with `DateTimeImmutable::createFromFormat('!Y-m-d',$value)` and round-trip equality.
- if only one of `from/to` is valid, treat custom range as invalid and use default.
- if `from > to` or difference > 89 days, reset to default and set a non-technical notice.
- `history` is boolean from `1` only.
- `status` allowed: `active`, `all`, `PROGRAMADA`, `EN_CURSO`, `FINALIZADA`, `CANCELADA`.
- when `history=0`, historical status is normalized to `active`.
- `scope_mode`: technician defaults `mine`, others `all`; only technician may retain `mine`.
- IDs are positive ints else 0.
- `activity_type` only Fase 5 enum.
- `program=1` only remains true for operational roles.
- `ticket_q` trimmed and capped to 100 chars.

Close the class properly after private helpers.

- [ ] **Step 4: Register route in public/index.php**

Add `AgendaController` to the import list and add:

```php
['GET','/agenda',[AgendaController::class,'index']],
```

near dashboard/search routes.

- [ ] **Step 5: Run controller regression + lint**

```bat
C:\xampp\php\php.exe -l app\Controllers\AgendaController.php
C:\xampp\php\php.exe -l public\index.php
C:\xampp\php\php.exe tests\phase6_agenda_ui_regression.php
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bat
git add app\Controllers\AgendaController.php public\index.php tests\phase6_agenda_ui_regression.php
git commit -m "feat: agregar ruta y controlador de agenda"
```

---

### Task 4: Vista Lista, filtros y flujo server-rendered

**Files:**
- Create: `app/Views/agenda/index.php`
- Create: `public/assets/css/agenda.css`
- Modify: `tests/phase6_agenda_ui_regression.php`

**Interfaces:**
- Consumes ViewModel from Task 3.
- Produces server-rendered filters GET, list grouped by day, overdue block, history controls, ticket links.

- [ ] **Step 1: Add failing UI contract checks**

Require in `tests/phase6_agenda_ui_regression.php`:

```php
$viewPath=$root.'/app/Views/agenda/index.php';
$view=is_file($viewPath)?file_get_contents($viewPath):'';
$cssPath=$root.'/public/assets/css/agenda.css';
$css=is_file($cssPath)?file_get_contents($cssPath):'';

ok($view!=='','Existe vista Agenda');
ok(str_contains($view,'Agenda'),'Vista muestra título Agenda');
ok(str_contains($view,'Calendario'),'Vista ofrece Calendario');
ok(str_contains($view,'Lista'),'Vista ofrece Lista');
ok(str_contains($view,'name="responsible_user_id"'),'Existe filtro Responsable');
ok(str_contains($view,'name="park_id"'),'Existe filtro Parque');
ok(str_contains($view,'name="activity_type"'),'Existe filtro Tipo');
ok(str_contains($view,'name="status"'),'Existe filtro Estado');
ok(str_contains($view,'name="from"')&&str_contains($view,'name="to"'),'Existe filtro de rango');
ok(str_contains($view,'Pendientes atrasadas'),'Vista contempla atrasadas');
ok(str_contains($view,'#actividades'),'Vista enlaza actividades al ticket');
ok($css!=='','Existe CSS de Agenda');
```

- [ ] **Step 2: Run UI regression and verify RED**

```bat
C:\xampp\php\php.exe tests\phase6_agenda_ui_regression.php
```

Expected: FAIL for missing view/CSS.

- [ ] **Step 3: Build app/Views/agenda/index.php server-rendered**

At top:

```php
<?php
use App\Core\Auth;
$pageTitle='Agenda';
$pageSection='Agenda';
$activeNav='agenda';
$helpContext='agenda';
require APP_ROOT.'/app/Views/shared/app_start.php';

$typeLabels=[
    'VISITA_EN_SITIO'=>'Visita en sitio',
    'SOPORTE_REMOTO'=>'Soporte remoto',
    'SEGUIMIENTO'=>'Seguimiento',
    'INTERVENCION_PROVEEDOR'=>'Intervención de proveedor',
    'OTRA'=>'Otra atención',
];
$statusLabels=[
    'PROGRAMADA'=>'Programada',
    'EN_CURSO'=>'En curso',
    'FINALIZADA'=>'Finalizada',
    'CANCELADA'=>'Cancelada',
];
```

Use one GET form preserving `view`, `history`, `scope_mode` and rendering selects from scoped options. Add quick links built from current query state for:

- Hoy
- Esta semana
- Próximos 30 días
- Historial (`history=1&status=all`)

Render notice safely with `htmlspecialchars`.

Group list by `Y-m-d` in PHP:

```php
$byDay=[];
foreach($activities as $activity){
    $key=date('Y-m-d',strtotime((string)$activity['scheduled_start_at']));
    $byDay[$key][]=$activity;
}
```

Render `Pendientes atrasadas` before the day groups. Each activity link must use the service-provided `ticket_url`, and show time, type, ticket code, park, responsible, status, and conflict if true. Never print internal notes or results.

Finish with:

```php
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>
```

- [ ] **Step 4: Add base agenda.css for list/filter/state semantics**

Use existing CSS variables only. Required class contract:

```css
.agenda-shell{display:grid;gap:16px}
.agenda-toolbar{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px}
.agenda-view-switch{display:inline-flex;gap:6px}
.agenda-filters{display:grid;grid-template-columns:repeat(5,minmax(150px,1fr));gap:10px}
.agenda-overdue{border:1px solid var(--border);border-radius:14px;padding:14px;background:var(--surface-warning-soft,var(--surface-soft))}
.agenda-list{display:grid;gap:16px}
.agenda-day{display:grid;gap:8px}
.agenda-item{display:grid;grid-template-columns:minmax(88px,auto) minmax(0,1fr) auto;gap:12px;align-items:center;text-decoration:none}
.agenda-item.is-programada{border-left:4px solid var(--brand)}
.agenda-item.is-en-curso{border-left:4px solid var(--success,#2f8f5b)}
.agenda-item.is-overdue{border-left-color:var(--warning,#b7791f)}
.agenda-item.is-finalizada,.agenda-item.is-cancelada{opacity:.78}
```

Do not hard-code a separate dark palette; rely on existing variables and only add `html[data-theme="dark"]` overrides if a contrast check later proves necessary.

- [ ] **Step 5: Run lint/UI regression**

```bat
C:\xampp\php\php.exe -l app\Views\agenda\index.php
C:\xampp\php\php.exe tests\phase6_agenda_ui_regression.php
```

Expected: PASS for list/filter contract.

- [ ] **Step 6: Commit**

```bat
git add app\Views\agenda\index.php public\assets\css\agenda.css tests\phase6_agenda_ui_regression.php
git commit -m "feat: agregar vista lista y filtros de agenda"
```

---

### Task 5: Calendario semanal y representación por día responsive

**Files:**
- Modify: `app/Views/agenda/index.php`
- Modify: `public/assets/css/agenda.css`
- Create: `public/assets/js/agenda.js`
- Modify: `tests/phase6_agenda_ui_regression.php`

**Interfaces:**
- Consumes: same `$activities`, `$filters`, `$hourWindow` as list view.
- Produces: desktop/iPad-horizontal weekly calendar; day-card fallback at <=1023px; progressive view switch.

- [ ] **Step 1: Add failing calendar/responsive checks**

Require:

```php
ok(str_contains($view,'agenda-calendar'),'Vista contiene calendario semanal');
ok(str_contains($view,'agenda-calendar-days'),'Calendario define siete días');
ok(str_contains($view,'Semana anterior'),'Existe navegación semana anterior');
ok(str_contains($view,'Semana siguiente'),'Existe navegación semana siguiente');
ok(str_contains($view,'Hoy'),'Existe navegación Hoy');
ok(str_contains($view,'Conflicto de horario'),'Calendario contempla conflictos');
ok(str_contains($css,'@media (max-width:1023px)'),'Agenda adapta iPad vertical');
ok(str_contains($css,'@media (max-width:760px)'),'Agenda adapta móvil');
ok(str_contains($css,'.agenda-calendar-grid'),'CSS define cuadrícula horaria');
```

- [ ] **Step 2: Run UI regression and verify RED**

```bat
C:\xampp\php\php.exe tests\phase6_agenda_ui_regression.php
```

- [ ] **Step 3: Render week navigation and seven-day calendar**

In the view, compute 7 day keys from `$filters['from']` only when `view=calendar`. Add links that preserve all filters but shift `from/to` by ±7 days. `Hoy` resets to current week.

Build day buckets once; do not query again.

For the hourly calendar, render a semantic grid whose rows are calculated from `$hourWindow['start_hour']..$hourWindow['end_hour']`. Use 30-minute slots for layout. Compute each activity's start slot and span in PHP from its timestamps and clamp to visible bounds. Pass placement via data attributes:

```html
<a class="agenda-event is-programada"
   data-start-slot="4"
   data-span-slots="2"
   href="...#actividades">...</a>
```

Do not put security-sensitive data in attributes. `agenda.js` may set CSS custom properties from these numeric attributes for visual placement; without JS, the same events must remain readable in day-card fallback/list.

- [ ] **Step 4: Add agenda.js progressive positioning/view behavior**

Create `public/assets/js/agenda.js`:

```js
(() => {
  document.querySelectorAll('.agenda-event[data-start-slot][data-span-slots]').forEach((event) => {
    const start = Number.parseInt(event.dataset.startSlot || '0', 10);
    const span = Math.max(1, Number.parseInt(event.dataset.spanSlots || '1', 10));
    if (Number.isFinite(start)) event.style.setProperty('--agenda-start-slot', String(start));
    event.style.setProperty('--agenda-span-slots', String(span));
  });

  const programToggle = document.querySelector('[data-agenda-program-toggle]');
  const programPanel = document.querySelector('[data-agenda-program-panel]');
  if (programToggle && programPanel) {
    programToggle.addEventListener('click', () => {
      const hidden = programPanel.hasAttribute('hidden');
      programPanel.toggleAttribute('hidden', !hidden);
      programToggle.setAttribute('aria-expanded', hidden ? 'true' : 'false');
    });
  }
})();
```

This JS must not filter records or decide access.

- [ ] **Step 5: Implement calendar CSS and responsive fallback**

Desktop contract:

```css
.agenda-calendar{display:grid;gap:8px}
.agenda-calendar-days{display:grid;grid-template-columns:72px repeat(7,minmax(0,1fr));gap:1px}
.agenda-calendar-grid{display:grid;grid-template-columns:72px repeat(7,minmax(0,1fr));position:relative}
.agenda-event{grid-row:calc(var(--agenda-start-slot) + 1)/span var(--agenda-span-slots);min-width:0}
```

Use dedicated day columns/slot containers so simultaneous events do not overflow outside their day. At `max-width:1023px`, hide the hourly grid and show `.agenda-day-cards`. At `max-width:760px`, filters become one column, toolbar stacks, and cards remain full width. There must be no mandatory horizontal scrolling.

- [ ] **Step 6: Run syntax/regression**

```bat
C:\xampp\php\php.exe -l app\Views\agenda\index.php
C:\xampp\php\php.exe tests\phase6_agenda_ui_regression.php
```

If Node is unavailable on PC TEST, do not block local testing on `node --check`; CI/static source gate will verify the small JS file. If Node is available:

```bat
node --check public\assets\js\agenda.js
```

- [ ] **Step 7: Commit**

```bat
git add app\Views\agenda\index.php public\assets\css\agenda.css public\assets\js\agenda.js tests\phase6_agenda_ui_regression.php
git commit -m "feat: agregar calendario semanal responsive"
```

---

### Task 6: Navegación por perfil, assets condicionales y Programar actividad

**Files:**
- Modify: `app/Views/shared/app_start.php`
- Modify: `app/Views/shared/app_end.php`
- Modify: `app/Views/agenda/index.php`
- Modify: `tests/phase6_agenda_ui_regression.php`

**Interfaces:**
- Consumes: `activeNav='agenda'`, `canProgram`, `ticketMatches`, GET `program`, `ticket_q`.
- Produces: nav Agenda for allowed internal profiles; no nav for requester/external; scoped ticket picker for operational roles.

- [ ] **Step 1: Add failing navigation/program regression**

Add checks against `app_start.php`, `app_end.php`, and Agenda view:

```php
$start=file_get_contents($root.'/app/Views/shared/app_start.php');
$end=file_get_contents($root.'/app/Views/shared/app_end.php');
ok(str_contains($start,"activeNav==='agenda'"),'Shell conoce navegación Agenda');
ok(str_contains($start,"/agenda"),'Shell enlaza Agenda');
ok(str_contains($start,'agenda.css'),'Shell carga CSS de Agenda');
ok(str_contains($end,'agenda.js'),'Shell carga JS de Agenda');
ok(str_contains($view,'Programar actividad'),'Vista contempla Programar actividad');
ok(str_contains($view,'ticket_q'),'Programar actividad busca ticket visible');
ok(str_contains($view,'ticketMatches'),'Vista consume resultados scoped de tickets');
```

- [ ] **Step 2: Run regression and verify RED**

```bat
C:\xampp\php\php.exe tests\phase6_agenda_ui_regression.php
```

- [ ] **Step 3: Add nav link with explicit profile rules**

In `app_start.php`, derive:

```php
$canAgenda=!$isExternal&&in_array((string)Auth::role(),['ADMIN','SEMIADMIN','TECHNICIAN','MANAGEMENT','SUPERVISOR'],true);
```

For support operators, add Agenda in Soporte after Disponibles. For management viewers, add Agenda in Gestión. Do not add it in requester/external branches.

Load `agenda.css` only when `$activeNav==='agenda'`:

```php
<?php if($activeNav==='agenda'): ?>
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/agenda.css?v=20260914-AGENDA1">
<?php endif; ?>
```

- [ ] **Step 4: Load agenda.js conditionally in app_end.php**

Follow existing asset patterns and add:

```php
<?php if(($activeNav??'')==='agenda'): ?>
<script src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/js/agenda.js?v=20260914-AGENDA1" defer></script>
<?php endif; ?>
```

Do not add inline event handlers.

- [ ] **Step 5: Finish Programar actividad panel**

Only render when `$canProgram` is true. The control must link or submit GET to `/agenda?program=1`; the panel contains:

```html
<form method="get" action="<?= APP_BASE_URL ?>/agenda" class="agenda-program-search">
  <input type="hidden" name="program" value="1">
  <input type="hidden" name="view" value="<?= htmlspecialchars((string)$filters['view']) ?>">
  <label>Buscar ticket
    <input class="form-control" type="search" name="ticket_q" maxlength="100" value="<?= htmlspecialchars((string)$filters['ticket_q']) ?>" placeholder="Código o asunto">
  </label>
  <button class="btn btn-primary" type="submit">Buscar</button>
</form>
```

Render max 10 `$ticketMatches`; each result links to its service-provided `ticket_url`. For MANAGEMENT/SUPERVISOR the panel/button must not render.

- [ ] **Step 6: Run regressions/lint**

```bat
C:\xampp\php\php.exe -l app\Views\shared\app_start.php
C:\xampp\php\php.exe -l app\Views\shared\app_end.php
C:\xampp\php\php.exe -l app\Views\agenda\index.php
C:\xampp\php\php.exe tests\phase6_agenda_ui_regression.php
C:\xampp\php\php.exe tests\phase5_activities_ui_regression.php
```

- [ ] **Step 7: Commit**

```bat
git add app\Views\shared\app_start.php app\Views\shared\app_end.php app\Views\agenda\index.php tests\phase6_agenda_ui_regression.php
git commit -m "feat: integrar agenda en navegación y tickets"
```

---

### Task 7: Manual, CI, README, CHANGELOG y roadmap

**Files:**
- Modify: `app/Views/help/manual.php`
- Modify: `.github/workflows/helpdesk-ci.yml`
- Modify: `README.md`
- Modify: `CHANGELOG.md`
- Modify: `docs/superpowers/plans/2026-09-10-helpdesk-functional-maturation-implementation.md`
- Modify: `tests/phase6_agenda_ui_regression.php`

**Interfaces:**
- Produces documentation canónica y CI que falla si Agenda se rompe.

- [ ] **Step 1: Add documentation/CI gates to UI regression**

Require:

```php
$manual=file_get_contents($root.'/app/Views/help/manual.php');
$readme=file_get_contents($root.'/README.md');
$changelog=file_get_contents($root.'/CHANGELOG.md');
$roadmap=file_get_contents($root.'/docs/superpowers/plans/2026-09-10-helpdesk-functional-maturation-implementation.md');
$ci=file_get_contents($root.'/.github/workflows/helpdesk-ci.yml');
ok(str_contains($manual,'Agenda'),'Manual documenta Agenda');
ok(str_contains($manual,'Calendario')&&str_contains($manual,'Lista'),'Manual explica ambas vistas');
ok(str_contains($readme,'Agenda'),'README registra Fase 6');
ok(str_contains($changelog,'Agenda'),'CHANGELOG registra Fase 6');
ok(str_contains($roadmap,'Fase 6')&&str_contains($roadmap,'IMPLEMENTADA'),'Roadmap marca Fase 6 implementada');
ok(str_contains($ci,'phase6_agenda_service_regression.php'),'CI ejecuta regresión de servicio Agenda');
ok(str_contains($ci,'phase6_agenda_ui_regression.php'),'CI ejecuta regresión UI Agenda');
```

- [ ] **Step 2: Run UI regression and verify RED**

```bat
C:\xampp\php\php.exe tests\phase6_agenda_ui_regression.php
```

- [ ] **Step 3: Update integrated manual**

Document according to profile:

- Técnico: Mis actividades, Todo mi alcance, filtros, atrasadas/conflictos, abrir ticket, programar desde ticket.
- Admin/Semiadmin: vista de alcance visible y filtros.
- Gerencia/Supervisión: consulta solamente; no operar.
- Do not expose Agenda instructions to requester/external sections if manual already segments by profile.

Use language visible in UI: `Agenda`, `Calendario`, `Lista`, `Pendientes atrasadas`, `Conflicto de horario`, `Programar actividad`.

- [ ] **Step 4: Add Phase 6 tests to CI**

In `.github/workflows/helpdesk-ci.yml`, after Fase 5 regressions add:

```yaml
- name: Phase 6 agenda service regression
  run: php tests/phase6_agenda_service_regression.php

- name: Phase 6 agenda UI regression
  run: php tests/phase6_agenda_ui_regression.php
```

Preserve existing PHP setup and all prior steps.

- [ ] **Step 5: Update README, CHANGELOG, roadmap**

README roadmap row:

```markdown
| 6 | Agenda | **Implementada — pendiente validación integral Fase 12** |
```

Add a Fase 6 section stating explicitly:

- no new calendar table;
- Calendario + Lista;
- scope backend;
- active/history states;
- overdue/conflicts derived;
- ticket remains operational workspace.

CHANGELOG entry must list exact user-visible changes and state **BD: sin cambios**.

Roadmap file must mark Fase 6 checklist items complete and Fase 7 as next phase.

- [ ] **Step 6: Run regression**

```bat
C:\xampp\php\php.exe tests\phase6_agenda_ui_regression.php
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bat
git add app\Views\help\manual.php .github\workflows\helpdesk-ci.yml README.md CHANGELOG.md docs\superpowers\plans\2026-09-10-helpdesk-functional-maturation-implementation.md tests\phase6_agenda_ui_regression.php
git commit -m "docs: cerrar documentación funcional fase 6"
```

---

### Task 8: Quality gates acumulados y validación manual PC TEST

**Files:**
- No functional files unless a discovered regression requires an approved fix.
- Possible test-only adjustments must be committed separately and explained.

**Interfaces:**
- Produces evidence that Fase 6 and Fase 5 remain green.

- [ ] **Step 1: Pull branch and verify clean tree**

```bat
cd /d C:\xampp\htdocs\HelpdeskCarrousel
git status
git pull --ff-only origin fase6-agenda
git status
```

Expected: clean working tree before validation.

- [ ] **Step 2: Run PHP syntax checks on Phase 6 files**

```bat
C:\xampp\php\php.exe -l app\Services\AgendaService.php
C:\xampp\php\php.exe -l app\Controllers\AgendaController.php
C:\xampp\php\php.exe -l app\Views\agenda\index.php
C:\xampp\php\php.exe -l app\Views\shared\app_start.php
C:\xampp\php\php.exe -l app\Views\shared\app_end.php
```

Expected: no syntax errors.

- [ ] **Step 3: Run Fase 6 regressions**

```bat
C:\xampp\php\php.exe tests\phase6_agenda_service_regression.php
C:\xampp\php\php.exe tests\phase6_agenda_ui_regression.php
```

Expected: all `[OK]`.

- [ ] **Step 4: Re-run critical Fase 5 regressions**

```bat
C:\xampp\php\php.exe tests\phase5_activities_schema_regression.php
C:\xampp\php\php.exe tests\phase5_activities_service_regression.php
C:\xampp\php\php.exe tests\phase5_activities_ui_regression.php
C:\xampp\php\php.exe tests\requester_return_visibility_smoke.php
```

Expected: all PASS; Agenda must not alter Fase 5 behavior.

- [ ] **Step 5: Run base quality gates**

```bat
C:\xampp\php\php.exe tests\static_checks.php
C:\xampp\php\php.exe tests\project_quality.php
C:\xampp\php\php.exe tests\xlsx_smoke.php
```

Expected: all PASS.

- [ ] **Step 6: Verify no DB changes**

```bat
git --no-pager diff origin/main...HEAD -- database
```

Expected: no Fase 6 database diff. Existing Fase 5 DB changes may be in main already; this compare from updated main should be empty for `database/`.

- [ ] **Step 7: Manual role/access validation**

Use real PC TEST users or controlled test profiles and verify:

```text
ADMIN       -> /agenda allowed, all scope, Programar actividad visible
SEMIADMIN   -> /agenda allowed, Programar actividad visible
TECHNICIAN  -> defaults to Mis actividades; Todo mi alcance works
MANAGEMENT  -> /agenda allowed; no Programar actividad or operational actions
SUPERVISOR  -> only assigned scope; no Programar activity
REQUESTER   -> HTTP 403 friendly view
EXTERNAL    -> HTTP 403 friendly view and no sidebar link
```

- [ ] **Step 8: Manual data behavior validation**

Create/use test activities from Fase 5 and verify:

```text
PROGRAMADA          visible by default
EN_CURSO            visible by default
FINALIZADA          hidden by default, visible in Historial
CANCELADA           hidden by default, visible in Historial
PROGRAMADA overdue  shown in Pendientes atrasadas
same-tech overlap   both show Conflicto de horario
boundary-touch       no conflict
different tech      no conflict
activity click       opens ticket #actividades
```

- [ ] **Step 9: Manual visual matrix**

Validate both light and dark where applicable:

```text
1920x1080       hourly Calendar + List
1366x768        hourly Calendar + filters usable
iPad horizontal hourly Calendar, compact cards
iPad vertical   day cards, no compressed 7-column grid
mobile <=760    one column, no mandatory horizontal scroll
```

Capture screenshots for at least desktop Calendar, desktop List, iPad vertical/day cards, mobile, and one dark-theme view.

- [ ] **Step 10: Diff hygiene**

```bat
git --no-pager diff --check origin/main...HEAD
git --no-pager diff --stat origin/main...HEAD
git status
```

Expected: no whitespace errors and clean working tree.

- [ ] **Step 11: Final commit only if validation required test/docs corrections**

If and only if validation generated legitimate tracked changes, rerun the relevant failing gate, then commit only those files with a narrow message. Otherwise do not create an empty commit.

---

## Final Acceptance Checklist

Before calling Fase 6 complete, confirm every item:

- [ ] `/agenda` exists as GET and is read-only.
- [ ] No new database tables, columns, migrations, or permissions.
- [ ] Agenda uses `ticket_activities` + scoped `tickets`.
- [ ] TECHNICIAN defaults to `mine` and can switch to `all` within scope.
- [ ] ADMIN/SEMIADMIN can consult visible global scope.
- [ ] MANAGEMENT/SUPERVISOR are consultation-only.
- [ ] REQUESTER/EXTERNAL receive HTTP 403 and have no nav link.
- [ ] Active default contains only PROGRAMADA + EN_CURSO.
- [ ] Historial explicitly exposes FINALIZADA + CANCELADA.
- [ ] Overdue PROGRAMADA activities before range remain visible.
- [ ] Same-responsible overlaps are flagged; touching endpoints are not.
- [ ] Calendar and List render the same filtered collection.
- [ ] Every activity opens `/tickets/view?id={id}#actividades`.
- [ ] Programar actividad searches only visible tickets and does not duplicate Fase 5 form.
- [ ] 1920, 1366, iPad horizontal, iPad vertical and mobile are usable.
- [ ] Light/dark remain legible.
- [ ] Fase 5 regressions remain green.
- [ ] Static checks, project quality and XLSX smoke remain green.
- [ ] README, Manual, CHANGELOG, roadmap and CI are updated.
- [ ] Branch remains `fase6-agenda`; do not merge to `main` until PC TEST approval.
