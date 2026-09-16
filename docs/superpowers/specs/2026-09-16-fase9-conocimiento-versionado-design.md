# Helpdesk Carrousel — Fase 9: Conocimiento versionado

Fecha: 2026-09-16
Rama: `main`
Estado: diseño aprobado; spec lista para revisión

## Objetivo

Convertir el módulo de Conocimiento existente en un núcleo versionado, auditable y reutilizable que permita:

- conservar una identidad estable por artículo (`KB-AAAA-NNNN`);
- separar contenido editable de contenido publicado;
- permitir que `TECHNICIAN` cree y mejore borradores sin poder publicar;
- exigir revisión de `ADMIN` / `SEMIADMIN` antes de publicar;
- publicar primero para uso interno y, mediante una segunda acción separada, habilitar una revisión para autoservicio;
- sugerir conocimiento reutilizable dentro del ticket y durante la creación de tickets;
- permitir `Usar como referencia` sin resolver automáticamente;
- registrar trazabilidad estructurada entre ticket y referencia utilizada;
- medir sugerencias, aperturas, uso y efectividad real;
- mantener historial completo, comparación y restauración segura;
- migrar los artículos actuales sin destruir datos ni modificar producción antes de validar en PC TEST.

La fase aprovecha la infraestructura ya existente. No reconstruye tickets, problemas conocidos, auditoría, búsqueda, categorías ni el motor de sugerencias.

## Principios aprobados

1. El conocimiento inicia como interno.
2. Ningún ticket publica conocimiento automáticamente.
3. Una sugerencia de creación desde ticket aparece solo cuando el caso está resuelto, tiene solución suficientemente documentada y presenta al menos una señal de reutilización.
4. `TECHNICIAN` puede crear y mejorar borradores.
5. `ADMIN` y `SEMIADMIN` revisan y publican.
6. Publicar internamente y publicar para autoservicio son acciones distintas.
7. Los artículos publicados no se sobrescriben.
8. Editar contenido publicado crea una nueva revisión borrador.
9. Restaurar una versión anterior crea una revisión nueva; nunca mueve silenciosamente la versión vigente hacia atrás.
10. El solicitante puede ver sugerencias de autoservicio, pero nunca se bloquea la creación del ticket.
11. Toda reutilización desde artículo, problema o ticket debe quedar trazada.
12. La pantalla principal debe ser simple; la ayuda puede ser profunda.

## Estado actual confirmado

El código actual contiene:

- `KnowledgeController`;
- vistas `knowledge/index.php`, `knowledge/form.php`, `knowledge/show.php` y relacionadas;
- `SolutionSuggestionService`;
- tabla `knowledge_articles` con contenido, estado y visibilidad en la misma fila;
- estados `DRAFT / PUBLISHED / ARCHIVED`;
- visibilidad `INTERNAL / PUBLIC`;
- creación desde ticket y problema conocido;
- relación `problem_solutions`;
- auditoría y `ticket_events`;
- búsqueda y categorías.

La limitación estructural principal es que `KnowledgeController::update()` sobrescribe directamente título, resumen, contenido, visibilidad y categoría de `knowledge_articles`. Esto es incompatible con el versionado aprobado para Fase 9.

## Alcance de Fase 9

### Incluido

- núcleo versionado de conocimiento;
- workflow editorial;
- permisos separados de edición y publicación;
- migración controlada de artículos actuales;
- sugerencias dentro del ticket;
- sugerencia de creación de conocimiento desde tickets elegibles;
- acción `Usar como referencia`;
- relación estructurada ticket ↔ referencia;
- eventos/métricas de sugerencia y uso;
- autoservicio con artículos públicos;
- historial, comparación y restauración;
- adaptación del buscador y listados al nuevo modelo;
- pruebas automáticas y gate específico de Fase 9;
- log de continuidad.

### Fuera de alcance

No forman parte de la implementación central de Fase 9:

- reconstrucción general del dashboard;
- rediseño global de correo;
- refactor completo de conversación/chat;
- simplificación global del formulario público;
- revisión integral de notificaciones;
- nueva arquitectura del manual;
- WebSockets;
- frameworks nuevos;
- migraciones destructivas;
- despliegue a producción.

Estos temas permanecen en el backlog de pulido transversal y se aplicarán cuando la fase correspondiente los toque. Los criterios visuales y de copy sí se respetarán en las pantallas modificadas por Fase 9.

# 1. Arquitectura elegida

## 1.1 Identidad estable + revisiones + dos canales de publicación

`knowledge_articles` queda como identidad estable del artículo.

El contenido vive en `knowledge_revisions`.

Cada artículo puede apuntar simultáneamente a:

- una revisión vigente interna;
- una revisión vigente pública.

Ejemplo:

```text
KB-2026-0001

current_internal_revision_id -> REV 4
current_public_revision_id   -> REV 3
```

Esto permite publicar una revisión nueva para técnicos sin alterar automáticamente la versión visible en autoservicio.

## 1.2 Regla de inmutabilidad

Una revisión publicada no admite edición de contenido.

Toda edición posterior genera una revisión `DRAFT` nueva, basada en la revisión vigente seleccionada.

# 2. Modelo de datos

## 2.1 `knowledge_articles`

Representa la identidad y ciclo de vida del artículo.

Campos objetivo:

- `id BIGINT UNSIGNED` PK;
- `article_number VARCHAR(30)` UNIQUE;
- `lifecycle_status ENUM('ACTIVE','ARCHIVED')`;
- `current_internal_revision_id BIGINT UNSIGNED NULL`;
- `current_public_revision_id BIGINT UNSIGNED NULL`;
- `created_by_user_id BIGINT UNSIGNED NULL`;
- `created_at DATETIME`;
- `updated_at DATETIME`;
- `archived_at DATETIME NULL`.

Reglas:

- `article_number` no cambia al crear revisiones;
- `ACTIVE` habilita uso normal;
- `ARCHIVED` excluye el artículo de sugerencias y autoservicio, pero conserva historial;
- los punteros internos/públicos deben referenciar revisiones del mismo `article_id`;
- archivar no elimina revisiones ni relaciones históricas.

## 2.2 `knowledge_revisions`

Representa cada versión del contenido.

Campos objetivo:

- `id BIGINT UNSIGNED` PK;
- `article_id BIGINT UNSIGNED NOT NULL`;
- `revision_number INT UNSIGNED NOT NULL`;
- `state ENUM('DRAFT','IN_REVIEW','PUBLISHED') NOT NULL`;
- `title VARCHAR(220) NOT NULL`;
- `summary TEXT NULL`;
- `content LONGTEXT NOT NULL`;
- `category_id BIGINT UNSIGNED NULL`;
- `based_on_revision_id BIGINT UNSIGNED NULL`;
- `created_by_user_id BIGINT UNSIGNED NULL`;
- `change_note VARCHAR(500) NULL`;
- `submitted_by_user_id BIGINT UNSIGNED NULL`;
- `submitted_at DATETIME NULL`;
- `reviewed_by_user_id BIGINT UNSIGNED NULL`;
- `reviewed_at DATETIME NULL`;
- `review_note VARCHAR(500) NULL`;
- `internal_published_by_user_id BIGINT UNSIGNED NULL`;
- `internal_published_at DATETIME NULL`;
- `public_published_by_user_id BIGINT UNSIGNED NULL`;
- `public_published_at DATETIME NULL`;
- `created_at DATETIME`;
- `updated_at DATETIME`.

Restricciones:

- `UNIQUE(article_id, revision_number)`;
- `revision_number` crece de forma monotónica por artículo;
- una revisión `PUBLISHED` no puede volver a `DRAFT` ni modificarse;
- devolver una revisión desde revisión administrativa significa cambiar `IN_REVIEW -> DRAFT` antes de haber sido publicada;
- una revisión puede estar publicada internamente y aún no haber sido publicada al autoservicio;
- `public_published_at` solo puede existir si la revisión ya fue publicada internamente.

## 2.3 `knowledge_article_sources`

Registra de dónde surgió un artículo sin depender únicamente de eventos de auditoría.

Campos objetivo:

- `id`;
- `article_id`;
- `source_type ENUM('TICKET','PROBLEM','MANUAL')`;
- `source_ticket_id NULL`;
- `source_problem_id NULL`;
- `created_by_user_id NULL`;
- `created_at`.

Reglas:

- `TICKET` exige `source_ticket_id`;
- `PROBLEM` exige `source_problem_id`;
- `MANUAL` no exige entidad origen;
- un artículo puede tener más de un origen registrado si posteriormente se vincula a nuevas fuentes relevantes.

`problem_solutions` se conserva como relación funcional problema ↔ artículo.

## 2.4 `ticket_resolution_references`

Registra qué referencia se utilizó durante la atención/resolución de un ticket.

Campos objetivo:

- `id`;
- `ticket_id`;
- `reference_type ENUM('KNOWLEDGE','PROBLEM','TICKET')`;
- `knowledge_article_id NULL`;
- `knowledge_revision_id NULL`;
- `problem_id NULL`;
- `source_ticket_id NULL`;
- `used_by_user_id NULL`;
- `used_at`;
- `applied_root_cause TINYINT(1)`;
- `applied_solution TINYINT(1)`;
- `applied_prevention TINYINT(1)`.

La tabla utiliza FKs explícitas por tipo y no un único `reference_id` polimórfico.

Reglas:

- una fila representa una acción real `Usar como referencia`;
- el ticket no cambia de estado por esta acción;
- si la referencia es conocimiento se registra también la revisión exacta usada;
- los campos `applied_*` indican qué bloques se precargaron, no garantizan que el técnico los haya dejado sin cambios;
- puede haber más de una referencia utilizada en un mismo ticket.

## 2.5 `solution_suggestion_events`

Log append-only de interacción con sugerencias.

Campos objetivo:

- `id`;
- `ticket_id NULL`;
- `actor_user_id NULL`;
- `context ENUM('INTERNAL_TICKET','SELF_SERVICE')`;
- `event_type ENUM('SUGGESTED','OPENED','USED_REFERENCE')`;
- `reference_type ENUM('KNOWLEDGE','PROBLEM','TICKET')`;
- `knowledge_article_id NULL`;
- `knowledge_revision_id NULL`;
- `problem_id NULL`;
- `source_ticket_id NULL`;
- `rank_position SMALLINT UNSIGNED NULL`;
- `score SMALLINT UNSIGNED NULL`;
- `metadata_json LONGTEXT NULL`;
- `created_at`.

No duplicará `TICKET_RESOLVED` ni `TICKET_REOPENED`; esas señales se consultan desde `ticket_events`/tickets existentes para calcular efectividad.

# 3. Workflow editorial

## 3.1 Nuevo artículo

```text
Crear artículo
   -> REV 1 DRAFT
   -> enviar a revisión
   -> IN_REVIEW
   -> ADMIN/SEMIADMIN aprueba
   -> PUBLISHED interno
   -> current_internal_revision_id = REV 1
   -> acción separada "Publicar para autoservicio"
   -> current_public_revision_id = REV 1
```

## 3.2 Editar artículo publicado

```text
REV N PUBLISHED
   -> Editar
   -> REV N+1 DRAFT
   -> IN_REVIEW
   -> PUBLISHED interno
   -> puntero interno cambia a N+1
   -> puntero público NO cambia automáticamente
```

## 3.3 Devolver a borrador

Solo aplica a una revisión `IN_REVIEW` todavía no publicada.

`ADMIN/SEMIADMIN` puede devolverla a `DRAFT` con `review_note` explicando la corrección requerida.

No se crea una revisión adicional para una devolución editorial normal; se conserva la misma revisión porque aún no ha sido publicada.

## 3.4 Restaurar una versión anterior

Restaurar REV 1 cuando REV 3 está vigente crea:

```text
REV 4 DRAFT
based_on_revision_id = REV 1
contenido inicial = copia de REV 1
```

REV 3 continúa vigente hasta que REV 4 complete revisión y publicación.

No se cambia directamente `current_internal_revision_id` ni `current_public_revision_id` durante la restauración.

## 3.5 Archivar

Archivar cambia `knowledge_articles.lifecycle_status` a `ARCHIVED`.

Efectos:

- deja de aparecer en sugerencias normales;
- deja de aparecer en autoservicio;
- no elimina punteros históricos ni revisiones;
- `ADMIN/SEMIADMIN` conserva acceso al historial;
- una eventual recuperación debe crear/usar un flujo administrativo explícito, nunca reactivar silenciosamente desde una vista pública.

# 4. Permisos y gobierno

Se reemplaza el uso funcional del permiso genérico `knowledge.manage` por permisos especializados.

Permisos nuevos:

- `knowledge.view`;
- `knowledge.draft_manage`;
- `knowledge.review`;
- `knowledge.publish_internal`;
- `knowledge.publish_public`;
- `knowledge.history`;
- `knowledge.restore`.

Asignación base:

| Permiso | TECHNICIAN | SEMIADMIN | ADMIN |
|---|---:|---:|---:|
| `knowledge.view` | Sí | Sí | Sí |
| `knowledge.draft_manage` | Sí | Sí | Sí |
| `knowledge.review` | No | Sí | Sí |
| `knowledge.publish_internal` | No | Sí | Sí |
| `knowledge.publish_public` | No | Sí | Sí |
| `knowledge.history` | No | Sí | Sí |
| `knowledge.restore` | No | Sí | Sí |

`knowledge.manage` se conserva temporalmente durante migración por compatibilidad, pero el código nuevo no debe utilizarlo como autorización suficiente para publicación/restauración.

Los perfiles de consulta como Gerencia/Supervisión conservan acceso únicamente según los permisos de lectura que ya correspondan; Fase 9 no los convierte en operadores editoriales.

# 5. Servicios

## 5.1 `KnowledgeRevisionService`

Responsabilidad única: workflow y persistencia del núcleo versionado.

Operaciones conceptuales:

- `createArticle()`;
- `createDraftFromRevision()`;
- `updateDraft()`;
- `submitForReview()`;
- `returnToDraft()`;
- `publishInternal()`;
- `publishPublic()`;
- `restoreAsDraft()`;
- `archiveArticle()`;
- `getCurrentInternalRevision()`;
- `getCurrentPublicRevision()`;
- `getHistory()`.

Debe usar transacciones para operaciones que actualizan revisión + puntero + auditoría funcional.

## 5.2 `KnowledgeCandidateService`

Determina si un ticket resuelto merece sugerencia de convertirlo en conocimiento.

Entrada: ticket + resolución + relaciones conocidas.

Salida conceptual:

```text
eligible: true|false
reasons: [...]
quality_signals: [...]
```

No crea artículos automáticamente.

## 5.3 `KnowledgeReferenceService`

Responsable de `Usar como referencia`.

Debe:

1. validar que la referencia sea accesible al actor;
2. obtener los campos reutilizables;
3. registrar `ticket_resolution_references`;
4. registrar evento de ticket/auditoría;
5. devolver datos de causa/solución/prevención para precarga editable.

No cambia el estado del ticket.

## 5.4 `SolutionSuggestionService`

Se conserva y evoluciona.

Responsabilidad:

- encontrar y ordenar candidatos `ARTICLE / PROBLEM / TICKET`;
- devolver 3–5 sugerencias internas;
- devolver hasta 3 artículos públicos en autoservicio;
- no registrar por sí mismo resolución ni aplicar datos al ticket.

Para artículos internos debe consumir la revisión apuntada por `current_internal_revision_id`.

Para autoservicio debe consumir únicamente `current_public_revision_id` de artículos `ACTIVE`.

## 5.5 `KnowledgeMetricsService`

Responsable de registrar y consultar interacciones de sugerencias.

No debe duplicar estados del ticket.

# 6. Elegibilidad para crear conocimiento desde ticket

El sistema sugiere crear conocimiento únicamente si se cumplen todas estas condiciones:

1. ticket en `RESOLVED` o `CLOSED`;
2. existe resolución estructurada;
3. `solution_applied` contiene información útil y no vacía;
4. existe al menos una señal de reutilización.

Señales válidas:

- `ROOT_CAUSE_DOCUMENTED`: causa raíz no vacía;
- `KNOWN_PROBLEM`: ticket relacionado con problema conocido;
- `RECURRENT`: existe señal estructurada de recurrencia/problema recurrente;
- `REOPENED`: el ticket tuvo reapertura registrada.

La calidad mínima no se define únicamente por cantidad de caracteres. El servicio debe combinar presencia de solución con señales estructuradas.

La UI explica por qué se sugirió el artículo y muestra una acción como `Crear borrador`, nunca `Publicar`.

# 7. Sugerencias dentro del ticket

Ubicación: cerca de conversación/resolución, sin saturar la pantalla.

Cantidad: 3–5 resultados relevantes.

Tipos:

- artículo de conocimiento;
- problema conocido;
- ticket resuelto reutilizable.

Cada sugerencia debe ofrecer:

- número/identificador;
- título;
- resumen corto;
- acción `Ver`;
- acción `Usar como referencia` cuando el actor tenga permiso operativo.

No mostrar score técnico al usuario salvo que tenga valor operacional claro. El score puede permanecer como dato interno para ordenamiento/telemetría.

# 8. Acción `Usar como referencia`

No resuelve automáticamente.

Precarga, cuando existan:

- causa raíz;
- solución;
- prevención.

Todo permanece editable antes de guardar la resolución.

Debe registrar:

- ticket destino;
- tipo de referencia;
- identidad estable del objeto;
- revisión exacta para conocimiento;
- actor;
- fecha;
- campos precargados;
- evento/auditoría correspondiente.

# 9. Autoservicio

Mientras el solicitante crea un ticket se pueden mostrar hasta 3 artículos.

Reglas estrictas:

- solo artículos `ACTIVE`;
- solo `current_public_revision_id IS NOT NULL`;
- nunca borradores;
- nunca problemas internos;
- nunca tickets anteriores;
- nunca revisiones internas aún no aprobadas públicamente;
- nunca bloquear el envío del ticket.

La experiencia debe usar lenguaje simple, por ejemplo `Esto podría ayudarte`, no términos como `knowledge`, `visibility`, `workflow` o `scope`.

# 10. Métricas y efectividad

Eventos mínimos:

- `SUGGESTED`;
- `OPENED`;
- `USED_REFERENCE`.

Indicadores derivados:

- impresiones;
- tasa de apertura;
- tasa de uso;
- tickets resueltos después de usar referencia;
- tickets reabiertos después de usar referencia;
- artículos/revisiones más utilizados;
- artículos con alta utilización y alta reapertura;
- autoservicio abierto antes de crear ticket.

Definición base de efectividad interna:

una referencia es considerada efectiva cuando existe uso registrado, el ticket se resuelve posteriormente y no existe una reapertura posterior dentro del historial observado.

La métrica debe conservar la revisión exacta usada para evitar atribuir resultados históricos a contenido que cambió después.

# 11. Auditoría

Eventos funcionales mínimos:

- `KNOWLEDGE_ARTICLE_CREATED`;
- `KNOWLEDGE_REVISION_CREATED`;
- `KNOWLEDGE_REVISION_UPDATED`;
- `KNOWLEDGE_SUBMITTED_REVIEW`;
- `KNOWLEDGE_RETURNED_DRAFT`;
- `KNOWLEDGE_PUBLISHED_INTERNAL`;
- `KNOWLEDGE_PUBLISHED_PUBLIC`;
- `KNOWLEDGE_REVISION_RESTORED`;
- `KNOWLEDGE_ARCHIVED`;
- `KNOWLEDGE_REFERENCE_USED`;
- `KNOWLEDGE_CREATE_SUGGESTED`.

`Audit::log()` continúa como auditoría administrativa/técnica.

Las relaciones estructuradas y tablas funcionales son la fuente de verdad para versiones/uso; `audit_logs` no sustituye esas relaciones.

# 12. Historial y comparación

`ADMIN/SEMIADMIN` pueden consultar historial completo.

La pantalla de historial debe mostrar por revisión:

- número de revisión;
- estado;
- autor;
- fechas de creación/envío/revisión/publicación;
- nota de cambio;
- publicación interna/pública;
- revisión base.

Comparación:

- permitir elegir dos revisiones del mismo artículo;
- comparar título, resumen, categoría y contenido;
- destacar cambios de forma legible;
- no editar desde la vista comparativa.

`TECHNICIAN` trabaja con la versión interna vigente y borradores que pueda editar según permiso, sin exponerle controles administrativos de restauración/publicación.

# 13. Migración controlada

## 13.1 Estrategia

Migración aditiva e idempotente, validada primero en PC TEST.

Orden recomendado:

1. crear `knowledge_revisions` y tablas auxiliares;
2. agregar columnas nuevas de identidad/punteros a `knowledge_articles`;
3. crear una REV 1 por cada artículo existente;
4. mapear estado/visibilidad actuales a punteros internos/públicos;
5. validar conteos y contenido;
6. adaptar lectura de aplicación;
7. adaptar escritura/workflow;
8. mantener campos legacy temporalmente como compatibilidad/read-only;
9. no eliminar columnas legacy en Fase 9.

La circularidad FK `knowledge_articles -> knowledge_revisions -> knowledge_articles` se resuelve creando primero revisiones con FK hacia artículos y agregando después los punteros/FKs de `knowledge_articles` mediante `ALTER TABLE`.

## 13.2 Mapeo legacy

### `DRAFT`

- artículo `ACTIVE`;
- REV 1 `DRAFT`;
- sin puntero interno/público hasta publicación.

### `PUBLISHED + INTERNAL`

- artículo `ACTIVE`;
- REV 1 `PUBLISHED`;
- `current_internal_revision_id = REV 1`;
- `current_public_revision_id = NULL`.

### `PUBLISHED + PUBLIC`

- artículo `ACTIVE`;
- REV 1 `PUBLISHED`;
- `current_internal_revision_id = REV 1`;
- `current_public_revision_id = REV 1`.

### `ARCHIVED`

- artículo `ARCHIVED`;
- REV 1 conserva el contenido legacy;
- no participa en sugerencias/autoservicio;
- se preservan fechas y metadatos disponibles.

## 13.3 Gate de datos

Antes y después deben cuadrar:

- cantidad total de artículos;
- `article_number`;
- título;
- resumen;
- contenido;
- categoría;
- autor;
- estado legacy;
- visibilidad legacy;
- fecha de publicación;
- relaciones `problem_solutions`.

Validaciones obligatorias:

- 100 % de legacy `PUBLISHED` debe tener revisión publicada;
- 100 % de legacy `PUBLISHED + INTERNAL/PUBLIC` debe tener puntero interno;
- 100 % de legacy `PUBLISHED + PUBLIC` debe tener puntero público;
- ningún artículo debe perder `article_number`;
- ningún `problem_solutions.article_id` debe quedar huérfano.

# 14. Compatibilidad

Durante Fase 9:

- no se eliminan columnas legacy de `knowledge_articles`;
- el nuevo código deja de tratarlas como fuente principal de contenido;
- se documenta claramente qué campos quedan deprecated;
- rollback de aplicación debe ser posible mientras no se eliminen esos campos;
- cualquier sincronización temporal de campos legacy, si fuera necesaria para rollback, debe quedar encapsulada y probada, no dispersa en controladores.

# 15. UI y pulido transversal aplicable a Fase 9

Se aplican los criterios de simplificación acordados sin convertir Fase 9 en un rediseño global.

## 15.1 Jerarquía

- una acción principal visible;
- máximo 2–3 acciones secundarias;
- resto en `Más acciones` cuando corresponda;
- evitar badges para datos que pueden ser texto normal;
- no repetir estado/visibilidad en múltiples zonas;
- usar ancho de pantalla de forma eficiente sin estirar contenido textual excesivamente.

## 15.2 Lenguaje

Evitar tecnicismos visibles como:

- `workflow`;
- `visibility`;
- `scope`;
- `reusable`;
- `internal` como término técnico cuando pueda decirse `Solo equipo de soporte` o `Uso interno`;
- códigos sin contexto.

Preferir:

- `Borrador`;
- `En revisión`;
- `Publicado para soporte`;
- `Disponible para solicitantes`;
- `Historial`;
- `Usar como referencia`;
- `Crear borrador`.

## 15.3 Responsive

Las vistas tocadas por Fase 9 deben validarse en:

- 1920x1080;
- 1366x768;
- iPad horizontal;
- iPad vertical;
- móvil.

En iPad no se debe degradar automáticamente a una composición móvil si existe ancho suficiente.

## 15.4 Ayuda

La pantalla principal se mantiene simple.

El tutorial/manual puede explicar en profundidad:

- crear borrador;
- enviar a revisión;
- publicar interno;
- publicar para autoservicio;
- comparar versiones;
- restaurar;
- usar referencias.

# 16. Componentes afectados previstos

Sin fijar aún una lista cerrada de archivos de implementación, el plan deberá contemplar al menos:

- `app/Controllers/KnowledgeController.php`;
- controladores/rutas de ticket donde se integren sugerencias y referencias;
- `app/Services/SolutionSuggestionService.php`;
- nuevos servicios de Fase 9;
- vistas `app/Views/knowledge/*`;
- vista interna de ticket;
- formulario público/autoservicio únicamente para el bloque de sugerencias;
- `database/INSTALAR.sql`;
- migración incremental de Fase 9;
- verificador SQL de Fase 9;
- pruebas unitarias/regresión existentes y nuevas;
- `VALIDAR_FASE9.bat`;
- CI si corresponde;
- `CHANGELOG.md` al implementar.

# 17. Manejo de errores y concurrencia

Reglas:

- publicar una revisión inexistente o de otro artículo -> rechazar;
- editar una revisión `PUBLISHED` -> rechazar y obligar a crear borrador nuevo;
- publicar públicamente una revisión no publicada internamente -> rechazar;
- restaurar revisión de otro artículo -> rechazar;
- usar como referencia un objeto no visible para el actor -> rechazar;
- crear dos números de revisión simultáneos -> proteger mediante transacción/bloqueo o estrategia equivalente;
- mover puntero interno/público -> operación transaccional;
- errores SQL no se muestran al usuario;
- toda autorización se valida en backend, no mediante botones ocultos.

# 18. Estrategia TDD

La implementación deberá seguir Red -> Green -> Refactor.

Orden de cobertura recomendado:

1. migración/backfill;
2. creación de artículo y REV 1;
3. edición de borrador;
4. envío/rechazo/aprobación;
5. publicación interna;
6. publicación pública independiente;
7. edición de publicado crea nueva revisión;
8. restauración crea nueva revisión;
9. permisos por rol;
10. sugerencias internas;
11. `Usar como referencia`;
12. sugerencia de creación desde ticket resuelto;
13. autoservicio público;
14. métricas/eventos;
15. búsqueda/listados;
16. regresión de `problem_solutions`;
17. UI/responsive/seguridad.

# 19. Pruebas obligatorias

## Datos/migración

- migración idempotente;
- backfill 1:1 de artículos legacy;
- punteros correctos según estado/visibilidad;
- sin pérdida de contenido;
- `problem_solutions` intacto.

## Workflow

- TECHNICIAN crea borrador;
- TECHNICIAN no publica;
- SEMIADMIN/ADMIN revisa;
- publicación interna no expone autoservicio;
- publicación pública exige revisión publicada internamente;
- editar publicado crea revisión nueva;
- restaurar crea revisión nueva;
- historial conserva versiones anteriores.

## Referencias

- artículo -> precarga campos existentes;
- problema -> precarga campos existentes;
- ticket resuelto -> precarga solución reutilizable;
- no cambia estado del ticket;
- relación estructurada queda registrada;
- revisión exacta queda registrada.

## Sugerencias

- devuelve máximo configurado;
- artículos internos usan puntero interno;
- autoservicio usa solo puntero público;
- borradores no aparecen al solicitante;
- artículos archivados no aparecen;
- score/ranking no rompe orden actual de problema/ticket.

## Seguridad

- REQUESTER no ve borradores ni historial administrativo;
- TECHNICIAN no publica ni restaura;
- GERENCIA/SUPERVISIÓN no adquieren acciones operativas por error;
- proveedor/colaborador externo no obtiene acceso a conocimiento interno;
- endpoints rechazan llamadas directas sin permiso.

## Visual

- claro/oscuro;
- 1920x1080;
- 1366x768;
- iPad horizontal;
- iPad vertical;
- móvil;
- botonera sin redundancia;
- copy sin tecnicismos innecesarios.

# 20. Gate final de Fase 9

Antes de considerar cerrada la fase deben cumplirse todos estos puntos:

1. migración/verificación GREEN en PC TEST;
2. suite TDD de Fase 9 GREEN;
3. regresiones existentes GREEN;
4. `VALIDAR_FASE9.bat` GREEN;
5. permisos validados por backend;
6. autoservicio no expone contenido interno;
7. historial/restauración comprobados;
8. métricas registran revisión exacta;
9. pruebas responsive principales completadas;
10. log de continuidad actualizado;
11. CI remoto GREEN si el flujo vigente lo exige;
12. producción permanece sin cambios hasta aprobación explícita posterior.

# 21. Criterios de aceptación

Fase 9 se considera funcionalmente lograda cuando:

- `KB-2026-0001` conserva identidad a través de múltiples revisiones;
- un técnico puede mejorar conocimiento sin capacidad de publicación;
- una revisión publicada nunca se sobrescribe;
- una publicación interna nueva no cambia automáticamente el autoservicio;
- un administrador puede publicar luego esa revisión para solicitantes;
- un ticket puede reutilizar artículo/problema/ticket con trazabilidad estructurada;
- el técnico puede modificar los campos precargados antes de resolver;
- el sistema sugiere crear borrador solo para tickets elegibles;
- el solicitante puede recibir artículos útiles sin ser obligado a usarlos;
- el historial permite comparar y restaurar sin destruir versiones;
- las métricas permiten vincular uso con resolución/reapertura;
- ninguna relación legacy relevante queda rota;
- la experiencia modificada mantiene la línea visual y criterios de simplificación del Helpdesk.

# 22. Restricciones operativas

- trabajar únicamente sobre `main`;
- no desplegar producción durante diseño/plan/TDD en PC TEST;
- no ejecutar migraciones en producción sin gate previo;
- no introducir React, Vue, Angular, WebSockets ni frameworks grandes;
- mantener PHP + JS + CSS actuales;
- no eliminar columnas legacy en Fase 9;
- no crear publicación automática desde tickets;
- no saltar revisión administrativa;
- mantener log específico de continuidad de Fase 9.

## Estado al cerrar esta spec

Diseño arquitectónico: aprobado.

Spec: escrita para revisión del usuario.

Implementación: no iniciada.

Siguiente gate: aprobación explícita de esta spec antes de redactar el plan de implementación TDD.
