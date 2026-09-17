# Helpdesk Carrousel — Fase 9: Conocimiento — Log de continuidad

Fecha de inicio: 2026-09-16
Rama única: `main`
Estado: Tasks 1–12 implementadas; compatibilidad transversal cerrada; Task 13 PC TEST pendiente; Task 14 preparado

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

Backlog registrado en:

`docs/superpowers/logs/pulido-operativo-backlog.md`

Commit:

- `bab293e` — `docs: registrar backlog transversal de pulido`

## Spec

Archivo:

`docs/superpowers/specs/2026-09-16-fase9-conocimiento-versionado-design.md`

Commit de creación:

- `8eac3a9` — `docs: definir arquitectura fase 9 conocimiento`

Estado:

- ✅ aprobada por el usuario el 2026-09-16.

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

## Plan de implementación TDD

Archivo:

`docs/superpowers/plans/2026-09-16-fase9-conocimiento-versionado-implementation.md`

Commit de creación:

- `9c4eae8` — `docs: planificar implementacion TDD fase 9 conocimiento`

El plan queda dividido en 14 tareas ejecutables:

1. esquema y migración aditiva;
2. núcleo `KnowledgeRevisionService`;
3. permisos editoriales/publicación;
4. refactor de `KnowledgeController` y rutas;
5. lecturas/listados/UI sobre revisiones;
6. historial, comparación y restauración;
7. candidatos desde tickets resueltos;
8. `Usar como referencia` y trazabilidad;
9. evolución de `SolutionSuggestionService`;
10. autoservicio público;
11. métricas y efectividad;
12. manual/tutorial + pulido visual Fase 9;
13. migración y verificación real en PC TEST;
14. CI, `VALIDAR_FASE9.bat`, changelog y cierre.

Cada tarea incluye ciclo TDD: prueba en rojo -> implementación mínima -> prueba verde -> commit.

## Progreso de implementación — 2026-09-17

### Task 1 — Esquema y migración
Estado: ✅ código/esquema preparado; ⏳ ejecución SQL pendiente en PC TEST.

Implementado:
- `tests/phase9_knowledge_schema_regression.php`;
- `database/MIGRAR_FASE9_CONOCIMIENTO_20260916.sql`;
- `database/VERIFICAR_FASE9_CONOCIMIENTO_20260916.sql`;
- esquema canónico actualizado en `database/INSTALAR.sql`;
- verificador limpio actualizado en `database/VERIFICAR_INSTALACION.sql`;
- FKs de punteros interno/público alineadas entre instalación limpia y migración;
- permisos editoriales incluidos.

Validación estática del contrato: GREEN.
No se ha ejecutado todavía la migración contra una BD real.

### Task 2 — KnowledgeRevisionService
Estado: ✅ núcleo implementado; ⏳ operaciones PDO pendientes de integración PC TEST.

Archivos:
- `tests/phase9_knowledge_revision_service_regression.php`;
- `app/Services/KnowledgeRevisionService.php`.

Incluye:
- creación de artículo + REV 1;
- creación de nueva revisión desde versión previa;
- actualización solo de DRAFT;
- DRAFT -> IN_REVIEW;
- IN_REVIEW -> DRAFT;
- IN_REVIEW -> PUBLISHED;
- publicación interna;
- publicación pública separada;
- restauración como revisión nueva;
- archivo de artículo;
- numeración monotónica.

Las reglas puras de transición/numeración fueron ejecutadas con PHP CLI y quedaron GREEN.

### Task 3 — Permisos editoriales
Estado: ✅ contrato preparado.

Archivo:
- `tests/phase9_knowledge_permissions_regression.php`.

Resultado:
- TECHNICIAN: `knowledge.view` + `knowledge.draft_manage`;
- TECHNICIAN no recibe review/publicación/historial/restauración;
- SEMIADMIN recibe gobierno editorial completo;
- ADMIN enlaza permisos nuevos en migración.

Validación estática: GREEN.

### Task 4 — KnowledgeController versionado
Estado: ✅ refactor de escrituras/rutas preparado.

Archivos:
- `tests/phase9_knowledge_workflow_controller_regression.php`;
- `app/Controllers/KnowledgeController.php`;
- `public/index.php`.

Rutas nuevas:
- `POST /knowledge/submit-review`;
- `POST /knowledge/return-draft`;
- `POST /knowledge/publish-internal`;
- `POST /knowledge/publish-public`;
- `POST /knowledge/restore`.

El controlador:
- delega escrituras a `KnowledgeRevisionService`;
- no sobrescribe directamente contenido publicado;
- no publica mediante UPDATE directo;
- mantiene `/knowledge/publish` solo como compatibilidad, pasando por permiso específico.

Validación contractual: GREEN.

### Tasks 5–12 — Integración funcional y pulido
Estado: ✅ implementadas a nivel de repositorio; ⏳ validación integral pendiente en PC TEST.

#### Task 5 — Lectura/UI versionada
- lectura interna mediante `current_internal_revision_id`;
- lectura para solicitantes mediante `current_public_revision_id`;
- borradores/revisión visibles solo para perfiles editoriales;
- formulario sin selector legacy de visibilidad;
- copy: `Borrador`, `En revisión`, `Publicado para soporte`, `Disponible para solicitantes`;
- acciones secundarias agrupadas en `Más acciones`.

#### Task 6 — Historial, comparación y restauración
- rutas `/knowledge/history` y `/knowledge/compare`;
- comparación de Título, Resumen, Contenido y Categoría;
- restauración crea revisión borrador nueva;
- comparar no muta revisiones ni punteros.

#### Task 7 — Candidatos a conocimiento
- `KnowledgeCandidateService`;
- solo casos `RESOLVED/CLOSED`;
- exige solución útil + contexto estructurado;
- señales: causa raíz, problema conocido, recurrencia o reapertura;
- CTA `Crear borrador` nunca publica automáticamente.

#### Task 8 — Usar como referencia
- `KnowledgeReferenceService`;
- soporta conocimiento, problema conocido y ticket resuelto;
- registra `ticket_resolution_references`;
- registra evento de ticket por tipo de referencia;
- precarga causa, solución y prevención;
- el contenido permanece editable;
- usar referencia no cambia el estado del ticket.

#### Task 9 — Sugerencias internas
- `SolutionSuggestionService` usa revisión interna vigente;
- solo artículos activos;
- conserva problemas conocidos y tickets resueltos como fuentes;
- no muestra score numérico crudo;
- ofrece `Usar como referencia`.

#### Task 10 — Autoservicio
- máximo 3 artículos;
- solo `current_public_revision_id`;
- bloque dinámico `Esto podría ayudarte`;
- el solicitante puede abrir contenido público sin abandonar el formulario;
- `Enviar solicitud` siempre permanece disponible.

#### Task 11 — Métricas
- `KnowledgeMetricsService`;
- eventos append-only `SUGGESTED`, `OPENED`, `USED_REFERENCE`;
- impresiones internas y públicas registradas;
- aperturas internas y públicas registradas;
- uso de referencia registrado;
- efectividad = referencia usada -> resolución posterior -> sin reapertura posterior;
- no se duplican `RESOLVED/REOPENED` como eventos de métrica.

#### Task 12 — Manual/tutorial
- manual segmentado por capacidades del perfil;
- solicitante solo recibe ayuda de autoservicio;
- técnico recibe borradores, envío a revisión y uso como referencia;
- Admin/Semiadmin reciben revisión, publicación, historial y restauración;
- tutorial actualizado con el workflow versionado;
- eliminada terminología legacy de visibilidad en la ayuda de conocimiento.

Validaciones contractuales realizadas mediante lectura del repositorio: GREEN.
Pruebas que requieren entorno completo, MariaDB, CSS/runtime y navegación real: pendientes de PC TEST/gate final.

## Compatibilidad transversal — 2026-09-17

Durante el pre-gate se detectaron lecturas legacy fuera del módulo principal de Conocimiento.

Corregido:
- `SearchController`: búsqueda de artículos desde revisiones, con puntero interno/público según perfil;
- `app/Views/search/index.php`: estados editoriales legibles y eliminación de `INTERNAL/PUBLIC` visible;
- `ProblemController`: relaciones Problema ↔ Artículo usan identidad estable + revisiones;
- `app/Views/problems/show.php`: copy versionado y sin visibilidad legacy;
- `DashboardController`: contador de trabajo editorial usa `knowledge_revisions.state IN ('DRAFT','IN_REVIEW')`;
- eliminado uso funcional de `knowledge.manage` en estas lecturas;
- nueva regresión `tests/phase9_knowledge_legacy_read_regression.php`;
- regresión agregada a CI, `VALIDAR_FASE9.bat` y `phase9_closeout_regression.php`.

Validación contractual de compatibilidad transversal: GREEN.

## Task 13 — Validación real de migración en PC TEST
Estado: 🟡 migración/verificador GREEN; faltan gate local y smoke funcional/visual.

Evidencia PC TEST — 2026-09-17:
- backup previo: `carrousel_helpdesk_PRE_FASE9_20260917.sql`;
- tamaño backup: 266665 bytes;
- baseline: 1 artículo `PUBLISHED + PUBLIC`;
- baseline `problem_solutions`: 1;
- primera migración: OK;
- artículo `KB-2026-0001` migrado a REV 1 `PUBLISHED`;
- `current_internal_revision_id = 1`;
- `current_public_revision_id = 1`;
- fechas interna/pública preservadas;
- `knowledge_article_sources`: 1 fuente `PROBLEM`;
- segunda migración: OK;
- `REVISIONES_TOTALES = 1` después de ejecutar dos veces;
- `duplicate_revision_numbers = 0`;
- `published_without_internal_pointer = 0`;
- `public_without_public_pointer = 0`;
- `problem_solutions = 1`;
- `permissions_phase9 = 6`;
- `technician_forbidden_publish_permissions = 0`;
- `semiadmin_expected_permissions = 6`;
- `fase9_schema_gate = PASS`.

Idempotencia y preservación de relaciones: ✅ confirmadas.

## Task 14 — Gate/CI/documentación
Estado: 🟡 preparado; CI remoto y PC TEST todavía pendientes de confirmación final.

Preparado:
- `VALIDAR_FASE9.bat`;
- regresiones Fase 9 integradas a `.github/workflows/helpdesk-ci.yml`;
- instalación limpia actualizada a 43 tablas;
- `tests/phase9_closeout_regression.php`;
- `CHANGELOG.md` actualizado;
- producción marcada explícitamente como no migrada.

## Estado actual exacto

- Diseño arquitectónico: ✅ aprobado.
- Spec formal: ✅ escrita y aprobada.
- Plan de implementación TDD: ✅ escrito y ejecutado hasta Task 12.
- Tasks 1–12: ✅ implementadas a nivel de repositorio.
- Compatibilidad transversal: ✅ buscador, Problemas y Dashboard migrados al modelo versionado.
- Regresiones contractuales Fase 9: ✅ preparadas e integradas al gate.
- Task 13 — migración/verificación PC TEST: 🟡 migración/verificador GREEN; faltan gate local y smoke funcional/visual.
- Task 14 — CI/gate/cierre: 🟡 gate local PC TEST GREEN; faltan smoke funcional/visual, CI remoto y aprobación final.
- Migración Fase 9: ✅ ejecutada dos veces en PC TEST; idempotencia y verificador GREEN.
- BD PC TEST: ✅ migrada a Fase 9 y verificada; aún pendiente smoke funcional/visual.
- Producción: sin cambios de Fase 9.

## Próximo paso exacto

Ejecutar **Task 13 en PC TEST**, nunca en producción:

1. sincronizar `main` en la PC TEST;
2. tomar evidencia/conteos previos de `knowledge_articles` y `problem_solutions`;
3. ejecutar `database/MIGRAR_FASE9_CONOCIMIENTO_20260916.sql`;
4. ejecutar `database/VERIFICAR_FASE9_CONOCIMIENTO_20260916.sql`;
5. repetir la migración para comprobar idempotencia;
6. volver a ejecutar el verificador;
7. correr `VALIDAR_FASE9.bat`;
8. probar manualmente creación, revisión, publicación interna/pública, restauración, referencias y autoservicio;
9. validar PC, iPad y móvil;
10. solo con todo GREEN considerar el cierre de Task 14.

No ejecutar todavía la migración contra producción.

## Gate local Fase 9 — GREEN · 2026-09-17

Evidencia ejecutada en PC TEST:
- `VALIDAR_FASE9.bat` completó sin fallos;
- esquema/migración: GREEN;
- núcleo de revisiones: GREEN;
- permisos editoriales: GREEN;
- workflow de conocimiento: GREEN;
- candidatos a conocimiento: GREEN;
- referencias de solución: GREEN;
- sugerencias versionadas: GREEN;
- autoservicio público: GREEN;
- historial/comparación: GREEN;
- métricas de conocimiento: GREEN;
- UI/manual/tutorial: GREEN;
- lecturas transversales: GREEN;
- cierre documental/CI: GREEN;
- checks estáticos: GREEN;
- calidad de rutas/vistas/CSS: GREEN;
- smoke XLSX: GREEN;
- `git diff --check`: GREEN;
- working tree: limpio.

Resultado final:
`[OK] GATE FINAL FASE 9 COMPLETADO SIN FALLOS`

Pendiente antes de producción:
1. smoke funcional de navegador en PC TEST;
2. matriz responsive/seguridad PC + iPad + móvil;
3. CI remoto GREEN;
4. aprobación explícita;
5. despliegue/migración en producción solo después de lo anterior.

Producción continúa sin cambios de Fase 9.

## Regla para futuros chats

Si se cambia de chat, continuar desde este archivo, la spec y el plan de implementación.

No regresar a ramas antiguas ni a `v2-rebuild`.

La rama vigente de trabajo para Fase 9 es `main`.
