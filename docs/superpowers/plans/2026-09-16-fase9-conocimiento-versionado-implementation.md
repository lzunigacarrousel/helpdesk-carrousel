# Fase 9 — Conocimiento Versionado Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Convertir el módulo de Conocimiento actual en un núcleo versionado, auditable y reutilizable, con publicación interna/pública separada, referencias desde tickets, autoservicio y métricas, sin destruir datos existentes.

**Architecture:** `knowledge_articles` conserva la identidad estable `KB-*`; el contenido pasa a `knowledge_revisions`. Cada artículo mantiene un puntero interno y otro público. La lógica de negocio sale del controlador hacia servicios especializados y las relaciones de uso/sugerencia quedan en tablas append-only o estructuradas.

**Tech Stack:** PHP 8+, MariaDB 10.4+, PDO, HTML/CSS/JS actuales, tests PHP CLI existentes, GitHub Actions actual. Sin frameworks nuevos.

**Spec:** `docs/superpowers/specs/2026-09-16-fase9-conocimiento-versionado-design.md`

## Global Constraints

- Trabajar únicamente sobre `main`.
- No desplegar producción durante implementación ni validación.
- Toda migración debe ser incremental, aditiva e idempotente.
- Probar migración primero en PC TEST.
- No eliminar columnas legacy de `knowledge_articles` en Fase 9.
- `TECHNICIAN` puede crear/mejorar borradores, pero no publicar.
- `ADMIN`/`SEMIADMIN` revisan y publican.
- Publicación interna y pública son acciones distintas.
- Un artículo publicado nunca se sobrescribe directamente.
- Restaurar una versión antigua crea una revisión borrador nueva.
- Autoservicio solo consume contenido público vigente y nunca bloquea crear ticket.
- Reutilizar `SolutionSuggestionService` en lugar de crear un motor paralelo.
- Mantener `problem_solutions`.
- Mantener auditoría y `ticket_events`.
- UI tocada en Fase 9 debe aplicar el backlog de pulido: copy claro, menos badges, botonera simple, responsive PC/iPad/móvil.
- No agregar React, Vue, Angular, Bootstrap nuevo, WebSockets ni dependencias grandes.

---

## File Map

### Nuevos archivos previstos

- `database/MIGRAR_FASE9_CONOCIMIENTO_20260916.sql` — migración aditiva e idempotente.
- `database/VERIFICAR_FASE9_CONOCIMIENTO_20260916.sql` — verificación de esquema/backfill.
- `app/Services/KnowledgeRevisionService.php` — ciclo de vida de artículos/revisiones.
- `app/Services/KnowledgeCandidateService.php` — elegibilidad de tickets como candidatos a conocimiento.
- `app/Services/KnowledgeReferenceService.php` — uso de artículo/problema/ticket como referencia.
- `app/Services/KnowledgeMetricsService.php` — eventos de sugerencia/apertura/uso.
- `app/Views/knowledge/history.php` — historial de revisiones.
- `app/Views/knowledge/compare.php` — comparación de revisiones.
- `tests/phase9_knowledge_schema_regression.php`
- `tests/phase9_knowledge_revision_service_regression.php`
- `tests/phase9_knowledge_permissions_regression.php`
- `tests/phase9_knowledge_workflow_controller_regression.php`
- `tests/phase9_knowledge_candidate_regression.php`
- `tests/phase9_knowledge_reference_regression.php`
- `tests/phase9_solution_suggestions_regression.php`
- `tests/phase9_self_service_regression.php`
- `tests/phase9_knowledge_history_regression.php`
- `tests/phase9_knowledge_metrics_regression.php`
- `tests/phase9_knowledge_ui_regression.php`
- `tests/phase9_closeout_regression.php`
- `VALIDAR_FASE9.bat`

### Archivos existentes a modificar

- `database/INSTALAR.sql`
- `database/VERIFICAR_INSTALACION.sql`
- `app/Controllers/KnowledgeController.php`
- `app/Controllers/TicketController.php`
- `app/Controllers/ResolutionController.php`
- `app/Services/SolutionSuggestionService.php`
- `app/Views/knowledge/index.php`
- `app/Views/knowledge/form.php`
- `app/Views/knowledge/show.php`
- `app/Views/tickets/show.php`
- `app/Views/tickets/public_create.php`
- `app/Views/help/manual.php` si el manual actual está centralizado ahí; si no, modificar la vista real usada por `HelpController`.
- `public/index.php`
- `.github/workflows/helpdesk-ci.yml`
- `CHANGELOG.md`
- `docs/superpowers/logs/fase9-conocimiento-progress.md`

---

### Task 1: Congelar contrato de esquema y migración aditiva

**Files:**
- Create: `tests/phase9_knowledge_schema_regression.php`
- Create: `database/MIGRAR_FASE9_CONOCIMIENTO_20260916.sql`
- Create: `database/VERIFICAR_FASE9_CONOCIMIENTO_20260916.sql`
- Modify: `database/INSTALAR.sql`
- Modify: `database/VERIFICAR_INSTALACION.sql`

**Interfaces:**
- Produces tables: `knowledge_revisions`, `knowledge_article_sources`, `ticket_resolution_references`, `solution_suggestion_events`.
- Produces columns on `knowledge_articles`: `lifecycle_status`, `current_internal_revision_id`, `current_public_revision_id`, `created_by_user_id`, `archived_at`.
- Preserves all legacy columns during Fase 9.

- [ ] **Step 1: Write failing schema regression**

Create `tests/phase9_knowledge_schema_regression.php` with checks for exact table/column names in `INSTALAR.sql` and migration SQL:

```php
<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$install=(string)file_get_contents($root.'/database/INSTALAR.sql');
$migration=(string)file_get_contents($root.'/database/MIGRAR_FASE9_CONOCIMIENTO_20260916.sql');
$errors=0;
function ok(bool $c,string $m):void{global $errors;echo ($c?'[OK] ':'[FALLO] ').$m.PHP_EOL;if(!$c)$errors++;}
foreach(['knowledge_revisions','knowledge_article_sources','ticket_resolution_references','solution_suggestion_events'] as $table){
    ok(str_contains($install,'CREATE TABLE '.$table),"INSTALAR contiene {$table}");
    ok(str_contains($migration,$table),"Migración contiene {$table}");
}
foreach(['current_internal_revision_id','current_public_revision_id','lifecycle_status'] as $column){
    ok(str_contains($install,$column),"INSTALAR contiene {$column}");
    ok(str_contains($migration,$column),"Migración contiene {$column}");
}
ok(!str_contains($migration,'DROP COLUMN title'),'Migración no elimina columnas legacy');
ok(!str_contains($migration,'DROP TABLE knowledge_articles'),'Migración conserva knowledge_articles');
if($errors)exit(1);
echo '[OK] Contrato de esquema Fase 9.'.PHP_EOL;
```

- [ ] **Step 2: Run failing test**

```bat
C:\xampp\php\php.exe tests\phase9_knowledge_schema_regression.php
```

Expected: FAIL porque la migración/tablas aún no existen.

- [ ] **Step 3: Implement migration and canonical schema**

`MIGRAR_FASE9_CONOCIMIENTO_20260916.sql` must:

```sql
ALTER TABLE knowledge_articles
  ADD COLUMN IF NOT EXISTS lifecycle_status ENUM('ACTIVE','ARCHIVED') NOT NULL DEFAULT 'ACTIVE',
  ADD COLUMN IF NOT EXISTS current_internal_revision_id BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS current_public_revision_id BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS created_by_user_id BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS archived_at DATETIME NULL;

CREATE TABLE IF NOT EXISTS knowledge_revisions (...);
CREATE TABLE IF NOT EXISTS knowledge_article_sources (...);
CREATE TABLE IF NOT EXISTS ticket_resolution_references (...);
CREATE TABLE IF NOT EXISTS solution_suggestion_events (...);
```

Backfill requirements:

```sql
INSERT INTO knowledge_revisions(...)
SELECT ... FROM knowledge_articles ka
WHERE NOT EXISTS (
  SELECT 1 FROM knowledge_revisions kr WHERE kr.article_id=ka.id AND kr.revision_number=1
);
```

Map legacy states exactly:

```text
DRAFT -> revision state DRAFT
PUBLISHED -> revision state PUBLISHED
ARCHIVED -> lifecycle_status ARCHIVED; revision content preserved
PUBLISHED + INTERNAL -> current_internal_revision_id
PUBLISHED + PUBLIC -> current_internal_revision_id + current_public_revision_id
```

- [ ] **Step 4: Add verification SQL**

`VERIFICAR_FASE9_CONOCIMIENTO_20260916.sql` must return explicit PASS/FAIL-friendly resultsets for:

```sql
SELECT COUNT(*) legacy_articles FROM knowledge_articles;
SELECT COUNT(DISTINCT article_id) revision_articles FROM knowledge_revisions;
SELECT COUNT(*) published_without_internal_pointer
FROM knowledge_articles
WHERE status='PUBLISHED' AND current_internal_revision_id IS NULL;
SELECT COUNT(*) public_without_public_pointer
FROM knowledge_articles
WHERE status='PUBLISHED' AND visibility='PUBLIC' AND current_public_revision_id IS NULL;
```

- [ ] **Step 5: Run regression again**

```bat
C:\xampp\php\php.exe tests\phase9_knowledge_schema_regression.php
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bat
git add database tests\phase9_knowledge_schema_regression.php
git commit -m "feat: agregar esquema versionado de conocimiento fase 9"
```

---

### Task 2: Implementar núcleo de revisiones con TDD

**Files:**
- Create: `tests/phase9_knowledge_revision_service_regression.php`
- Create: `app/Services/KnowledgeRevisionService.php`

**Interfaces:**
- `createArticle(array $data,int $userId): array`
- `createDraftFromRevision(int $articleId,int $revisionId,int $userId,?string $changeNote=null): array`
- `updateDraft(int $revisionId,array $data,int $userId): void`
- `submitForReview(int $revisionId,int $userId): void`
- `returnToDraft(int $revisionId,int $reviewerId,string $note): void`
- `publishInternal(int $revisionId,int $reviewerId): void`
- `publishPublic(int $revisionId,int $reviewerId): void`
- `restoreAsDraft(int $articleId,int $sourceRevisionId,int $userId,string $note): array`
- `archiveArticle(int $articleId,int $userId): void`

- [ ] **Step 1: Write failing domain test**

Test static/domain helpers first so state rules can be tested without DB coupling:

```php
ok($class::canTransition('DRAFT','IN_REVIEW'),'DRAFT -> IN_REVIEW permitido');
ok($class::canTransition('IN_REVIEW','DRAFT'),'IN_REVIEW -> DRAFT permitido');
ok($class::canTransition('IN_REVIEW','PUBLISHED'),'IN_REVIEW -> PUBLISHED permitido');
ok(!$class::canTransition('PUBLISHED','DRAFT'),'PUBLISHED -> DRAFT prohibido');
ok($class::nextRevisionNumber([1,2,3])===4,'Número de revisión es monotónico');
```

- [ ] **Step 2: Run failing test**

```bat
C:\xampp\php\php.exe tests\phase9_knowledge_revision_service_regression.php
```

Expected: FAIL; service missing.

- [ ] **Step 3: Implement minimal state helpers and transactional methods**

`KnowledgeRevisionService` must centralize all writes and use `Database::transaction()` for pointer-changing operations.

Core invariant for `publishInternal()`:

```php
if($revision['state']!=='IN_REVIEW'){
    throw new \RuntimeException('La revisión debe estar en revisión antes de publicarse.');
}
```

Core invariant for `publishPublic()`:

```php
if($revision['state']!=='PUBLISHED' || empty($revision['internal_published_at'])){
    throw new \RuntimeException('Solo una revisión publicada para soporte puede habilitarse para solicitantes.');
}
```

`updateDraft()` must reject non-drafts.

- [ ] **Step 4: Re-run test**

Expected: PASS.

- [ ] **Step 5: Commit**

```bat
git add app\Services\KnowledgeRevisionService.php tests\phase9_knowledge_revision_service_regression.php
git commit -m "feat: implementar nucleo de revisiones de conocimiento"
```

---

### Task 3: Separar permisos editoriales y de publicación

**Files:**
- Create: `tests/phase9_knowledge_permissions_regression.php`
- Modify: `database/INSTALAR.sql`
- Modify: `database/MIGRAR_FASE9_CONOCIMIENTO_20260916.sql`

**Interfaces:**
- Permissions: `knowledge.draft_manage`, `knowledge.review`, `knowledge.publish_internal`, `knowledge.publish_public`, `knowledge.history`, `knowledge.restore`.

- [ ] **Step 1: Write failing permission regression**

```php
ok(str_contains($install,"'knowledge.draft_manage'"),'Existe permiso draft_manage');
ok(str_contains($install,"'knowledge.publish_public'"),'Existe permiso publish_public');
ok(str_contains($install,"WHERE r.code='TECHNICIAN'"),'Existe bloque TECHNICIAN');
ok(str_contains($install,"'knowledge.draft_manage'"),'TECHNICIAN recibe borradores');
```

Also assert code does not use `knowledge.manage` for publishing once Task 4 lands.

- [ ] **Step 2: Run test and verify FAIL**

```bat
C:\xampp\php\php.exe tests\phase9_knowledge_permissions_regression.php
```

- [ ] **Step 3: Add permissions idempotently**

Migration pattern:

```sql
INSERT INTO permissions(code,name,module,description)
SELECT 'knowledge.draft_manage','Crear y editar borradores','knowledge','...'
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='knowledge.draft_manage');
```

Assign:

```text
TECHNICIAN: knowledge.view + knowledge.draft_manage
SEMIADMIN: all Fase 9 knowledge permissions
ADMIN: all permissions via existing admin rule
```

- [ ] **Step 4: Re-run PASS and commit**

```bat
git add database tests\phase9_knowledge_permissions_regression.php
git commit -m "feat: separar permisos editoriales de conocimiento"
```

---

### Task 4: Refactorizar KnowledgeController al workflow versionado

**Files:**
- Create: `tests/phase9_knowledge_workflow_controller_regression.php`
- Modify: `app/Controllers/KnowledgeController.php`
- Modify: `public/index.php`

**Interfaces:**
- Controller delegates writes to `KnowledgeRevisionService`.
- New POST routes:
  - `/knowledge/submit-review`
  - `/knowledge/return-draft`
  - `/knowledge/publish-internal`
  - `/knowledge/publish-public`
  - `/knowledge/restore`
- Existing `/knowledge/publish` may remain temporarily as compatibility redirect only if needed, but must not bypass permissions.

- [ ] **Step 1: Write failing controller regression**

Assert route strings and permission checks:

```php
ok(str_contains($routes,"/knowledge/publish-internal"),'Ruta publicación interna');
ok(str_contains($controller,"knowledge.publish_internal"),'Publicación interna exige permiso específico');
ok(str_contains($controller,"knowledge.publish_public"),'Publicación pública exige permiso específico');
ok(!str_contains($controller,"UPDATE knowledge_articles SET title="),'Controller no sobrescribe contenido legacy');
```

- [ ] **Step 2: Run and verify FAIL**

- [ ] **Step 3: Replace direct SQL workflow writes**

Controller methods should be thin:

```php
public function publishInternal():void
{
    Auth::requirePermission('knowledge.publish_internal');
    Csrf::verify($_POST['_csrf']??null);
    $revisionId=(int)($_POST['revision_id']??0);
    (new KnowledgeRevisionService())->publishInternal($revisionId,(int)Auth::id());
    Flash::set('Versión publicada para soporte.','success');
    header('Location: '.APP_BASE_URL.'/knowledge/view?id='.(int)($_POST['article_id']??0));exit;
}
```

- [ ] **Step 4: Run regression PASS**

- [ ] **Step 5: Commit**

```bat
git add app\Controllers\KnowledgeController.php public\index.php tests\phase9_knowledge_workflow_controller_regression.php
git commit -m "refactor: mover conocimiento al workflow versionado"
```

---

### Task 5: Migrar lectura/listados/vistas al modelo versionado

**Files:**
- Create: `tests/phase9_knowledge_ui_regression.php`
- Modify: `app/Controllers/KnowledgeController.php`
- Modify: `app/Views/knowledge/index.php`
- Modify: `app/Views/knowledge/form.php`
- Modify: `app/Views/knowledge/show.php`

**Interfaces:**
- Internos leen `current_internal_revision_id` para contenido vigente.
- Usuarios sin acceso interno solo leen revisión pública vigente cuando corresponda.
- Borradores visibles/editables solo con permisos editoriales.

- [ ] **Step 1: Write failing UI regression**

Assert visible copy and removal of legacy semantics:

```php
ok(str_contains($show,'Publicado para soporte'),'UI distingue publicación interna');
ok(str_contains($show,'Disponible para solicitantes'),'UI distingue publicación pública');
ok(!str_contains($show,'visibility'),'No expone tecnicismo visibility');
ok(!str_contains($show,'scope'),'No expone tecnicismo scope');
```

- [ ] **Step 2: Run FAIL**

- [ ] **Step 3: Implement list/detail/form queries using revisions**

Target query shape:

```sql
SELECT ka.*, kr.title,kr.summary,kr.content,kr.category_id,kr.revision_number,kr.state
FROM knowledge_articles ka
LEFT JOIN knowledge_revisions kr ON kr.id=ka.current_internal_revision_id
```

For editorial list, also expose latest draft/review via subquery/order, without replacing current published pointer.

- [ ] **Step 4: Apply pulido rules**

- one primary action;
- max 2–3 secondary actions;
- remaining actions under `Más acciones`;
- no score/ranking badges;
- state labels in Spanish;
- responsive structure compatible with iPad.

- [ ] **Step 5: Run PASS and commit**

```bat
git add app\Controllers\KnowledgeController.php app\Views\knowledge tests\phase9_knowledge_ui_regression.php
git commit -m "feat: adaptar interfaz de conocimiento a revisiones"
```

---

### Task 6: Historial, comparación y restauración segura

**Files:**
- Create: `tests/phase9_knowledge_history_regression.php`
- Create: `app/Views/knowledge/history.php`
- Create: `app/Views/knowledge/compare.php`
- Modify: `app/Controllers/KnowledgeController.php`
- Modify: `public/index.php`

**Interfaces:**
- GET `/knowledge/history?id={article}`
- GET `/knowledge/compare?id={article}&from={revision}&to={revision}`
- POST `/knowledge/restore`

- [ ] **Step 1: Write failing history regression**

```php
ok(str_contains($routes,"/knowledge/history"),'Ruta historial');
ok(str_contains($routes,"/knowledge/compare"),'Ruta comparar');
ok(str_contains($controller,"knowledge.history"),'Historial exige permiso');
ok(str_contains($controller,"knowledge.restore"),'Restauración exige permiso');
```

- [ ] **Step 2: Run FAIL**

- [ ] **Step 3: Implement history/compare read-only views**

Comparison must render field-level blocks for at least:

```text
Título
Resumen
Contenido
Categoría
```

Do not mutate revisions from compare view.

- [ ] **Step 4: Implement restore using service**

```php
$draft=$service->restoreAsDraft($articleId,$sourceRevisionId,(int)Auth::id(),$note);
```

Expected: new revision number; no pointer changes until publish.

- [ ] **Step 5: PASS and commit**

```bat
git add app\Controllers\KnowledgeController.php app\Views\knowledge public\index.php tests\phase9_knowledge_history_regression.php
git commit -m "feat: agregar historial comparacion y restauracion de conocimiento"
```

---

### Task 7: Detectar tickets candidatos a conocimiento

**Files:**
- Create: `tests/phase9_knowledge_candidate_regression.php`
- Create: `app/Services/KnowledgeCandidateService.php`
- Modify: `app/Controllers/TicketController.php`
- Modify: `app/Views/tickets/show.php`

**Interfaces:**
- `KnowledgeCandidateService::evaluate(array $ticket,array $resolution,array $signals=[]): array`
- Output:

```php
[
  'eligible'=>true,
  'reasons'=>['ROOT_CAUSE_DOCUMENTED','KNOWN_PROBLEM'],
  'documentation_ok'=>true,
]
```

- [ ] **Step 1: Write failing candidate test**

Cases:

```php
ok(!$class::evaluate($open,$resolution,[])['eligible'],'Ticket abierto no es candidato');
ok(!$class::evaluate($resolved,['solution_applied'=>''],['ROOT_CAUSE_DOCUMENTED'])['eligible'],'Sin solución documentada no es candidato');
ok($class::evaluate($resolved,$good,['KNOWN_PROBLEM'])['eligible'],'Resuelto + documentado + señal sí es candidato');
```

- [ ] **Step 2: Run FAIL**

- [ ] **Step 3: Implement pure eligibility logic**

Accepted statuses: `RESOLVED`, `CLOSED`.

Documentation rule: non-empty meaningful `solution_applied` plus enough structured context from `root_cause`, `preventive_action`, or linked known problem. Do not use character count as the only criterion.

- [ ] **Step 4: Surface suggestion in ticket**

Only when eligible:

```text
Este caso puede convertirse en conocimiento reutilizable.
[Crear borrador]
```

The CTA must route to `/knowledge/new?ticket_id=...` and never publish.

- [ ] **Step 5: PASS and commit**

```bat
git add app\Services\KnowledgeCandidateService.php app\Controllers\TicketController.php app\Views\tickets\show.php tests\phase9_knowledge_candidate_regression.php
git commit -m "feat: sugerir conocimiento desde tickets reutilizables"
```

---

### Task 8: Implementar “Usar como referencia” y trazabilidad

**Files:**
- Create: `tests/phase9_knowledge_reference_regression.php`
- Create: `app/Services/KnowledgeReferenceService.php`
- Modify: `app/Controllers/ResolutionController.php`
- Modify: `public/index.php`
- Modify: `app/Views/tickets/show.php`

**Interfaces:**
- `useReference(int $ticketId,string $type,int $referenceId,int $userId): array`
- Return prefill:

```php
[
  'root_cause'=>'...',
  'solution'=>'...',
  'prevention'=>'...',
  'reference'=>['type'=>'KNOWLEDGE','article_id'=>12,'revision_id'=>44],
]
```

- [ ] **Step 1: Write failing reference test**

Verify mapping and no automatic state change:

```php
ok(str_contains($service,'ticket_resolution_references'),'Registra relación estructurada');
ok(!str_contains($service,"status='RESOLVED'"),'Usar referencia no resuelve ticket');
```

- [ ] **Step 2: Run FAIL**

- [ ] **Step 3: Implement reference extraction**

For `KNOWLEDGE`, capture exact active internal revision id. For `PROBLEM`, map `root_cause`, `permanent_solution/workaround`. For `TICKET`, map prior `ticket_resolutions` fields.

- [ ] **Step 4: Persist trace + ticket event**

Insert one row in `ticket_resolution_references` and event like:

```text
KNOWLEDGE_REFERENCE_USED
```

with article/revision identifiers in JSON.

- [ ] **Step 5: Wire POST route and editable prefill**

Add POST `/tickets/reference/use`.

Resolution form values must remain editable after prefill.

- [ ] **Step 6: PASS and commit**

```bat
git add app\Services\KnowledgeReferenceService.php app\Controllers\ResolutionController.php app\Views\tickets\show.php public\index.php tests\phase9_knowledge_reference_regression.php
git commit -m "feat: registrar y reutilizar referencias de solucion"
```

---

### Task 9: Evolucionar SolutionSuggestionService al modelo versionado

**Files:**
- Create: `tests/phase9_solution_suggestions_regression.php`
- Modify: `app/Services/SolutionSuggestionService.php`
- Modify: `app/Controllers/TicketController.php`
- Modify: `app/Views/tickets/show.php`

**Interfaces:**
- Keep `forTicket(array $ticket,int $limit=5): array`.
- ARTICLE candidates must read only active `current_internal_revision_id`.
- Return 3–5 suggestions in UI; service max remains parameterized.

- [ ] **Step 1: Write failing regression**

```php
ok(str_contains($service,'current_internal_revision_id'),'Artículos usan puntero interno vigente');
ok(str_contains($service,'knowledge_revisions'),'Ranking usa revisiones');
ok(!str_contains($service,"ka.status='PUBLISHED'"),'No depende del status legacy como fuente principal');
```

- [ ] **Step 2: Run FAIL**

- [ ] **Step 3: Refactor article query**

Shape:

```sql
SELECT ka.id,ka.article_number,kr.id revision_id,kr.title,kr.summary,kr.content,kr.category_id
FROM knowledge_articles ka
JOIN knowledge_revisions kr ON kr.id=ka.current_internal_revision_id
WHERE ka.lifecycle_status='ACTIVE'
```

Keep existing problem/ticket suggestion logic.

- [ ] **Step 4: Add “Ver” and “Usar como referencia” actions**

Do not display raw numeric score in UI.

- [ ] **Step 5: PASS and commit**

```bat
git add app\Services\SolutionSuggestionService.php app\Controllers\TicketController.php app\Views\tickets\show.php tests\phase9_solution_suggestions_regression.php
git commit -m "feat: adaptar sugerencias al conocimiento versionado"
```

---

### Task 10: Autoservicio público con máximo 3 artículos

**Files:**
- Create: `tests/phase9_self_service_regression.php`
- Modify: `app/Services/SolutionSuggestionService.php`
- Modify: `app/Controllers/TicketController.php`
- Modify: `app/Views/tickets/public_create.php`

**Interfaces:**
- Add `forRequesterDraft(array $context,int $limit=3): array` or equivalent explicit method.
- Must only read `current_public_revision_id`.

- [ ] **Step 1: Write failing self-service test**

```php
ok(str_contains($service,'current_public_revision_id'),'Autoservicio usa puntero público');
ok(str_contains($publicView,'Esto podría ayudarte'),'Vista muestra bloque de ayuda');
ok(str_contains($publicView,'crear-ticket'),'El flujo de ticket continúa disponible');
```

- [ ] **Step 2: Run FAIL**

- [ ] **Step 3: Implement public-only query**

```sql
SELECT ka.id,ka.article_number,kr.id revision_id,kr.title,kr.summary,kr.content,kr.category_id
FROM knowledge_articles ka
JOIN knowledge_revisions kr ON kr.id=ka.current_public_revision_id
WHERE ka.lifecycle_status='ACTIVE'
LIMIT ...
```

Never include problems, old tickets, drafts or internal-only revisions in self-service.

- [ ] **Step 4: Add non-blocking UI**

Block must be compact:

```text
Esto podría ayudarte
[Artículo 1]
[Artículo 2]
[Artículo 3]

¿Aún necesitas ayuda? Continúa con tu solicitud.
```

- [ ] **Step 5: PASS and commit**

```bat
git add app\Services\SolutionSuggestionService.php app\Controllers\TicketController.php app\Views\tickets\public_create.php tests\phase9_self_service_regression.php
git commit -m "feat: agregar autoservicio publico de conocimiento"
```

---

### Task 11: Métricas de sugerencia, apertura y uso

**Files:**
- Create: `tests/phase9_knowledge_metrics_regression.php`
- Create: `app/Services/KnowledgeMetricsService.php`
- Modify: `app/Services/SolutionSuggestionService.php`
- Modify: `app/Services/KnowledgeReferenceService.php`

**Interfaces:**
- `recordSuggested(...)`
- `recordOpened(...)`
- `recordUsedReference(...)`
- `effectivenessSummary(...)`

- [ ] **Step 1: Write failing metrics test**

Assert allowed event types and no duplication of ticket status events:

```php
ok($class::isAllowedEvent('SUGGESTED'),'Permite SUGGESTED');
ok($class::isAllowedEvent('OPENED'),'Permite OPENED');
ok($class::isAllowedEvent('USED_REFERENCE'),'Permite USED_REFERENCE');
ok(!$class::isAllowedEvent('TICKET_RESOLVED'),'No duplica resolución');
```

- [ ] **Step 2: Run FAIL**

- [ ] **Step 3: Implement append-only recorder**

Every event must preserve context, reference type, exact article/revision where applicable, rank and score when available.

- [ ] **Step 4: Compute effectiveness using existing ticket status history**

Definition:

```text
used reference -> later resolution -> no later reopen = effective
```

Do not infer effectiveness only from clicks.

- [ ] **Step 5: PASS and commit**

```bat
git add app\Services\KnowledgeMetricsService.php app\Services\SolutionSuggestionService.php app\Services\KnowledgeReferenceService.php tests\phase9_knowledge_metrics_regression.php
git commit -m "feat: medir uso y efectividad de conocimiento"
```

---

### Task 12: Manual/tutorial y regresión visual de Fase 9

**Files:**
- Modify: actual manual view used by `HelpController`
- Modify: knowledge views touched in prior tasks
- Create or modify: tutorial registry/file used by current app
- Extend: `tests/phase9_knowledge_ui_regression.php`

**Interfaces:**
- Help must explain workflow without technical permission names.

- [ ] **Step 1: Extend failing UI regression for help content**

Required visible concepts:

```text
Crear borrador
Enviar a revisión
Publicado para soporte
Disponible para solicitantes
Comparar versiones
Restaurar versión
Usar como referencia
```

- [ ] **Step 2: Add help/manual content according to role**

REQUESTER must not see admin publishing/restoration instructions.
TECHNICIAN sees draft/use-reference flow.
ADMIN/SEMIADMIN see review/publish/history/restore flow.

- [ ] **Step 3: Verify responsive/static quality**

Run:

```bat
C:\xampp\php\php.exe tests\dark_theme_coverage_smoke.php
C:\xampp\php\php.exe tests\project_quality.php
```

- [ ] **Step 4: Commit**

```bat
git add app\Views tests\phase9_knowledge_ui_regression.php
git commit -m "docs: integrar ayuda y pulido visual de conocimiento"
```

---

### Task 13: Validación real de migración en PC TEST

**Files:**
- No production changes.
- Use: `database/MIGRAR_FASE9_CONOCIMIENTO_20260916.sql`
- Use: `database/VERIFICAR_FASE9_CONOCIMIENTO_20260916.sql`

**Interfaces:**
- PC TEST database only.

- [ ] **Step 1: Backup PC TEST database before migration**

Use existing Helpdesk/XAMPP backup procedure. Confirm backup file exists before SQL execution.

- [ ] **Step 2: Record pre-migration counts**

```sql
SELECT COUNT(*) FROM knowledge_articles;
SELECT status,visibility,COUNT(*) FROM knowledge_articles GROUP BY status,visibility;
SELECT COUNT(*) FROM problem_solutions;
```

- [ ] **Step 3: Run migration once**

```bat
C:\xampp\mysql\bin\mysql.exe -u root carrousel_helpdesk < database\MIGRAR_FASE9_CONOCIMIENTO_20260916.sql
```

Adapt credentials only to the PC TEST configuration; never embed passwords into repository files.

- [ ] **Step 4: Run migration a second time**

Expected: no duplicate revisions/tables/errors; validates idempotency.

- [ ] **Step 5: Run verification SQL**

```bat
C:\xampp\mysql\bin\mysql.exe -u root carrousel_helpdesk < database\VERIFICAR_FASE9_CONOCIMIENTO_20260916.sql
```

Expected:

```text
legacy article count == distinct revision article count
published_without_internal_pointer == 0
public_without_public_pointer == 0
problem_solutions count unchanged
```

- [ ] **Step 6: Manual smoke on PC TEST**

Verify at least:

```text
1 legacy draft
1 legacy internal published article
1 legacy public published article
1 archived article if dataset has one
1 problem_solutions relationship
```

- [ ] **Step 7: Record exact results in progress log**

No production migration yet.

---

### Task 14: CI, gate final and closeout

**Files:**
- Create: `tests/phase9_closeout_regression.php`
- Create: `VALIDAR_FASE9.bat`
- Modify: `.github/workflows/helpdesk-ci.yml`
- Modify: `CHANGELOG.md`
- Modify: `docs/superpowers/logs/fase9-conocimiento-progress.md`

**Interfaces:**
- Gate must run all Phase 9 regressions plus baseline static/project/XLSX checks.

- [ ] **Step 1: Write failing closeout regression**

Assert CI and BAT include all phase 9 tests:

```php
$required=[
 'phase9_knowledge_schema_regression.php',
 'phase9_knowledge_revision_service_regression.php',
 'phase9_knowledge_permissions_regression.php',
 'phase9_knowledge_workflow_controller_regression.php',
 'phase9_knowledge_candidate_regression.php',
 'phase9_knowledge_reference_regression.php',
 'phase9_solution_suggestions_regression.php',
 'phase9_self_service_regression.php',
 'phase9_knowledge_history_regression.php',
 'phase9_knowledge_metrics_regression.php',
 'phase9_knowledge_ui_regression.php',
];
```

- [ ] **Step 2: Create `VALIDAR_FASE9.bat`**

Pattern based on `VALIDAR_FASE8.bat`, but header must say:

```text
HELPDESK CARROUSEL - GATE FINAL FASE 9
Rama esperada: main
```

Include:

```text
phase9_* tests
static_checks.php
project_quality.php
xlsx_smoke.php
git diff --check
git status --short
```

- [ ] **Step 3: Add phase 9 tests to CI**

Modify `.github/workflows/helpdesk-ci.yml` so the same regressions run remotely.

- [ ] **Step 4: Update changelog and progress log**

Changelog must list actual implemented behavior, not design intentions.

Progress log must include:

```text
migrations executed on PC TEST
verification counts
tests PASS/FAIL
final commit SHA
production status = untouched
```

- [ ] **Step 5: Run complete gate locally**

```bat
VALIDAR_FASE9.bat
```

Expected: all GREEN.

- [ ] **Step 6: Verify Git diff**

```bat
git diff --check
git status --short
```

- [ ] **Step 7: Commit closeout**

```bat
git add .github VALIDAR_FASE9.bat tests\phase9_closeout_regression.php CHANGELOG.md docs\superpowers\logs\fase9-conocimiento-progress.md
git commit -m "test: cerrar gate fase 9 conocimiento"
```

- [ ] **Step 8: Fetch CI status and record it**

Only after local gate passes. Do not deploy production automatically.

---

## Mandatory Manual Test Matrix Before Final Gate

### Knowledge workflow

```text
TECHNICIAN creates draft -> PASS
TECHNICIAN edits draft -> PASS
TECHNICIAN cannot publish -> PASS
SEMIADMIN sends/returns review -> PASS
SEMIADMIN publishes internal -> PASS
Public pointer unchanged -> PASS
SEMIADMIN publishes public -> PASS
Published revision cannot be directly edited -> PASS
Edit published creates new draft -> PASS
Restore old revision creates new draft -> PASS
```

### Ticket integration

```text
3–5 internal suggestions render -> PASS
Open suggestion -> metric recorded -> PASS
Use as reference -> trace recorded -> PASS
Prefill remains editable -> PASS
Ticket state does not auto-resolve -> PASS
Eligible resolved ticket suggests creating draft -> PASS
Ineligible ticket does not suggest draft -> PASS
```

### Self-service

```text
Only public current revisions appear -> PASS
Max 3 suggestions -> PASS
Internal/draft revisions never appear -> PASS
User can continue creating ticket -> PASS
```

### Security

```text
REQUESTER cannot access draft/history/admin actions -> PASS
TECHNICIAN cannot review/publish/restore -> PASS
SEMIADMIN/ADMIN can review/publish/restore -> PASS
Direct POST without permission rejected -> PASS
CSRF applies to every write -> PASS
```

### Migration

```text
Legacy count preserved -> PASS
Article numbers preserved -> PASS
Titles/content/categories preserved -> PASS
Published/internal pointers correct -> PASS
Published/public pointers correct -> PASS
problem_solutions preserved -> PASS
Second migration run idempotent -> PASS
```

### Visual/responsive

```text
1920x1080 -> PASS
1366x768 -> PASS
iPad horizontal -> PASS
iPad vertical -> PASS
mobile -> PASS
light theme -> PASS
dark theme -> PASS
```

---

## Commit Sequence Expected

1. `feat: agregar esquema versionado de conocimiento fase 9`
2. `feat: implementar nucleo de revisiones de conocimiento`
3. `feat: separar permisos editoriales de conocimiento`
4. `refactor: mover conocimiento al workflow versionado`
5. `feat: adaptar interfaz de conocimiento a revisiones`
6. `feat: agregar historial comparacion y restauracion de conocimiento`
7. `feat: sugerir conocimiento desde tickets reutilizables`
8. `feat: registrar y reutilizar referencias de solucion`
9. `feat: adaptar sugerencias al conocimiento versionado`
10. `feat: agregar autoservicio publico de conocimiento`
11. `feat: medir uso y efectividad de conocimiento`
12. `docs: integrar ayuda y pulido visual de conocimiento`
13. PC TEST migration evidence in progress log
14. `test: cerrar gate fase 9 conocimiento`

## Stop Conditions

Stop implementation and do not advance to the next task if any of these occurs:

- migration changes article counts unexpectedly;
- a public legacy article loses public visibility;
- `problem_solutions` changes unexpectedly;
- a TECHNICIAN can publish through direct POST;
- public autoservice can see internal/draft content;
- published content can still be overwritten in place;
- `VALIDAR_FASE9.bat` reports any failure.

Do not deploy production until all stop conditions are clear, PC TEST is validated, local gate is GREEN, CI is GREEN, progress log is updated, and the user explicitly approves production deployment.
