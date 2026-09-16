# Helpdesk Carrousel — Fase 9: Conocimiento — Log de continuidad

Fecha de inicio: 2026-09-16
Rama única: `main`
Estado: diseño aprobado; spec escrita; implementación no iniciada

## Punto de partida

Fase 8 quedó cerrada e integrada.

Commit de cierre documental de Fase 8:

- `aac15f3` — `docs: registrar ci final exitoso fase 8`

Estado recibido para iniciar Fase 9:

- `main` limpio y sincronizado con `origin/main`;
- `fase8-calidad-proveedor` integrada y eliminada local/remotamente;
- `VALIDAR_FASE8.bat` GREEN;
- producción no debe tocarse durante diseño/plan/pruebas de Fase 9.

## Objetivo de Fase 9

Convertir Conocimiento en un núcleo versionado y reutilizable sin reconstruir infraestructura ya existente.

Debe cubrir:

- identidad estable por artículo;
- revisiones inmutables una vez publicadas;
- workflow borrador -> revisión -> publicación interna -> publicación pública opcional;
- creación/mejora de borradores por técnicos;
- publicación solo por ADMIN/SEMIADMIN;
- sugerencias dentro del ticket;
- sugerencia de creación desde tickets elegibles;
- `Usar como referencia` con precarga editable;
- trazabilidad ticket ↔ referencia;
- métricas de uso/efectividad;
- historial, comparación y restauración segura;
- autoservicio público sin bloquear creación de ticket;
- migración controlada validada primero en PC TEST.

## Decisiones aprobadas antes de la spec

1. Alcance: interno + autoservicio, comenzando por conocimiento interno.
2. El sistema sugiere crear conocimiento desde ticket, pero nunca publica automáticamente.
3. La sugerencia aparece solo para ticket resuelto, con solución suficientemente documentada y al menos una señal de reutilización.
4. Señales de reutilización: causa raíz, problema conocido, recurrencia o reapertura.
5. `TECHNICIAN` puede crear/mejorar borradores.
6. `ADMIN` / `SEMIADMIN` revisan y publican.
7. Un artículo se publica primero como interno.
8. Hacerlo público es una segunda acción separada.
9. Dentro del ticket se muestran 3–5 sugerencias relevantes.
10. Se reutiliza `SolutionSuggestionService`.
11. `Usar como referencia` no resuelve automáticamente.
12. La referencia precarga causa, solución y prevención cuando existan; todo queda editable.
13. Se registra qué artículo/problema/ticket fue utilizado.
14. La trazabilidad es estructurada y además auditable.
15. Se medirán sugerido, abierto, usado como referencia, resolución, reapertura y efectividad.
16. Editar un artículo publicado crea una nueva revisión borrador.
17. ADMIN/SEMIADMIN pueden revisar historial completo, comparar y restaurar.
18. Restaurar una versión antigua crea una nueva revisión borrador.
19. Autoservicio muestra hasta 3 artículos públicos publicados y nunca bloquea la creación del ticket.
20. Arquitectura elegida: Núcleo de conocimiento versionado.

## Estado técnico encontrado

El repositorio ya contiene:

- `KnowledgeController`;
- vistas `knowledge/*`;
- `SolutionSuggestionService`;
- estados actuales `DRAFT / PUBLISHED / ARCHIVED`;
- visibilidad `INTERNAL / PUBLIC`;
- creación desde ticket/problema;
- búsqueda y categorías;
- auditoría;
- `problem_solutions`.

Problema principal:

`KnowledgeController::update()` sobrescribe directamente el contenido de `knowledge_articles`, por lo que el modelo actual no puede cumplir la inmutabilidad/versionado aprobado.

También se confirmó que actualmente:

- `TECHNICIAN` tiene `knowledge.view`;
- `knowledge.manage` concentra edición/publicación administrativa;
- Fase 9 necesita permisos editoriales separados.

## Arquitectura aprobada

### Identidad y versiones

`knowledge_articles` queda como identidad estable.

Nueva tabla principal de contenido:

- `knowledge_revisions`.

Cada artículo mantiene dos punteros independientes:

- `current_internal_revision_id`;
- `current_public_revision_id`.

Esto permite que soporte use una revisión nueva internamente sin exponerla automáticamente al solicitante.

### Tablas nuevas previstas

- `knowledge_revisions`;
- `knowledge_article_sources`;
- `ticket_resolution_references`;
- `solution_suggestion_events`.

### Servicios previstos

- `KnowledgeRevisionService`;
- `KnowledgeCandidateService`;
- `KnowledgeReferenceService`;
- evolución de `SolutionSuggestionService`;
- `KnowledgeMetricsService`.

### Permisos previstos

- `knowledge.view`;
- `knowledge.draft_manage`;
- `knowledge.review`;
- `knowledge.publish_internal`;
- `knowledge.publish_public`;
- `knowledge.history`;
- `knowledge.restore`.

`knowledge.manage` queda temporalmente por compatibilidad, pero no será autorización suficiente para las acciones nuevas sensibles.

## Migración prevista

Estrategia aditiva e idempotente.

No eliminar columnas legacy en Fase 9.

Backfill:

- cada artículo actual genera REV 1;
- `PUBLISHED + INTERNAL` -> puntero interno;
- `PUBLISHED + PUBLIC` -> puntero interno + público;
- `DRAFT` -> REV 1 borrador;
- `ARCHIVED` -> identidad archivada con contenido preservado.

La migración se probará primero en PC TEST con verificación de conteos, contenido, estado, visibilidad, fechas y `problem_solutions`.

## Pulido transversal incorporado

Se recibió un prompt previo de pulido operativo que incluía simplificación global, copy, responsive, conversación, correo, manual, dashboard y otras mejoras.

Para evitar mezclar fases:

- Fase 9 solo implementará los elementos de ese backlog que afecten directamente a Conocimiento, sugerencias y autoservicio;
- la UI modificada seguirá la regla `pantalla principal simple; ayuda profunda`;
- se evitarán tecnicismos visibles innecesarios;
- se reducirá botonera/badges redundantes en las vistas tocadas;
- se validará responsive en PC, iPad y móvil;
- correo, chat global, dashboard completo, formulario público global y manual global permanecen como backlog transversal para fases/pulidos posteriores.

## Spec

Archivo:

`docs/superpowers/specs/2026-09-16-fase9-conocimiento-versionado-design.md`

Commit de creación:

- `8eac3a9` — `docs: definir arquitectura fase 9 conocimiento`

La spec contiene:

- alcance y fuera de alcance;
- modelo de datos;
- workflow editorial;
- permisos;
- servicios;
- elegibilidad desde ticket;
- sugerencias internas;
- `Usar como referencia`;
- autoservicio;
- métricas;
- auditoría;
- historial/comparación/restauración;
- migración y compatibilidad;
- criterios UX aplicables;
- manejo de errores/concurrencia;
- estrategia TDD;
- pruebas obligatorias;
- gate final;
- criterios de aceptación.

## Estado actual exacto

- Diseño arquitectónico: ✅ aprobado.
- Spec formal: ✅ escrita.
- Log de continuidad: ✅ creado con este archivo.
- Plan de implementación: ⏸️ no iniciado.
- Código funcional Fase 9: ⏸️ no iniciado.
- Migración Fase 9: ⏸️ no creada.
- BD PC TEST: sin cambios de Fase 9.
- Producción: sin cambios.

## Próximo gate

El usuario debe revisar/aprobar la spec.

Después de aprobación:

1. redactar plan de implementación detallado;
2. dividir por tareas TDD;
3. definir orden de migración y verificadores;
4. implementar únicamente sobre `main`;
5. validar primero en PC TEST;
6. mantener este log actualizado durante toda la fase;
7. crear `VALIDAR_FASE9.bat` antes del cierre;
8. no tocar producción hasta gate final y aprobación explícita.

## Regla para futuros chats

Si se cambia de chat, continuar desde este archivo y desde la spec de Fase 9.

No regresar a ramas antiguas ni a `v2-rebuild`.

La rama vigente de trabajo para Fase 9 es `main`.
