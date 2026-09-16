# Log de continuidad — Fase 8 · Calidad IT → proveedor

**Rama:** `fase8-calidad-proveedor`
**Objetivo:** valoración interna 1–5 de IT por ciclo finalizado de participación de proveedor, con correcciones inmutables, captura dentro del ticket e informe de proveedores.
**BD:** 0 cambios estructurales previstos. Fuente de verdad: `ticket_events`.

## Regla operativa del log

Este archivo se actualiza obligatoriamente:
- después de cada cambio funcional relevante;
- después de cada RED/GREEN importante;
- al encontrar o resolver un blocker;
- al cerrar cada Task;
- antes de un commit de cierre importante;
- antes de cambiar de chat;
- antes de fusionar o eliminar la rama.

No guardar secretos, credenciales ni datos sensibles.

## Diseño aprobado

- Evaluación por ciclo exacto de participación.
- Solo ciclo terminado mediante `EXTERNAL_REVOKED` es evaluable.
- Escala: 1 Muy deficiente, 2 Deficiente, 3 Adecuado, 4 Bueno, 5 Excelente.
- Comentario obligatorio para 1–2 estrellas y para toda corrección.
- Evaluación opcional; no bloquea cierre ni flujo del ticket.
- Roles que pueden evaluar/corregir: `ADMIN`, `SEMIADMIN`, `TECHNICIAN`, siempre con scope válido.
- Proveedor y solicitante no ven score/comentario.
- Primera valoración: `PROVIDER_RATED`.
- Corrección: `PROVIDER_RATING_CORRECTED`.
- Correcciones son nuevos eventos; nunca se modifica/elimina una valoración anterior.
- Último evento válido del ciclo es la valoración vigente.
- Cada rating referencia `external_user_id` + `grant_event_id`.
- `Sin evaluar` no vale 0 ni entra al promedio.

## Estado de implementación

### Task 1 — Identidad exacta del ciclo ✅

Implementado en `ProviderParticipationService`:
- `grant_event_id`
- `revoke_event_id`
- `rowsForTicket()`
- cierre implícito por nuevo grant no inventa `revoke_event_id`

Pruebas Fase 8 y regresiones Fase 7: verdes.

### Task 2 — Dominio inmutable de valoración ✅

Creado `ProviderRatingService` con:
- `SCORE_LABELS`
- `validateInput()`
- `buildCurrentRatings()`
- `enrichCycles()`
- `providerSummary()`
- `ratingOptions()`

Pruebas verdes.

### Task 3 — Persistencia + autorización backend ✅

Implementado:
- `ratingEventsForTickets()`
- `enrichRows()`
- `rateCycle()`
- `correctCycle()`
- bloqueo `FOR UPDATE`
- solo INSERT de eventos; sin UPDATE/DELETE de valoraciones
- `ProviderRatingController`
- CSRF
- roles + `ScopeService::userCanAccessTicket()`
- auditoría
- rutas POST `/tickets/provider-rating` y `/tickets/provider-rating/correct`

`phase8_provider_rating_controller_regression.php`, dominio, identidad y `project_quality.php`: verdes.

### Task 4 — UI interna de calidad del proveedor ✅ CERRADA

Commit funcional:
- `67160ba ui: evaluar proveedores desde el ticket`

Arquitectura confirmada:
- `/tickets/view` entra por `TicketViewController`.
- usuarios internos son delegados a `TicketController::show()`.
- usuarios `EXTERNAL` renderizan `tickets/show_external.php` por separado.
- ratings se cargan solo en `TicketController::show()` y `tickets/show.php`.
- `show_external.php` permanece sin score, comentario ni bloque de calidad.

Implementado:
- `TicketController` carga `ProviderParticipationService` y `ProviderRatingService`;
- obtiene `rowsForTicket($id)` y los enriquece con valoraciones;
- expone `providerCycles`, `providerRatingLabels` y `canRateProviders`;
- bloque interno `#provider-quality`;
- ciclo activo no evaluable;
- primera evaluación para ciclo finalizado;
- corrección de valoración vigente;
- referencia exacta a `external_user_id`, `grant_event_id` y `corrected_rating_event_id`;
- muestra score, etiqueta, actor, fecha y comentario interno;
- comentario obligatorio visible para 1–2 estrellas y correcciones;
- no fuga a vista externa.

RED/GREEN:
- RED inicial: 23 fallos de UI interna inexistente; no fuga externa ya GREEN.
- GREEN final: `phase8_provider_rating_ui_regression.php` completamente GREEN.
- `phase8_provider_rating_controller_regression.php`: GREEN.
- `phase8_provider_rating_service_regression.php`: GREEN.
- `phase8_provider_cycle_identity_regression.php`: GREEN.
- regresión Fase 7 de proveedores: GREEN.
- `project_quality.php`: GREEN en verificación integral de Task 4.
- sintaxis PHP de controller y vista: GREEN.

Blocker CRLF/LF resuelto:
- primer aplicador falló por ancla multilínea LF sobre copia Windows CRLF;
- luego `git diff --check` detectó `^M` como trailing whitespace;
- se normalizaron `TicketController.php` y `show.php` a LF;
- `git diff --check` quedó limpio;
- advertencias `LF will be replaced by CRLF` son configuración local de Git, no fallos.

Cierre y limpieza:
- push de `67160ba` exitoso;
- rama local quedó sincronizada y limpia después del commit funcional;
- log de cierre registrado en `2fcf54d`;
- eliminado `tools/apply_phase8_provider_rating_ui.php` en `1bf5d8e`;
- eliminado `tools/normalize_phase8_task4_eol.php` en `e302a49`;
- no quedan herramientas temporales de Task 4 versionadas.

### Task 5 — Informe de proveedores + valoración + XLSX ✅ CERRADA

Inicio TDD:
- creado `tests/phase8_provider_rating_report_regression.php`;
- commit RED: `8b74bfb test: definir informe de calidad de proveedores fase 8`.

RED confirmado:
- 21 fallos de integración antes de implementar;
- `ProviderRatingService::providerSummary()` ya estaba GREEN por Task 2.

Implementado:
- `ProviderParticipationService::applyFilters()` soporta `rating=UNRATED|1|2|3|4|5`;
- `ExternalReportController` enriquece filas con `ProviderRatingService::enrichRows()` antes de filtrar;
- GET `rating` normalizado;
- se exponen `ratingOptions` y `providerRatingSummary`;
- vista agrega filtro Valoración, resumen de calidad y valoración vigente por ciclo;
- XLSX agrega valoración vigente, comentario interno y hoja de calidad por proveedor;
- 0 cambios de BD.

Blocker de test resuelto:
- una aserción de `null` usaba `??` y era inválida;
- corregida en `4ad3f8c test: corregir asercion de ciclo sin evaluar`;
- no se cambió lógica funcional ni BD.

GREEN final:
- `phase8_provider_rating_report_regression.php`: GREEN completo;
- `phase8_provider_rating_ui_regression.php`: GREEN;
- `phase8_provider_rating_controller_regression.php`: GREEN;
- `phase8_provider_rating_service_regression.php`: GREEN;
- `phase8_provider_cycle_identity_regression.php`: GREEN;
- `phase7_provider_filters_regression.php`: GREEN;
- `phase7_provider_participation_regression.php`: GREEN;
- `project_quality.php`: GREEN en verificación previa;
- `git diff --check`: limpio; solo advertencias LF/CRLF de Windows.

Commit funcional:
- `a8811c6 feat: integrar calidad de proveedores en informes`.

Estado después del commit:
- push exitoso a `origin/fase8-calidad-proveedor`;
- `git status`: `nothing to commit, working tree clean`.

Limpieza pendiente inmediata:
- eliminar `tools/apply_phase8_provider_rating_report.php`;
- registrar esa limpieza en este log;
- iniciar Task 6.

## Estado actual para retomar

- Tasks 1–5: cerradas funcionalmente.
- Task 5: pendiente solo limpieza del aplicador temporal remoto.
- Rama de trabajo: `fase8-calidad-proveedor`.

## Próximas tareas del plan

- Task 6: consolidar seguridad, historial y casos límite.
- Task 7+: CI/cierre documental y gate integral según `docs/superpowers/plans/2026-09-15-fase8-calidad-proveedor-implementation.md`.
