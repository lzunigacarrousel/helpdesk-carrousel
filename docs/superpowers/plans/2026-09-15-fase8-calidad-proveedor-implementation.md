# Fase 8 — Calidad IT → proveedor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Incorporar una valoración interna 1–5 de IT hacia cada ciclo finalizado de participación de proveedor, con correcciones inmutables, captura desde el ticket, explotación en Informe de proveedores/XLSX y cero cambios estructurales de BD.

**Architecture:** Reutilizar `ticket_events` como fuente de verdad. Fase 7 conserva la reconstrucción de ciclos y se amplía únicamente para exponer la identidad exacta del ciclo (`grant_event_id`/`revoke_event_id`); un nuevo `ProviderRatingService` concentra reglas, lectura, escritura y agregados de valoración. Un `ProviderRatingController` expone dos POST internos y el ticket interno + Informe de proveedores consumen el mismo dataset enriquecido.

**Tech Stack:** PHP 8+, MariaDB/MySQL vía PDO, arquitectura MVC actual, `ticket_events`, `ScopeService`, `Audit`, CSRF existente, XLSX con `XlsxExportService`, pruebas PHP CLI estilo regresión del repositorio.

**Spec:** `docs/superpowers/specs/2026-09-15-fase8-calidad-proveedor-design.md`

## Global Constraints

- Base activa: `carrousel_helpdesk`.
- Base histórica `helpdesk_carrousel`: protegida; no tocar.
- **0 cambios estructurales de BD**: no crear tablas, columnas, índices ni migraciones en Fase 8.
- Fuente funcional de valoración: `ticket_events`.
- Eventos funcionales: `PROVIDER_RATED` y `PROVIDER_RATING_CORRECTED`.
- Escala: `1 Muy deficiente`, `2 Deficiente`, `3 Adecuado`, `4 Bueno`, `5 Excelente`.
- Comentario obligatorio en score 1–2 y en toda corrección; opcional en 3–5 para primera valoración.
- Solo `ADMIN`, `SEMIADMIN`, `TECHNICIAN` con scope válido pueden evaluar/corregir.
- Solo ciclos cerrados mediante `EXTERNAL_REVOKED` son evaluables.
- Evaluación opcional; nunca bloquea revocación, resolución ni cierre.
- El proveedor y el solicitante no ven score ni comentario.
- Una corrección crea otro evento; nunca UPDATE/DELETE de una valoración previa.
- La última valoración/corrección válida del ciclo es la vigente.
- `Sin evaluar` no equivale a 0 y no participa en promedios.
- No crear permiso nuevo; autorización por rol + `ScopeService::userCanAccessTicket()`.
- Mantener regresiones de Fase 7 verdes.

---

## File Structure

### Nuevos archivos

- `app/Services/ProviderRatingService.php` — reglas de dominio, lectura de eventos de valoración, escritura inmutable y agregados.
- `app/Controllers/ProviderRatingController.php` — endpoints POST para primera valoración y corrección.
- `tests/phase8_provider_cycle_identity_regression.php` — identidad estable del ciclo Fase 7.
- `tests/phase8_provider_rating_service_regression.php` — reglas puras de score, corrección y última valoración vigente.
- `tests/phase8_provider_rating_controller_regression.php` — rutas, autorización, scope, CSRF y escritura por eventos.
- `tests/phase8_provider_rating_ui_regression.php` — captura solo interna y solo para ciclos finalizados.
- `tests/phase8_provider_rating_report_regression.php` — filtros, agregados y XLSX.
- `tests/phase8_provider_rating_closeout_regression.php` — CI, README, CHANGELOG, Manual y cero cambios de BD.

### Archivos a modificar

- `app/Services/ProviderParticipationService.php` — exponer `grant_event_id`, `revoke_event_id` y `rowsForTicket()` sin cambiar la semántica de Fase 7.
- `app/Controllers/TicketController.php` — cargar ciclos del ticket enriquecidos con valoración para la vista interna.
- `app/Views/tickets/show.php` — bloque interno de calidad del proveedor.
- `public/index.php` — registrar los dos POST de valoración.
- `app/Controllers/ExternalReportController.php` — enriquecer filas, normalizar filtro `rating`, pasar agregados y exportarlos.
- `app/Views/management/external_report.php` — mostrar valoración vigente, filtro y resumen por proveedor.
- `.github/workflows/helpdesk-ci.yml` — ejecutar regresiones de Fase 8.
- `README.md`, `CHANGELOG.md`, `app/Views/help/manual.php` — cierre documental.

---

### Task 1: Preservar identidad exacta del ciclo de proveedor

**Files:**
- Create: `tests/phase8_provider_cycle_identity_regression.php`
- Modify: `app/Services/ProviderParticipationService.php`

**Interfaces:**
- Consumes: eventos `EXTERNAL_GRANTED` / `EXTERNAL_REVOKED` que ya usa `ProviderParticipationService::buildCycles()`.
- Produces: cada row de ciclo incluye `grant_event_id:int`, `revoke_event_id:?int`; nuevo `rowsForTicket(int $ticketId, ?int $nowTs = null): array`.

- [ ] **Step 1: Escribir test RED de identidad de ciclo**

Crear `tests/phase8_provider_cycle_identity_regression.php` con fixtures de dos grants del mismo proveedor/ticket y verificar:

```php
$rows=\App\Services\ProviderParticipationService::buildCycles($users,$events,[],[],[],strtotime('2026-09-05 08:00:00'));
ok((int)($rows[0]['grant_event_id']??0)===101,'Primer ciclo conserva grant_event_id');
ok((int)($rows[0]['revoke_event_id']??0)===102,'Primer ciclo conserva revoke_event_id');
ok((int)($rows[1]['grant_event_id']??0)===103,'Segundo ciclo usa otro grant_event_id');
ok(array_key_exists('revoke_event_id',$rows[1])&&$rows[1]['revoke_event_id']===null,'Ciclo activo no inventa revoke_event_id');
```

También validar por lectura del archivo:

```php
ok(str_contains($body,'function rowsForTicket('),'Expone rowsForTicket');
```

- [ ] **Step 2: Ejecutar RED**

Run:

```bat
C:\xampp\php\php.exe tests\phase8_provider_cycle_identity_regression.php
```

Expected: FAIL en `grant_event_id`, `revoke_event_id` y `rowsForTicket`.

- [ ] **Step 3: Implementar identidad mínima en `ProviderParticipationService`**

Al abrir un ciclo dentro de `buildCycles()` agregar:

```php
'grant_event_id'=>(int)($event['id']??0),
'revoke_event_id'=>null,
```

Al cerrar por `EXTERNAL_REVOKED`:

```php
$rows[$i]['revoke_event_id']=(int)($event['id']??0);
```

Al cierre implícito por nuevo grant, mantener `revoke_event_id=null` porque no existió evento `EXTERNAL_REVOKED` real. Ese ciclo **no será evaluable** en Fase 8.

Agregar:

```php
public function rowsForTicket(int $ticketId, ?int $nowTs = null): array
{
    if($ticketId<=0)return [];
    return array_values(array_filter(
        $this->rows($nowTs),
        static fn(array $row):bool=>(int)($row['ticket_id']??0)===$ticketId
    ));
}
```

- [ ] **Step 4: Ejecutar GREEN y regresión Fase 7**

```bat
C:\xampp\php\php.exe tests\phase8_provider_cycle_identity_regression.php
C:\xampp\php\php.exe tests\phase7_provider_participation_regression.php
C:\xampp\php\php.exe tests\phase7_provider_activity_regression.php
C:\xampp\php\php.exe tests\phase7_provider_returns_regression.php
```

Expected: todo `[OK]`.

- [ ] **Step 5: Commit**

```bat
git add app\Services\ProviderParticipationService.php tests\phase8_provider_cycle_identity_regression.php
git commit -m "feat: identificar ciclos de proveedor por evento"
```

---

### Task 2: Implementar dominio inmutable de valoración

**Files:**
- Create: `app/Services/ProviderRatingService.php`
- Create: `tests/phase8_provider_rating_service_regression.php`

**Interfaces:**
- Consumes: rows de Fase 7 con `grant_event_id`, `revoke_event_id`, `user_id`, `ticket_id`.
- Produces:
  - `ProviderRatingService::SCORE_LABELS`
  - `ProviderRatingService::validateInput(int $score, string $comment, bool $correction): void`
  - `ProviderRatingService::buildCurrentRatings(array $events): array`
  - `ProviderRatingService::enrichCycles(array $cycles, array $ratingEvents): array`
  - `ProviderRatingService::providerSummary(array $rows): array`
  - `ProviderRatingService::ratingOptions(): array`

- [ ] **Step 1: Crear test RED de reglas puras**

El test debe construir eventos de rating sintéticos:

```php
$ratingEvents=[
    ['id'=>201,'ticket_id'=>100,'event_type'=>'PROVIDER_RATED','actor_user_id'=>1,'actor_name'=>'Tecnico A','metadata_json'=>j([
        'external_user_id'=>10,'grant_event_id'=>101,'score'=>2,'comment'=>'Respuesta incompleta',
    ]),'created_at'=>'2026-09-03 09:00:00'],
    ['id'=>202,'ticket_id'=>100,'event_type'=>'PROVIDER_RATING_CORRECTED','actor_user_id'=>2,'actor_name'=>'Tecnico B','metadata_json'=>j([
        'external_user_id'=>10,'grant_event_id'=>101,'score'=>4,'comment'=>'Se corrigió tras revisar evidencia','corrected_rating_event_id'=>201,
    ]),'created_at'=>'2026-09-03 10:00:00'],
];
```

Verificar:

```php
$current=ProviderRatingService::buildCurrentRatings($ratingEvents);
ok((int)$current[101]['score']===4,'Última corrección es vigente');
ok((int)$current[101]['event_id']===202,'Conserva id de evento vigente');
ok((int)$current[101]['revision_count']===1,'Cuenta una corrección');
```

Validar `validateInput()` con `try/catch`:

```php
ProviderRatingService::validateInput(5,'',false); // válido
ProviderRatingService::validateInput(3,'',false); // válido
```

Y casos que deben lanzar `InvalidArgumentException`: score 0, score 6, score 1 sin comentario, score 2 sin comentario, cualquier corrección sin comentario.

- [ ] **Step 2: Ejecutar RED**

```bat
C:\xampp\php\php.exe tests\phase8_provider_rating_service_regression.php
```

Expected: FAIL porque `ProviderRatingService` no existe.

- [ ] **Step 3: Implementar `ProviderRatingService` puro**

Crear:

```php
final class ProviderRatingService
{
    public const SCORE_LABELS=[
        1=>'Muy deficiente',
        2=>'Deficiente',
        3=>'Adecuado',
        4=>'Bueno',
        5=>'Excelente',
    ];

    public function __construct(private ?object $pdo=null){}

    public static function validateInput(int $score,string $comment,bool $correction): void
    {
        if($score<1||$score>5)throw new \InvalidArgumentException('Selecciona una valoración entre 1 y 5.');
        $comment=trim($comment);
        if(($score<=2||$correction)&&$comment===''){
            throw new \InvalidArgumentException($correction?'Explica el motivo de la corrección.':'Agrega un comentario para una valoración de 1 o 2 estrellas.');
        }
    }
}
```

`buildCurrentRatings()` debe ignorar eventos cuyo JSON no sea válido, score no esté 1..5, `grant_event_id<=0` o `external_user_id<=0`. Ordenar por `created_at`, luego `id`; solo aceptar `PROVIDER_RATED` y `PROVIDER_RATING_CORRECTED`. Para una corrección válida, `corrected_rating_event_id` debe referir al evento vigente previo del mismo `grant_event_id`; si no coincide, ignorar esa corrección como corrupta.

Cada valoración vigente debe exponer:

```php
[
  'event_id'=>202,
  'score'=>4,
  'label'=>'Bueno',
  'comment'=>'...',
  'rated_at'=>'2026-09-03 10:00:00',
  'actor_user_id'=>2,
  'actor_name'=>'Tecnico B',
  'external_user_id'=>10,
  'grant_event_id'=>101,
  'revision_count'=>1,
]
```

`enrichCycles()` agrega a cada row:

```php
'provider_rating_score'=>null|int,
'provider_rating_label'=>'Sin evaluar'|string,
'provider_rating_comment'=>null|string,
'provider_rating_at'=>null|string,
'provider_rating_actor'=>null|string,
'provider_rating_event_id'=>null|int,
'provider_rating_revisions'=>int,
```

`providerSummary()` agrupa por `user_id` y devuelve por proveedor:

```php
[
  10=>[
    'user_id'=>10,
    'organization'=>'Proveedor Uno, S.A.',
    'rated_cycles'=>2,
    'unrated_cycles'=>1,
    'average_score'=>4.5,
  ],
]
```

Redondear promedio a 2 decimales. `Sin evaluar` no entra al promedio.

`ratingOptions()` devuelve:

```php
['UNRATED'=>'Sin evaluar','1'=>'1★ Muy deficiente','2'=>'2★ Deficiente','3'=>'3★ Adecuado','4'=>'4★ Bueno','5'=>'5★ Excelente'];
```

- [ ] **Step 4: Ejecutar GREEN**

```bat
C:\xampp\php\php.exe -l app\Services\ProviderRatingService.php
C:\xampp\php\php.exe tests\phase8_provider_rating_service_regression.php
```

Expected: todo `[OK]`.

- [ ] **Step 5: Commit**

```bat
git add app\Services\ProviderRatingService.php tests\phase8_provider_rating_service_regression.php
git commit -m "feat: modelar valoracion inmutable de proveedores"
```

---

### Task 3: Persistir valoraciones y correcciones con autorización backend

**Files:**
- Modify: `app/Services/ProviderRatingService.php`
- Create: `app/Controllers/ProviderRatingController.php`
- Modify: `public/index.php`
- Create: `tests/phase8_provider_rating_controller_regression.php`

**Interfaces:**
- Consumes: `ScopeService::userCanAccessTicket(int $userId, int $ticketId): bool`, `ProviderParticipationService::rowsForTicket()`.
- Produces:
  - `ProviderRatingService::ratingEventsForTickets(array $ticketIds): array`
  - `ProviderRatingService::enrichRows(array $rows): array`
  - `ProviderRatingService::rateCycle(int $ticketId,int $externalUserId,int $grantEventId,int $score,string $comment,int $actorUserId): int`
  - `ProviderRatingService::correctCycle(int $ticketId,int $externalUserId,int $grantEventId,int $score,string $comment,int $correctedRatingEventId,int $actorUserId): int`
  - `ProviderRatingController::rate(): void`
  - `ProviderRatingController::correct(): void`

- [ ] **Step 1: Escribir test RED de contrato controller/servicio**

Verificar por lectura estática:

```php
ok(str_contains($routerBody,"['POST','/tickets/provider-rating',[ProviderRatingController::class,'rate']]"),'Existe POST de primera valoración');
ok(str_contains($routerBody,"['POST','/tickets/provider-rating/correct',[ProviderRatingController::class,'correct']]"),'Existe POST de corrección');
ok(str_contains($controllerBody,"['ADMIN','SEMIADMIN','TECHNICIAN']"),'Controller limita roles operativos');
ok(str_contains($controllerBody,'userCanAccessTicket'),'Controller exige scope backend');
ok(str_contains($controllerBody,'Csrf::verify'),'Controller valida CSRF');
ok(str_contains($serviceBody,"'PROVIDER_RATED'"),'Servicio registra PROVIDER_RATED');
ok(str_contains($serviceBody,"'PROVIDER_RATING_CORRECTED'"),'Servicio registra PROVIDER_RATING_CORRECTED');
ok(!preg_match('/UPDATE\s+ticket_events|DELETE\s+FROM\s+ticket_events/i',$serviceBody),'Valoración nunca edita ni borra eventos');
```

- [ ] **Step 2: Ejecutar RED**

```bat
C:\xampp\php\php.exe tests\phase8_provider_rating_controller_regression.php
```

Expected: FAIL por controller/rutas/métodos inexistentes.

- [ ] **Step 3: Implementar lectura/persistencia en servicio**

`ratingEventsForTickets()` debe hacer un SELECT solo de:

```sql
WHERE te.event_type IN('PROVIDER_RATED','PROVIDER_RATING_CORRECTED')
```

incluyendo `actor.full_name actor_name`, y opcionalmente restringir por `ticket_id IN (...)` con placeholders.

`enrichRows($rows)` obtiene `ticket_id` únicos, carga rating events una vez y delega a `enrichCycles()`.

Para `rateCycle()`:

1. `validateInput($score,$comment,false)`.
2. Validar que `grant_event_id` pertenece a `ticket_id` y `external_user_id` usando `ProviderParticipationService::rowsForTicket($ticketId)`.
3. Exigir `revoke_event_id !== null`; un cierre implícito por nuevo grant no es evaluable.
4. Exigir que no exista valoración vigente para ese `grant_event_id`.
5. Insertar solo `PROVIDER_RATED` con `metadata_json` exacto.

Para `correctCycle()`:

1. `validateInput($score,$comment,true)`.
2. Validar el mismo ciclo finalizado.
3. Obtener valoración vigente.
4. Exigir `current.event_id === $correctedRatingEventId` para evitar corrección obsoleta.
5. Insertar `PROVIDER_RATING_CORRECTED` con `corrected_rating_event_id`.

Usar transacción y bloquear el ticket antes de revalidar estado vigente:

```sql
SELECT id FROM tickets WHERE id=? AND deleted_at IS NULL FOR UPDATE
```

para serializar dos envíos concurrentes del mismo ticket.

- [ ] **Step 4: Implementar controller y rutas**

`ProviderRatingController` debe:

```php
private const ALLOWED_ROLES=['ADMIN','SEMIADMIN','TECHNICIAN'];
```

Flujo común:

```php
Auth::requireLogin();
Csrf::verify($_POST['_csrf']??null);
if(!in_array(Auth::role(),self::ALLOWED_ROLES,true)){http_response_code(403);throw new \RuntimeException('No tienes permiso para evaluar proveedores.');}
if(!(new ScopeService())->userCanAccessTicket((int)Auth::id(),$ticketId)){http_response_code(403);throw new \RuntimeException('Ese caso está fuera de tu alcance.');}
```

`rate()` lee `ticket_id`, `external_user_id`, `grant_event_id`, `score`, `comment`; llama `rateCycle()`; registra:

```php
Audit::log('PROVIDER_RATED','ticket',$ticketId,null,[
    'external_user_id'=>$externalUserId,
    'grant_event_id'=>$grantEventId,
    'rating_event_id'=>$eventId,
    'score'=>$score,
]);
```

`correct()` además lee `corrected_rating_event_id` y usa `Audit::log('PROVIDER_RATING_CORRECTED',...)`.

Ambos redirigen a:

```php
APP_BASE_URL.'/tickets/view?id='.$ticketId.'#provider-quality'
```

Registrar imports en `public/index.php` y rutas POST.

- [ ] **Step 5: Ejecutar GREEN + quality route gate**

```bat
C:\xampp\php\php.exe -l app\Controllers\ProviderRatingController.php
C:\xampp\php\php.exe -l app\Services\ProviderRatingService.php
C:\xampp\php\php.exe -l public\index.php
C:\xampp\php\php.exe tests\phase8_provider_rating_controller_regression.php
C:\xampp\php\php.exe tests\project_quality.php
```

Expected: todo `[OK]`.

- [ ] **Step 6: Commit**

```bat
git add app\Services\ProviderRatingService.php app\Controllers\ProviderRatingController.php public\index.php tests\phase8_provider_rating_controller_regression.php
git commit -m "feat: registrar calidad de proveedor por ciclo"
```

---

### Task 4: Mostrar y capturar valoración dentro del ticket interno

**Files:**
- Modify: `app/Controllers/TicketController.php`
- Modify: `app/Views/tickets/show.php`
- Create: `tests/phase8_provider_rating_ui_regression.php`

**Interfaces:**
- Consumes: `ProviderParticipationService::rowsForTicket()`, `ProviderRatingService::enrichRows()`, `ProviderRatingService::SCORE_LABELS`.
- Produces: variable de vista `providerCycles` y `providerRatingLabels` para ticket interno.

- [ ] **Step 1: Escribir RED de UI y no fuga externa**

El test lee `TicketController.php`, `show.php`, `show_external.php` y verifica:

```php
ok(str_contains($ticketControllerBody,'ProviderRatingService'),'Ticket carga ProviderRatingService');
ok(str_contains($showBody,'id="provider-quality"'),'Ticket interno contiene bloque Calidad del proveedor');
ok(str_contains($showBody,'Evaluar proveedor'),'Ciclo finalizado sin rating permite evaluar');
ok(str_contains($showBody,'Registrar corrección'),'Ciclo evaluado permite corrección');
ok(str_contains($showBody,'name="score"'),'Formulario captura score');
ok(str_contains($showBody,'name="comment"'),'Formulario captura comentario');
ok(!str_contains($externalBody,'PROVIDER_RATED')&&!str_contains($externalBody,'provider_rating_comment'),'Vista externa no expone valoración interna');
```

- [ ] **Step 2: Ejecutar RED**

```bat
C:\xampp\php\php.exe tests\phase8_provider_rating_ui_regression.php
```

Expected: FAIL en carga y bloque interno.

- [ ] **Step 3: Cargar ciclos enriquecidos desde `TicketController::show()`**

Solo cuando `$isSupport` sea verdadero:

```php
$providerCycles=[];
$providerRatingLabels=ProviderRatingService::SCORE_LABELS;
if($isSupport){
    $participationService=new ProviderParticipationService($pdo);
    $ratingService=new ProviderRatingService($pdo);
    $providerCycles=$ratingService->enrichRows($participationService->rowsForTicket($id));
}
```

Pasar ambas variables a `View::render('tickets/show', ...)`.

No cargar ratings en `TicketViewController` externo.

- [ ] **Step 4: Implementar bloque `#provider-quality`**

Ubicarlo en el ticket interno cerca de la colaboración con proveedor, antes de historial/resolución.

Para cada ciclo mostrar siempre:

- organización/contacto;
- asignado y revocado;
- estado `Activo`/`Finalizado`;
- valoración vigente o `Sin evaluar`.

Reglas de captura:

```php
$closed=!empty($cycle['revoke_event_id']);
$rated=($cycle['provider_rating_score']??null)!==null;
```

Si `$closed && !$rated`, renderizar POST a `/tickets/provider-rating` con hidden:

```php
_csrf
ticket_id
external_user_id
grant_event_id
```

Select/radios 1..5 con las etiquetas aprobadas y `textarea name="comment"`.

Si `$closed && $rated`, mostrar score/etiqueta, actor, fecha, comentario interno y botón/details `Registrar corrección` cuyo POST va a `/tickets/provider-rating/correct` con `corrected_rating_event_id` vigente.

Si el ciclo está activo, mostrar `Podrás evaluar cuando finalice la participación.` y ningún formulario.

Añadir ayuda de formulario: `Comentario obligatorio para 1–2 estrellas y para toda corrección.`

- [ ] **Step 5: Ejecutar GREEN y verificar que Fase 7 UI no se rompa**

```bat
C:\xampp\php\php.exe -l app\Controllers\TicketController.php
C:\xampp\php\php.exe -l app\Views\tickets\show.php
C:\xampp\php\php.exe tests\phase8_provider_rating_ui_regression.php
C:\xampp\php\php.exe tests\phase7_provider_participation_regression.php
C:\xampp\php\php.exe tests\project_quality.php
```

Expected: todo `[OK]`.

- [ ] **Step 6: Commit**

```bat
git add app\Controllers\TicketController.php app\Views\tickets\show.php tests\phase8_provider_rating_ui_regression.php
git commit -m "ui: evaluar proveedores desde el ticket"
```

---

### Task 5: Integrar valoración en Informe de proveedores y XLSX

**Files:**
- Modify: `app/Services/ProviderParticipationService.php`
- Modify: `app/Controllers/ExternalReportController.php`
- Modify: `app/Views/management/external_report.php`
- Create: `tests/phase8_provider_rating_report_regression.php`

**Interfaces:**
- Consumes: `ProviderRatingService::enrichRows()`, `providerSummary()`, `ratingOptions()`.
- Produces: filtro GET `rating`, colección `providerRatingSummary`, columnas XLSX de valoración y hoja/tabla de resumen por proveedor.

- [ ] **Step 1: Escribir RED de filtros/agregados/reportes**

Fixtures con 4 ciclos: score 5, score 3, sin evaluar, corrección 2→4. Verificar:

```php
$filtered=ProviderParticipationService::applyFilters($rows,['rating'=>'UNRATED']);
ok(count($filtered)===1,'Filtro Sin evaluar selecciona solo ciclos sin rating');
$filtered=ProviderParticipationService::applyFilters($rows,['rating'=>'4']);
ok(count($filtered)===1,'Filtro 4 estrellas usa valoración vigente');
$summary=ProviderRatingService::providerSummary($rows);
ok((float)$summary[10]['average_score']===4.0,'Promedio usa solo ratings vigentes y evaluados');
ok((int)$summary[10]['rated_cycles']===3,'Cuenta ciclos evaluados');
ok((int)$summary[10]['unrated_cycles']===1,'Cuenta ciclos sin evaluar');
```

Verificar por archivo:

```php
ok(str_contains($controllerBody,"'rating'"),'Controller normaliza filtro de valoración');
ok(str_contains($viewBody,'name="rating"'),'Vista expone filtro valoración');
ok(str_contains($controllerBody,"'Valoración proveedor'"),'XLSX exporta score');
ok(str_contains($controllerBody,"'Comentario valoración'"),'XLSX exporta comentario interno');
```

- [ ] **Step 2: Ejecutar RED**

```bat
C:\xampp\php\php.exe tests\phase8_provider_rating_report_regression.php
```

Expected: FAIL en filtro/agregados/XLSX.

- [ ] **Step 3: Extender filtro existente sin duplicar dataset**

En `ProviderParticipationService::applyFilters()` leer:

```php
$rating=(string)($filters['rating']??'');
```

Reglas:

```php
if($rating==='UNRATED'&&($row['provider_rating_score']??null)!==null)return false;
if(in_array($rating,['1','2','3','4','5'],true)&&(int)($row['provider_rating_score']??0)!==(int)$rating)return false;
```

No cambiar semántica de filtros Fase 7.

- [ ] **Step 4: Enriquecer controller antes de filtrar**

En `index()` y `export()`:

```php
$participationService=new ProviderParticipationService(Database::pdo());
$ratingService=new ProviderRatingService(Database::pdo());
$allRows=$ratingService->enrichRows($participationService->rows());
$rows=ProviderParticipationService::applyFilters($allRows,$filters);
```

En `index()` pasar:

```php
'ratingOptions'=>ProviderRatingService::ratingOptions(),
'providerRatingSummary'=>ProviderRatingService::providerSummary($rows),
```

`filters()` solo acepta `''`, `UNRATED`, `1`, `2`, `3`, `4`, `5`.

- [ ] **Step 5: Actualizar informe visual**

Agregar al resumen superior:

- `Valoración promedio` (solo si hay ciclos evaluados; de lo contrario `—`);
- `Ciclos evaluados`;
- `Sin evaluar`.

Agregar filtro select `name="rating"` con opciones de `ratingOptions`.

En cada row mostrar dentro de `Resultado` o una sublínea operativa claramente etiquetada:

```text
Valoración: 4★ Bueno
Comentario: ...
Evaluó: Nombre · fecha
Correcciones: N
```

Cuando no exista: `Valoración: Sin evaluar`.

Agregar una tabla compacta `Calidad por proveedor` con columnas:

```text
Proveedor | Promedio | Evaluados | Sin evaluar
```

No crear rankings/ordenamiento “mejor-peor”; ordenar alfabéticamente por proveedor.

- [ ] **Step 6: Actualizar XLSX**

En hoja `Participaciones` agregar encabezados al final:

```php
'Valoración proveedor','Etiqueta valoración','Comentario valoración','Evaluado por','Fecha valoración','Correcciones valoración'
```

Agregar hoja `Calidad proveedores` con:

```php
['Proveedor','Promedio valoración','Ciclos evaluados','Ciclos sin evaluar']
```

Orden alfabético. Sin score `0`; usar vacío para promedio sin evaluaciones.

Resumen principal añade:

```php
['Ciclos evaluados',...],
['Ciclos sin evaluar',...],
['Promedio valoración proveedor',...],
```

El XLSX debe usar exactamente `$rows` ya filtradas.

- [ ] **Step 7: Ejecutar GREEN + regresiones Fase 7 + XLSX smoke**

```bat
C:\xampp\php\php.exe -l app\Controllers\ExternalReportController.php
C:\xampp\php\php.exe -l app\Views\management\external_report.php
C:\xampp\php\php.exe tests\phase8_provider_rating_report_regression.php
C:\xampp\php\php.exe tests\phase7_provider_filters_regression.php
C:\xampp\php\php.exe tests\phase7_provider_participation_regression.php
C:\xampp\php\php.exe tests\xlsx_smoke.php
```

Expected: todo `[OK]`.

- [ ] **Step 8: Commit**

```bat
git add app\Services\ProviderParticipationService.php app\Controllers\ExternalReportController.php app\Views\management\external_report.php tests\phase8_provider_rating_report_regression.php
git commit -m "feat: reportar calidad de proveedores"
```

---

### Task 6: Consolidar seguridad, historial y casos límite

**Files:**
- Modify: `app/Services/ProviderRatingService.php`
- Modify: `app/Views/tickets/show.php`
- Modify: `tests/phase8_provider_rating_service_regression.php`
- Modify: `tests/phase8_provider_rating_controller_regression.php`
- Modify: `tests/phase8_provider_rating_ui_regression.php`

**Interfaces:**
- Consumes: interfaces de Tasks 2–4.
- Produces: comportamiento robusto ante rating corrupto, corrección obsoleta y ciclo implícitamente cerrado sin `EXTERNAL_REVOKED`.

- [ ] **Step 1: Añadir RED para casos límite**

Agregar casos:

```php
ok(!$service->isCycleEvaluable($implicitClosedCycle),'Cierre implícito por nuevo grant no es evaluable');
```

En pruebas puras, un `PROVIDER_RATING_CORRECTED` con `corrected_rating_event_id` que no sea el evento vigente debe ser ignorado por `buildCurrentRatings()`.

Agregar test estático:

```php
ok(!str_contains($externalViewBody,'provider_rating_score')&&!str_contains($externalViewBody,'provider_rating_comment'),'Proveedor no recibe calidad interna');
ok(!str_contains($publicTicketViewBody,'provider_rating_comment'),'Solicitante no recibe comentario interno');
```

- [ ] **Step 2: Ejecutar RED**

```bat
C:\xampp\php\php.exe tests\phase8_provider_rating_service_regression.php
C:\xampp\php\php.exe tests\phase8_provider_rating_ui_regression.php
```

Expected: al menos los nuevos casos fallan antes del ajuste.

- [ ] **Step 3: Implementar `isCycleEvaluable()` y defensas**

Agregar:

```php
public static function isCycleEvaluable(array $cycle): bool
{
    return (int)($cycle['grant_event_id']??0)>0
        && (int)($cycle['revoke_event_id']??0)>0
        && !empty($cycle['revoked_at']);
}
```

Usar esta función en `rateCycle()` / `correctCycle()` y en la vista para no duplicar criterio.

`buildCurrentRatings()` debe ignorar eventos con mismatch de `external_user_id` cuando `enrichCycles()` aplica rating a una row.

- [ ] **Step 4: Ejecutar GREEN acumulado de Fase 8**

```bat
C:\xampp\php\php.exe tests\phase8_provider_cycle_identity_regression.php
C:\xampp\php\php.exe tests\phase8_provider_rating_service_regression.php
C:\xampp\php\php.exe tests\phase8_provider_rating_controller_regression.php
C:\xampp\php\php.exe tests\phase8_provider_rating_ui_regression.php
C:\xampp\php\php.exe tests\phase8_provider_rating_report_regression.php
```

Expected: todo `[OK]`.

- [ ] **Step 5: Commit**

```bat
git add app\Services\ProviderRatingService.php app\Views\tickets\show.php tests\phase8_provider_rating_service_regression.php tests\phase8_provider_rating_controller_regression.php tests\phase8_provider_rating_ui_regression.php
git commit -m "test: endurecer reglas de calidad proveedor"
```

---

### Task 7: CI, Manual y cierre documental de Fase 8

**Files:**
- Create: `tests/phase8_provider_rating_closeout_regression.php`
- Modify: `.github/workflows/helpdesk-ci.yml`
- Modify: `README.md`
- Modify: `CHANGELOG.md`
- Modify: `app/Views/help/manual.php`

**Interfaces:**
- Consumes: todos los artefactos funcionales de Fase 8.
- Produces: gate documental/CI y roadmap con Fase 8 implementada, Fase 9 siguiente.

- [ ] **Step 1: Escribir RED de cierre**

Verificar:

```php
ok(str_contains($ciBody,'Phase 8 provider rating'),'CI declara Fase 8');
ok(str_contains($ciBody,'php tests/phase8_provider_rating_service_regression.php'),'CI ejecuta servicio Fase 8');
ok(str_contains($ciBody,'php tests/phase8_provider_rating_controller_regression.php'),'CI ejecuta controller Fase 8');
ok(str_contains($ciBody,'php tests/phase8_provider_rating_ui_regression.php'),'CI ejecuta UI Fase 8');
ok(str_contains($ciBody,'php tests/phase8_provider_rating_report_regression.php'),'CI ejecuta reportes Fase 8');
ok(str_contains($readmeBody,'| 8 | Calidad IT → proveedor | **Implementada — pendiente validación integral Fase 12** |'),'README cierra Fase 8');
ok(str_contains($readmeBody,'| 9 | Conocimiento | **Siguiente fase** |'),'README mueve siguiente a Fase 9');
ok(str_contains($changelogBody,'Fase 8')&&str_contains($changelogBody,'PROVIDER_RATED'),'CHANGELOG documenta rating');
ok(str_contains($manualBody,'Calidad del proveedor')&&str_contains($manualBody,'Muy deficiente'),'Manual explica valoración');
ok(str_contains($readmeBody,'BD: sin cambios'),'Documentación confirma cero cambios de BD');
```

- [ ] **Step 2: Ejecutar RED**

```bat
C:\xampp\php\php.exe tests\phase8_provider_rating_closeout_regression.php
```

Expected: FAIL en CI/documentación.

- [ ] **Step 3: Actualizar CI**

Añadir pasos separados para:

```text
phase8_provider_cycle_identity_regression.php
phase8_provider_rating_service_regression.php
phase8_provider_rating_controller_regression.php
phase8_provider_rating_ui_regression.php
phase8_provider_rating_report_regression.php
phase8_provider_rating_closeout_regression.php
```

Mantener todos los gates de Fase 7.

- [ ] **Step 4: Actualizar documentación**

README:
- Fase 8 `Implementada — pendiente validación integral Fase 12`.
- Fase 9 `Siguiente fase`.
- sección Fase 8 con escala 1–5, evento inmutable por ciclo, corrección, scope, reporte y **BD: sin cambios**.

CHANGELOG:
- evento `PROVIDER_RATED`;
- evento `PROVIDER_RATING_CORRECTED`;
- captura desde ticket interno;
- agregados del informe/XLSX;
- separación total de `ticket_feedback.nps_score`;
- BD sin cambios.

Manual:
- cuándo puede evaluar IT;
- significado 1–5;
- comentario obligatorio 1–2;
- cómo registrar corrección;
- `Sin evaluar` no afecta promedio;
- proveedor no ve la valoración.

- [ ] **Step 5: Ejecutar GREEN**

```bat
C:\xampp\php\php.exe tests\phase8_provider_rating_closeout_regression.php
```

Expected: todo `[OK]`.

- [ ] **Step 6: Commit**

```bat
git add .github\workflows\helpdesk-ci.yml README.md CHANGELOG.md app\Views\help\manual.php tests\phase8_provider_rating_closeout_regression.php
git commit -m "docs: cerrar fase 8 calidad proveedor"
```

---

### Task 8: Gate integral y validación PC TEST antes de merge

**Files:**
- No crear código nuevo salvo correcciones derivadas de fallos reales.
- Verify: repositorio completo y PC TEST.

**Interfaces:**
- Consumes: Fase 8 completa.
- Produces: evidencia de cierre; no hace merge automáticamente.

- [ ] **Step 1: Ejecutar sintaxis PHP de archivos modificados**

```bat
C:\xampp\php\php.exe -l app\Services\ProviderParticipationService.php
C:\xampp\php\php.exe -l app\Services\ProviderRatingService.php
C:\xampp\php\php.exe -l app\Controllers\ProviderRatingController.php
C:\xampp\php\php.exe -l app\Controllers\TicketController.php
C:\xampp\php\php.exe -l app\Controllers\ExternalReportController.php
C:\xampp\php\php.exe -l app\Views\tickets\show.php
C:\xampp\php\php.exe -l app\Views\management\external_report.php
C:\xampp\php\php.exe -l public\index.php
```

Expected: `No syntax errors detected` en todos.

- [ ] **Step 2: Ejecutar todas las regresiones Fase 8**

```bat
C:\xampp\php\php.exe tests\phase8_provider_cycle_identity_regression.php
C:\xampp\php\php.exe tests\phase8_provider_rating_service_regression.php
C:\xampp\php\php.exe tests\phase8_provider_rating_controller_regression.php
C:\xampp\php\php.exe tests\phase8_provider_rating_ui_regression.php
C:\xampp\php\php.exe tests\phase8_provider_rating_report_regression.php
C:\xampp\php\php.exe tests\phase8_provider_rating_closeout_regression.php
```

Expected: todo `[OK]`.

- [ ] **Step 3: Ejecutar regresiones Fase 7**

```bat
C:\xampp\php\php.exe tests\phase7_provider_catalog_regression.php
C:\xampp\php\php.exe tests\phase7_provider_participation_regression.php
C:\xampp\php\php.exe tests\phase7_provider_filters_regression.php
C:\xampp\php\php.exe tests\phase7_provider_activity_regression.php
C:\xampp\php\php.exe tests\phase7_provider_returns_regression.php
```

Expected: todo `[OK]`.

- [ ] **Step 4: Ejecutar quality gates base**

```bat
C:\xampp\php\php.exe tests\project_quality.php
C:\xampp\php\php.exe tests\xlsx_smoke.php
C:\xampp\php\php.exe tests\static_checks.php
git diff --check
```

Expected: todo `[OK]`; `git diff --check` sin salida de error.

- [ ] **Step 5: Verificar que Fase 8 no cambió `database/`**

```bat
git diff --name-only origin/main...HEAD -- database
```

Expected: **sin salida**.

- [ ] **Step 6: Validación funcional manual en PC TEST**

Usar un proveedor de prueba y dos ciclos del mismo ticket si es posible.

Checklist:

```text
[ ] Ciclo activo no muestra formulario de valoración.
[ ] Revocar proveedor vuelve el ciclo evaluable.
[ ] 5★ sin comentario guarda correctamente.
[ ] 1★ sin comentario es rechazado por backend.
[ ] 1★ con comentario guarda correctamente.
[ ] Corrección sin comentario es rechazada.
[ ] Corrección con comentario crea nueva versión y conserva anterior.
[ ] La última corrección es la que aparece como vigente.
[ ] El proveedor no ve valoración ni comentario al iniciar sesión.
[ ] El solicitante no ve valoración ni comentario.
[ ] Informe de proveedores muestra rating del ciclo.
[ ] Filtro Sin evaluar funciona.
[ ] Filtro 1★–5★ funciona.
[ ] Promedio ignora Sin evaluar.
[ ] XLSX coincide con filtros de pantalla.
```

- [ ] **Step 7: Validación visual**

Revisar ticket interno + Informe de proveedores en:

```text
Claro
Oscuro
Laptop 1366
Tablet/iPad ~1024/768
Móvil <=760
```

No rehacer responsive general; corregir únicamente defectos introducidos por Fase 8.

- [ ] **Step 8: Estado Git antes de integración**

```bat
git status
git branch -a
git --no-pager diff --stat origin/main...HEAD
```

Expected: rama `fase8-calidad-proveedor`, working tree limpio, sin ramas temporales adicionales.

- [ ] **Step 9: No hacer merge hasta aprobación explícita**

Cuando todos los gates y la validación visual estén aprobados, usar `superpowers:finishing-a-development-branch` y presentar las opciones de integración. No fusionar a `main` automáticamente.

---

## Self-Review

- Cobertura del spec: ciclo cerrado, escala, comentarios, correcciones, inmutabilidad, roles/scope, ticket interno, no fuga externa, reporte, XLSX, CI, docs y cero cambios BD están asignados a tareas concretas.
- Sin placeholders: no hay `TBD`, `TODO`, ni pasos genéricos sin comando/criterio.
- Consistencia de interfaces: `grant_event_id` nace en Task 1; `ProviderRatingService` nace en Task 2; persistencia/controller en Task 3; ticket interno en Task 4; informe/XLSX en Task 5; cierre y gates en Tasks 6–8.
- Scope: no incluye ranking público, bloqueo automático, SLA de proveedor, permisos nuevos, tabla de ratings ni cambios en `ticket_feedback`.
