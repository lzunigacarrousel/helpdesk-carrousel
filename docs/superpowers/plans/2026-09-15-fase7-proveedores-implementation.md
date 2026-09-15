# Fase 7 — Proveedores Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.
>
> **Modo acordado para este proyecto:** ejecución inline, secuencial y sin agentes/subagentes.

**Goal:** Consolidar métricas operativas confiables por ciclo de participación de proveedores externos y mostrarlas/exportarlas desde el informe existente, sin cambios de BD ni cambios de permisos.

**Architecture:** Crear `ProviderParticipationService` como única fuente de verdad para reconstruir ciclos `EXTERNAL_GRANTED → EXTERNAL_REVOKED` y derivar métricas desde eventos, comentarios, informes técnicos y adjuntos ya existentes. `ExternalReportController` quedará como orquestador de autorización/filtros/render/exportación y la vista consumirá el dataset ya calculado; pantalla y XLSX usarán exactamente las mismas filas filtradas.

**Tech Stack:** PHP 8.2+, MariaDB/MySQL, PDO, HTML/CSS existente, `XlsxExportService`, pruebas PHP CLI sin framework.

**Spec:** `docs/superpowers/specs/2026-09-15-fase7-proveedores-design.md`

## Global Constraints

- Objetivo de BD: **0 cambios**; no crear tablas, columnas ni migraciones.
- No agregar permisos nuevos.
- `READY_FOR_REVIEW` no modifica el estado del ticket.
- Cada `EXTERNAL_GRANTED → EXTERNAL_REVOKED` es un ciclo independiente.
- Primera respuesta = primer mensaje externo independiente o primer informe técnico del proveedor, el que ocurra primero dentro del ciclo.
- Comentarios autogenerados por un `ticket_work_report.comment_id` no deben duplicar el origen `Informe técnico` como si fueran un mensaje independiente.
- Duración de participación y `time_spent_minutes` son métricas distintas.
- Actividad actual = `work_status` del último informe técnico del ciclo; sin informe = `Sin actualización`.
- Una devolución se cuenta solo después de una entrega `READY_FOR_REVIEW` y ante un retorno posterior a `REOPENED` o `IN_PROGRESS` dentro del mismo ciclo.
- Un mismo retorno de estado cuenta como máximo una devolución; múltiples entregas previas no multiplican el mismo retorno.
- Pantalla y XLSX deben consumir el mismo dataset filtrado.
- No usar comentarios `INTERNAL` para métricas de proveedor.
- No mezclar Fase 8, rediseño global del chat, dashboard externo ni correos.

---

## File Structure

### Crear

- `app/Services/ProviderParticipationService.php` — consulta datos existentes, reconstruye ciclos y calcula métricas/filtros/resumen.
- `tests/phase7_provider_participation_regression.php` — contrato, casos puros, seguridad, controller/view/XLSX y CI.

### Modificar

- `app/Controllers/ExternalReportController.php` — delegar cálculo al servicio y exportar nuevas métricas.
- `app/Views/management/external_report.php` — resumen, filtro de actividad y tabla operativa.
- `.github/workflows/helpdesk-ci.yml` — ejecutar regresión Fase 7.
- `README.md` — estado funcional de Fase 7 al cierre.
- `CHANGELOG.md` — cambio funcional sin BD al cierre.
- documentación/manual/roadmap existente que marque Fase 7 como implementada y Fase 8 como siguiente.

### No modificar funcionalmente

- `app/Controllers/WorkReportController.php` — ya registra `work_status`, `time_spent_minutes`, `ready_for_review`, comentario y evento.
- esquema SQL / instaladores — Fase 7 no cambia BD.

---

### Task 1: Definir contrato RED de `ProviderParticipationService`

**Files:**
- Create: `tests/phase7_provider_participation_regression.php`
- Create later in this task: `app/Services/ProviderParticipationService.php`

**Interfaces:**
- Produces: `ProviderParticipationService::buildCycles(array $users, array $events, array $comments, array $attachments, array $reports, ?int $nowTs=null): array`
- Produces: filas con claves estables usadas por tareas posteriores.

- [ ] **Step 1: Crear el test con helpers y fixtures mínimos**

El archivo debe empezar con:

```php
<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$servicePath=$root.'/app/Services/ProviderParticipationService.php';
$controllerPath=$root.'/app/Controllers/ExternalReportController.php';
$viewPath=$root.'/app/Views/management/external_report.php';
$workReportPath=$root.'/app/Controllers/WorkReportController.php';
$ciPath=$root.'/.github/workflows/helpdesk-ci.yml';
$errors=0;
function ok(bool $condition,string $message):void{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}
function j(array $value):string{return json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}
```

Definir fixtures deterministas:

```php
$users=[
    10=>['id'=>10,'full_name'=>'Proveedor Uno','email'=>'proveedor1@example.com','organization_name'=>'Proveedor Uno, S.A.'],
    11=>['id'=>11,'full_name'=>'Proveedor Dos','email'=>'proveedor2@example.com','organization_name'=>'Proveedor Dos, S.A.'],
];
$events=[
    ['id'=>1,'ticket_id'=>100,'event_type'=>'EXTERNAL_GRANTED','old_value'=>null,'new_value'=>j(['external_user_id'=>10]),'metadata_json'=>j(['can_comment'=>true,'can_upload'=>true]),'created_at'=>'2026-09-01 08:00:00','ticket_number'=>'HD-100','subject'=>'Caso A','ticket_status'=>'REOPENED','actor_name'=>'Luis'],
    ['id'=>2,'ticket_id'=>100,'event_type'=>'EXTERNAL_REVOKED','old_value'=>j(['external_user_id'=>10]),'new_value'=>null,'metadata_json'=>'{}','created_at'=>'2026-09-03 08:00:00','ticket_number'=>'HD-100','subject'=>'Caso A','ticket_status'=>'REOPENED','actor_name'=>'Luis'],
    ['id'=>3,'ticket_id'=>100,'event_type'=>'EXTERNAL_GRANTED','old_value'=>null,'new_value'=>j(['external_user_id'=>10]),'metadata_json'=>j(['can_comment'=>true,'can_upload'=>true]),'created_at'=>'2026-09-04 08:00:00','ticket_number'=>'HD-100','subject'=>'Caso A','ticket_status'=>'REOPENED','actor_name'=>'Luis'],
];
```

- [ ] **Step 2: Agregar aserciones RED de estructura y ciclos**

Antes de crear el servicio, validar:

```php
$serviceBody=is_file($servicePath)?file_get_contents($servicePath):'';
ok($serviceBody!=='','Existe ProviderParticipationService');
ok(str_contains($serviceBody,'function buildCycles('),'Expone buildCycles');
ok(str_contains($serviceBody,'function rows('),'Expone rows');
ok(!preg_match('/\b(INSERT|UPDATE|DELETE|ALTER|CREATE TABLE)\b/i',$serviceBody),'Servicio no escribe ni altera BD');
```

Cuando exista la clase, el fixture anterior debe producir dos ciclos distintos del mismo proveedor/ticket.

- [ ] **Step 3: Ejecutar el test y confirmar RED**

Run:

```cmd
C:\xampp\php\php.exe tests\phase7_provider_participation_regression.php
```

Expected: FAIL porque `ProviderParticipationService` todavía no existe.

- [ ] **Step 4: Crear el esqueleto mínimo del servicio**

```php
<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;

final class ProviderParticipationService
{
    public const WORK_STATUS_LABELS=[
        'ANALYSIS'=>'En análisis / diagnóstico',
        'WAITING_CARROUSEL'=>'Esperando información de Carrousel',
        'WAITING_THIRD_PARTY'=>'Esperando tercero / fabricante',
        'IN_PROGRESS'=>'En atención / trabajando',
        'VALIDATING'=>'En validación',
        'READY_FOR_REVIEW'=>'Listo para revisión de Carrousel',
    ];

    public function __construct(private ?object $pdo=null){}

    private function pdo(): object{return $this->pdo??Database::pdo();}

    public function rows(?int $nowTs=null): array
    {
        return [];
    }

    public static function buildCycles(array $users,array $events,array $comments,array $attachments,array $reports,?int $nowTs=null): array
    {
        return [];
    }
}
```

- [ ] **Step 5: Ejecutar test para confirmar que solo fallan comportamientos aún no implementados**

Run: `C:\xampp\php\php.exe tests\phase7_provider_participation_regression.php`

Expected: contrato de archivo/métodos PASS; ciclos funcionales aún FAIL.

- [ ] **Step 6: Commit**

```cmd
git add tests\phase7_provider_participation_regression.php app\Services\ProviderParticipationService.php
git commit -m "test: definir contrato de participacion de proveedores"
```

---

### Task 2: Reconstruir ciclos independientes y métricas básicas

**Files:**
- Modify: `app/Services/ProviderParticipationService.php`
- Modify: `tests/phase7_provider_participation_regression.php`

**Interfaces:**
- Consumes: `buildCycles(...)` de Task 1.
- Produces cada fila con: `ticket_id`, `user_id`, `organization`, `contact`, `email`, `ticket_number`, `subject`, `ticket_status`, `granted_at`, `granted_by`, `revoked_at`, `revoked_by`, `duration_minutes`, `responses`, `attachments`, `reports`, `declared_minutes`.

- [ ] **Step 1: Agregar pruebas de ciclo cerrado, activo y reasignación**

Agregar un ciclo activo y validar con `nowTs=strtotime('2026-09-05 08:00:00')`:

```php
ok(count($rows)===2,'Dos grants del mismo proveedor generan dos ciclos');
ok($rows[0]['duration_minutes']===2880,'Ciclo cerrado dura 48 horas');
ok($rows[1]['duration_minutes']===1440,'Ciclo activo usa ahora como fin');
ok($rows[0]['revoked_by']==='Luis','Ciclo cerrado conserva actor de revocación');
```

También cubrir grant duplicado sin revoke: un nuevo `EXTERNAL_GRANTED` debe cerrar el ciclo abierto anterior en el timestamp del nuevo grant con `revoked_by = 'Nueva asignación'`.

- [ ] **Step 2: Ejecutar RED**

Run: `C:\xampp\php\php.exe tests\phase7_provider_participation_regression.php`

Expected: FAIL en reconstrucción/duración.

- [ ] **Step 3: Implementar reconstrucción en `buildCycles`**

Usar la misma clave `ticket_id:user_id`, ordenar eventos por `created_at,id`, abrir en `EXTERNAL_GRANTED`, cerrar en `EXTERNAL_REVOKED`, y cerrar implícitamente un ciclo anterior si llega otro grant.

La fila base debe inicializar:

```php
[
 'ticket_id'=>$ticketId,
 'user_id'=>$uid,
 'organization'=>(string)$user['organization_name'],
 'contact'=>(string)$user['full_name'],
 'email'=>(string)$user['email'],
 'ticket_number'=>(string)$event['ticket_number'],
 'subject'=>(string)$event['subject'],
 'ticket_status'=>(string)$event['ticket_status'],
 'granted_at'=>(string)$event['created_at'],
 'granted_by'=>(string)($event['actor_name']?:'Sistema'),
 'revoked_at'=>null,
 'revoked_by'=>null,
 'duration_minutes'=>0,
 'responses'=>0,
 'attachments'=>0,
 'reports'=>0,
 'declared_minutes'=>0,
]
```

- [ ] **Step 4: Agregar `rows()` para cargar datasets existentes**

Consultas de solo lectura:

```sql
SELECT u.id,u.full_name,u.email,COALESCE(ep.organization_name,u.full_name) organization_name
FROM users u
LEFT JOIN external_profiles ep ON ep.user_id=u.id
WHERE u.access_type='EXTERNAL'
```

```sql
SELECT te.id,te.ticket_id,te.event_type,te.old_value,te.new_value,te.metadata_json,te.created_at,
       t.ticket_number,t.subject,t.status ticket_status,actor.full_name actor_name
FROM ticket_events te
JOIN tickets t ON t.id=te.ticket_id
LEFT JOIN users actor ON actor.id=te.actor_user_id
WHERE te.event_type IN('EXTERNAL_GRANTED','EXTERNAL_REVOKED','STATUS_CHANGED')
  AND t.deleted_at IS NULL
ORDER BY te.created_at,te.id
```

Además cargar comentarios EXTERNAL, adjuntos EXTERNAL e informes `author_access_type='EXTERNAL'` con sus `id`, `ticket_id`, `author_user_id`, timestamps y campos necesarios.

- [ ] **Step 5: Ejecutar test GREEN para ciclos**

Run: `C:\xampp\php\php.exe tests\phase7_provider_participation_regression.php`

Expected: casos de ciclo/duración PASS.

- [ ] **Step 6: Commit**

```cmd
git add app\Services\ProviderParticipationService.php tests\phase7_provider_participation_regression.php
git commit -m "feat: reconstruir ciclos de proveedores"
```

---

### Task 3: Primera respuesta, trabajo, actividad actual y adjuntos sin doble conteo

**Files:**
- Modify: `app/Services/ProviderParticipationService.php`
- Modify: `tests/phase7_provider_participation_regression.php`

**Interfaces:**
- Adds row keys: `first_response_at`, `first_response_minutes`, `first_response_origin`, `last_activity_at`, `work_status`, `activity_label`, `deliveries`.

- [ ] **Step 1: Agregar fixtures de mensajes e informes**

Usar, por ejemplo:

```php
$comments=[
 ['id'=>501,'ticket_id'=>100,'author_user_id'=>10,'visibility'=>'EXTERNAL','created_at'=>'2026-09-01 09:00:00'],
 ['id'=>502,'ticket_id'=>100,'author_user_id'=>10,'visibility'=>'INTERNAL','created_at'=>'2026-09-01 08:15:00'],
 ['id'=>503,'ticket_id'=>100,'author_user_id'=>10,'visibility'=>'EXTERNAL','created_at'=>'2026-09-04 10:00:00'],
];
$reports=[
 ['id'=>601,'ticket_id'=>100,'author_user_id'=>10,'comment_id'=>501,'work_status'=>'ANALYSIS','time_spent_minutes'=>30,'ready_for_review'=>0,'created_at'=>'2026-09-01 09:30:00'],
 ['id'=>602,'ticket_id'=>100,'author_user_id'=>10,'comment_id'=>null,'work_status'=>'READY_FOR_REVIEW','time_spent_minutes'=>45,'ready_for_review'=>1,'created_at'=>'2026-09-04 09:00:00'],
];
```

Ajustar el primer reporte para que su `comment_id` apunte a un comentario autogenerado específico y agregar un mensaje independiente distinto. La prueba debe demostrar que un comentario enlazado a `ticket_work_reports.comment_id` no gana como `Mensaje` frente al propio `Informe técnico`.

- [ ] **Step 2: Agregar aserciones RED**

```php
ok($cycle['first_response_origin']==='Mensaje','Mensaje independiente puede ser primera respuesta');
ok($cycle['first_response_minutes']===60,'Tiempo primera respuesta se mide desde grant');
ok($cycle['declared_minutes']===75,'Suma tiempo declarado dentro del ciclo');
ok($cycle['reports']===2,'Cuenta informes técnicos del ciclo');
ok($cycle['work_status']==='READY_FOR_REVIEW','Actividad actual usa último informe');
ok($cycle['activity_label']==='Listo para revisión de Carrousel','Actividad actual usa etiqueta operativa');
ok($cycle['deliveries']===1,'Cuenta entregas READY_FOR_REVIEW');
```

Crear otro ciclo donde el primer evento real sea un informe y validar `first_response_origin==='Informe técnico'`.

- [ ] **Step 3: Ejecutar RED**

Run: `C:\xampp\php\php.exe tests\phase7_provider_participation_regression.php`

- [ ] **Step 4: Implementar asociación temporal por ciclo**

Crear helper privado/estático equivalente a:

```php
private static function rowIndexFor(array $rows,int $ticketId,int $userId,string $createdAt): ?int
```

Debe aceptar actividad cuando `created_at >= granted_at` y `created_at <= revoked_at`; para ciclo activo, sin límite superior.

Para primera respuesta:

- construir set de `comment_id` referenciados por informes del mismo proveedor;
- comentarios de ese set no se consideran mensajes independientes para `first_response_origin`;
- informes sí son candidatos con origen `Informe técnico`;
- mensajes externos independientes son candidatos con origen `Mensaje`;
- en empate exacto de timestamp entre informe y su comentario asociado, gana `Informe técnico`.

- [ ] **Step 5: Contar adjuntos, respuestas, informes, tiempo y última actividad**

- `responses`: comentarios EXTERNAL independientes del proveedor dentro del ciclo;
- `attachments`: adjuntos EXTERNAL subidos por ese proveedor dentro del ciclo;
- `reports`: informes técnicos del proveedor dentro del ciclo;
- `declared_minutes`: suma de `time_spent_minutes`;
- `work_status`: último informe por `created_at,id`;
- `last_activity_at`: timestamp del último informe; si no hay informe, `null`;
- `deliveries`: informes con `work_status='READY_FOR_REVIEW'` o `ready_for_review=1`.

- [ ] **Step 6: Ejecutar GREEN**

Run: `C:\xampp\php\php.exe tests\phase7_provider_participation_regression.php`

- [ ] **Step 7: Commit**

```cmd
git add app\Services\ProviderParticipationService.php tests\phase7_provider_participation_regression.php
git commit -m "feat: calcular respuesta y actividad de proveedores"
```

---

### Task 4: Calcular devoluciones después de READY_FOR_REVIEW

**Files:**
- Modify: `app/Services/ProviderParticipationService.php`
- Modify: `tests/phase7_provider_participation_regression.php`

**Interfaces:**
- Adds row key: `returns`.

- [ ] **Step 1: Crear casos de estado antes y después de una entrega**

Fixtures de `STATUS_CHANGED` deben usar `new_value` JSON del flujo real:

```php
['event_type'=>'STATUS_CHANGED','ticket_id'=>100,'created_at'=>'2026-09-01 08:30:00','new_value'=>j(['status'=>'REOPENED'])],
['event_type'=>'STATUS_CHANGED','ticket_id'=>100,'created_at'=>'2026-09-04 12:00:00','new_value'=>j(['status'=>'IN_PROGRESS'])],
```

Validar:

```php
ok($cycle['returns']===1,'Solo retorno posterior a READY_FOR_REVIEW cuenta');
```

Agregar dos entregas antes de un único retorno y validar que el retorno cuente solo una vez.

- [ ] **Step 2: Ejecutar RED**

Run: `C:\xampp\php\php.exe tests\phase7_provider_participation_regression.php`

- [ ] **Step 3: Implementar emparejamiento entrega → retorno**

Para cada ciclo:

1. ordenar timestamps de entregas `READY_FOR_REVIEW`;
2. ordenar eventos `STATUS_CHANGED` dentro del ciclo cuyo nuevo estado sea `REOPENED` o `IN_PROGRESS`;
3. recorrer retornos cronológicamente;
4. un retorno cuenta si existe al menos una entrega posterior al último retorno contado y anterior al retorno actual;
5. después de contar el retorno, avanzar el corte para que el mismo retorno no cuente varias entregas.

Pseudoimplementación exacta:

```php
$returns=0;$lastReturnAt=0;
foreach($returnTimes as $returnAt){
    $hasDelivery=false;
    foreach($deliveryTimes as $deliveryAt){
        if($deliveryAt>$lastReturnAt&&$deliveryAt<$returnAt){$hasDelivery=true;break;}
    }
    if($hasDelivery){$returns++;$lastReturnAt=$returnAt;}
}
```

- [ ] **Step 4: Agregar guardas**

No contar:

- reapertura anterior a la primera entrega;
- eventos fuera del ciclo;
- `PENDING`, `RESOLVED`, `CLOSED`, `CANCELLED`;
- retornos de otro ticket/proveedor fuera de la ventana temporal.

- [ ] **Step 5: Ejecutar GREEN**

Run: `C:\xampp\php\php.exe tests\phase7_provider_participation_regression.php`

- [ ] **Step 6: Commit**

```cmd
git add app\Services\ProviderParticipationService.php tests\phase7_provider_participation_regression.php
git commit -m "feat: medir devoluciones de proveedores"
```

---

### Task 5: Filtros, resumen y opciones del informe

**Files:**
- Modify: `app/Services/ProviderParticipationService.php`
- Modify: `tests/phase7_provider_participation_regression.php`

**Interfaces:**
- Produces: `applyFilters(array $rows,array $filters): array`
- Produces: `summary(array $rows): array`
- Produces: `providers(array $rows): array`
- Produces: `activityOptions(): array`

- [ ] **Step 1: Escribir pruebas RED para filtros**

Contrato de filtros:

```php
[
 'q'=>'',
 'provider'=>0,
 'state'=>'',      // '', active, closed
 'activity'=>'',   // '', NONE o WORK_STATUS
 'from'=>'',
 'to'=>'',
]
```

Validar proveedor, estado de ciclo, actividad actual y fechas por `granted_at`.

Para `activity='NONE'`, solo filas con `work_status===null`.

- [ ] **Step 2: Escribir pruebas RED para resumen**

Resumen esperado:

```php
[
 'participations'=>count($rows),
 'active'=>...,
 'no_response'=>...,
 'avg_first_response_minutes'=>null|int,
 'returns'=>...,
]
```

El promedio usa únicamente filas con `first_response_minutes !== null` y `round(sum/count)`.

- [ ] **Step 3: Ejecutar RED**

Run: `C:\xampp\php\php.exe tests\phase7_provider_participation_regression.php`

- [ ] **Step 4: Implementar helpers públicos puros**

```php
public static function applyFilters(array $rows,array $filters): array
public static function summary(array $rows): array
public static function providers(array $rows): array
public static function activityOptions(): array
```

`activityOptions()` debe devolver:

```php
[
 'NONE'=>'Sin actualización',
 'ANALYSIS'=>'En análisis / diagnóstico',
 'WAITING_CARROUSEL'=>'Esperando información de Carrousel',
 'WAITING_THIRD_PARTY'=>'Esperando tercero / fabricante',
 'IN_PROGRESS'=>'En atención / trabajando',
 'VALIDATING'=>'En validación',
 'READY_FOR_REVIEW'=>'Listo para revisión de Carrousel',
]
```

- [ ] **Step 5: Ejecutar GREEN**

Run: `C:\xampp\php\php.exe tests\phase7_provider_participation_regression.php`

- [ ] **Step 6: Commit**

```cmd
git add app\Services\ProviderParticipationService.php tests\phase7_provider_participation_regression.php
git commit -m "feat: filtrar y resumir participacion externa"
```

---

### Task 6: Refactorizar `ExternalReportController` y XLSX sobre el servicio

**Files:**
- Modify: `app/Controllers/ExternalReportController.php`
- Modify: `tests/phase7_provider_participation_regression.php`

**Interfaces:**
- Consumes: `ProviderParticipationService::rows`, `applyFilters`, `summary`, `providers`, `activityOptions`.
- Produces para la vista: `rows`, `summary`, `filters`, `providers`, `activityOptions`.

- [ ] **Step 1: Agregar pruebas estáticas RED del controller**

```php
$controllerBody=file_get_contents($controllerPath);
ok(str_contains($controllerBody,'ProviderParticipationService'),'Controller usa ProviderParticipationService');
ok(!str_contains($controllerBody,'private function history('),'Controller ya no reconstruye ciclos');
ok(str_contains($controllerBody,"'activity'"),'Controller normaliza filtro de actividad');
ok(str_contains($controllerBody,"'activityOptions'"),'Controller expone opciones de actividad');
```

También validar que `requireAccess()` conserve `ADMIN`, `SEMIADMIN`, `external.manage` y `reports.view`.

- [ ] **Step 2: Ejecutar RED**

Run: `C:\xampp\php\php.exe tests\phase7_provider_participation_regression.php`

- [ ] **Step 3: Refactorizar `index()`**

Estructura objetivo:

```php
$service=new ProviderParticipationService(Database::pdo());
$allRows=$service->rows();
$filters=$this->filters();
$rows=ProviderParticipationService::applyFilters($allRows,$filters);
View::render('management/external_report',[
    'user'=>Auth::user(),
    'rows'=>$rows,
    'summary'=>ProviderParticipationService::summary($rows),
    'filters'=>$filters,
    'providers'=>ProviderParticipationService::providers($allRows),
    'activityOptions'=>ProviderParticipationService::activityOptions(),
]);
```

- [ ] **Step 4: Actualizar filtros del controller**

Aceptar `activity` solo si es `NONE` o clave válida de `WORK_STATUS_LABELS`; si no, usar `''`.

- [ ] **Step 5: Refactorizar `export()` usando las mismas filas filtradas**

Cabeceras mínimas de `Participaciones`:

```php
[
 'Proveedor','Contacto','Correo','Ticket','Asunto','Asignado','Asignado por',
 'Revocado','Revocado por','Duración (min)','Primera respuesta','T. primera respuesta (min)',
 'Origen primera respuesta','Actividad actual','Última actualización','Trabajo declarado (min)',
 'Respuestas','Archivos','Informes','Entregas listas','Devoluciones','Estado ticket','Estado ciclo'
]
```

Resumen XLSX:

```php
['Participaciones',$summary['participations']],
['Activas',$summary['active']],
['Sin respuesta',$summary['no_response']],
['Promedio primera respuesta (min)',$summary['avg_first_response_minutes']??''],
['Devoluciones',$summary['returns']],
```

- [ ] **Step 6: Ejecutar lint + regresión**

```cmd
C:\xampp\php\php.exe -l app\Controllers\ExternalReportController.php
C:\xampp\php\php.exe tests\phase7_provider_participation_regression.php
```

Expected: PASS.

- [ ] **Step 7: Commit**

```cmd
git add app\Controllers\ExternalReportController.php tests\phase7_provider_participation_regression.php
git commit -m "refactor: centralizar informe de proveedores"
```

---

### Task 7: Rediseñar el informe de proveedores sin duplicar administración

**Files:**
- Modify: `app/Views/management/external_report.php`
- Modify: `tests/phase7_provider_participation_regression.php`

**Interfaces:**
- Consumes variables del Task 6.

- [ ] **Step 1: Agregar pruebas RED de UI**

Validar que la vista contenga etiquetas:

```php
$viewBody=file_get_contents($viewPath);
ok(str_contains($viewBody,'Sin respuesta'),'Resumen muestra Sin respuesta');
ok(str_contains($viewBody,'Primera respuesta'),'Tabla muestra Primera respuesta');
ok(str_contains($viewBody,'Actividad actual'),'Tabla muestra Actividad actual');
ok(str_contains($viewBody,'Devoluciones'),'Tabla muestra devoluciones');
ok(str_contains($viewBody,'name="activity"'),'Existe filtro Actividad actual');
ok(!str_contains($viewBody,'Permisos</th>'),'Permisos deja de ser columna principal');
```

- [ ] **Step 2: Ejecutar RED**

Run: `C:\xampp\php\php.exe tests\phase7_provider_participation_regression.php`

- [ ] **Step 3: Actualizar resumen a cinco tarjetas**

Mostrar exactamente:

- Participaciones;
- Activas;
- Sin respuesta;
- T. primera respuesta;
- Devoluciones.

Usar el formateador de duración existente para `avg_first_response_minutes`; si es `null`, mostrar `—`.

- [ ] **Step 4: Agregar filtro Actividad actual**

```php
<label>Actividad actual
<select class="form-control" name="activity">
  <option value="">Todas</option>
  <?php foreach($activityOptions as $value=>$label): ?>
    <option value="<?= htmlspecialchars($value) ?>" <?= $filters['activity']===$value?'selected':'' ?>><?= htmlspecialchars($label) ?></option>
  <?php endforeach; ?>
</select>
</label>
```

Ajustar `external-report-filters` para seis filtros + acciones manteniendo responsive.

- [ ] **Step 5: Reemplazar tabla por siete columnas operativas**

Cabecera:

```html
<tr>
  <th>Proveedor / Ticket</th>
  <th>Asignación</th>
  <th>Primera respuesta</th>
  <th>Participación</th>
  <th>Actividad actual</th>
  <th>Trabajo</th>
  <th>Resultado</th>
</tr>
```

Reglas de render:

- Primera respuesta sin respuesta: `Sin respuesta`.
- Con respuesta: fecha + duración + `Mensaje`/`Informe técnico`.
- Trabajo: tiempo declarado + `N informes · N respuestas · N archivos`.
- Resultado: `N entregas · N devoluciones` + estado actual del ticket + ciclo Activo/Finalizado.

- [ ] **Step 6: Ejecutar lint/regresión**

```cmd
C:\xampp\php\php.exe -l app\Views\management\external_report.php
C:\xampp\php\php.exe tests\phase7_provider_participation_regression.php
```

- [ ] **Step 7: Commit**

```cmd
git add app\Views\management\external_report.php tests\phase7_provider_participation_regression.php
git commit -m "ui: mejorar informe operativo de proveedores"
```

---

### Task 8: Blindar READY_FOR_REVIEW, CI y cierre documental

**Files:**
- Modify: `tests/phase7_provider_participation_regression.php`
- Modify: `.github/workflows/helpdesk-ci.yml`
- Modify: `README.md`
- Modify: `CHANGELOG.md`
- Modify: roadmap/manual existente identificado durante implementación.

**Interfaces:**
- Produces gate final de Fase 7.

- [ ] **Step 1: Agregar regresión estática sobre `WorkReportController`**

```php
$workBody=file_get_contents($workReportPath);
ok(str_contains($workBody,"$readyForReview=$workStatus==='READY_FOR_REVIEW'"),'Work report conserva READY_FOR_REVIEW');
ok(!str_contains($workBody,"UPDATE tickets SET status"),'READY_FOR_REVIEW no cambia estado del ticket');
ok(!str_contains($workBody,'WorkflowController'),'Informe externo no invoca WorkflowController');
```

La aserción debe escapar correctamente `$` en PHP para no interpolar accidentalmente al construir el test.

- [ ] **Step 2: Agregar CI**

Después de Fase 6 en `.github/workflows/helpdesk-ci.yml`:

```yaml
      - name: Phase 7 provider participation regression
        run: php tests/phase7_provider_participation_regression.php
```

- [ ] **Step 3: Actualizar documentación de cierre**

Registrar explícitamente:

- Fase 7 implementada;
- 0 cambios de BD;
- métricas por ciclo;
- primera respuesta mensaje/informe;
- actividad actual;
- devoluciones posteriores a READY_FOR_REVIEW;
- XLSX consistente;
- Fase 8 como siguiente.

No documentar funcionalidades de Fase 8 como implementadas.

- [ ] **Step 4: Ejecutar regresión de Fase 7 y gates relacionados**

```cmd
C:\xampp\php\php.exe tests\phase7_provider_participation_regression.php
C:\xampp\php\php.exe tests\project_quality.php
C:\xampp\php\php.exe tests\xlsx_smoke.php
C:\xampp\php\php.exe tests\static_checks.php
```

- [ ] **Step 5: Ejecutar lint de archivos tocados**

```cmd
C:\xampp\php\php.exe -l app\Services\ProviderParticipationService.php
C:\xampp\php\php.exe -l app\Controllers\ExternalReportController.php
C:\xampp\php\php.exe -l app\Views\management\external_report.php
C:\xampp\php\php.exe -l tests\phase7_provider_participation_regression.php
```

- [ ] **Step 6: Verificar que no hubo cambios de BD**

```cmd
git diff --name-only origin/main...HEAD -- database
git diff --check
git status
```

Expected: primer comando sin salida; `git diff --check` sin errores.

- [ ] **Step 7: Commit de cierre documental/CI**

```cmd
git add .github\workflows\helpdesk-ci.yml tests\phase7_provider_participation_regression.php README.md CHANGELOG.md docs
git commit -m "docs: cerrar fase 7 proveedores"
```

---

## Final Verification Gate

Antes de considerar Fase 7 terminada, ejecutar en PC TEST:

```cmd
cd /d C:\xampp\htdocs\HelpdeskCarrousel

git status
C:\xampp\php\php.exe tests\phase7_provider_participation_regression.php
C:\xampp\php\php.exe tests\project_quality.php
C:\xampp\php\php.exe tests\xlsx_smoke.php
C:\xampp\php\php.exe tests\static_checks.php
C:\xampp\php\php.exe -l app\Services\ProviderParticipationService.php
C:\xampp\php\php.exe -l app\Controllers\ExternalReportController.php
C:\xampp\php\php.exe -l app\Views\management\external_report.php
git diff --check
git diff --name-only origin/main...HEAD -- database
```

Luego validar visualmente `/admin/externos/informe` con al menos:

1. ciclo activo sin respuesta;
2. ciclo con primera respuesta por mensaje;
3. ciclo con primera respuesta por informe;
4. ciclo finalizado;
5. proveedor con `READY_FOR_REVIEW` y devolución posterior;
6. filtros Proveedor, Estado, Actividad, Desde/Hasta;
7. exportación XLSX con los mismos resultados filtrados.

No fusionar a `main` hasta que los comandos y la validación visual estén verdes.

## Self-Review

- Cobertura del spec: ciclos, primera respuesta, duración, trabajo declarado, actividad, entregas, devoluciones, filtros, resumen, XLSX, permisos, aislamiento y 0 BD están asignados a tareas concretas.
- No se introducen permisos, rutas ni tablas nuevas.
- El servicio es la única fuente de cálculo; controller y XLSX no duplican lógica.
- La distinción entre mensaje y comentario autogenerado por informe evita doble conteo/origen ambiguo.
- Devoluciones usan `STATUS_CHANGED` y solo `REOPENED`/`IN_PROGRESS` posteriores a una entrega.
- Ejecución acordada: inline, secuencial, sin agentes.
