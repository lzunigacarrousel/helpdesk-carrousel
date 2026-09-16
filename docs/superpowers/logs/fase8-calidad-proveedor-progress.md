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

### Task 5 — Informe de proveedores + valoración + XLSX 🚧 EN CURSO

Inicio TDD:
- creado `tests/phase8_provider_rating_report_regression.php`;
- commit RED: `8b74bfb test: definir informe de calidad de proveedores fase 8`.

RED confirmado en PC TEST:
- rama sincronizada y `working tree clean` antes de ejecutar;
- 21 validaciones fallaron, todas correspondientes a integración aún no implementada;
- 4 validaciones de `ProviderRatingService::providerSummary()` ya pasan por venir de Task 2.

GREEN preparado:
- creado `tools/apply_phase8_provider_rating_report.php`;
- commit del aplicador: `b144e23 tool: aplicar informe de calidad proveedores fase 8`;
- el aplicador es tolerante a CRLF y escribe LF para evitar repetir el blocker de Task 4;
- modifica solo código de servicio/controller/vista; no toca BD.

GREEN ejecutado en PC TEST:
- aplicador completó todos sus pasos en `[OK]`;
- sintaxis PHP de `ProviderParticipationService.php`, `ExternalReportController.php` y `external_report.php`: GREEN;
- `phase8_provider_rating_ui_regression.php`: GREEN;
- `phase8_provider_rating_controller_regression.php`: GREEN;
- `phase8_provider_rating_service_regression.php`: GREEN;
- `phase8_provider_cycle_identity_regression.php`: GREEN;
- `phase7_provider_filters_regression.php`: GREEN;
- `phase7_provider_participation_regression.php`: GREEN;
- `project_quality.php`: GREEN;
- `git diff --check` no reportó errores; solo advertencias LF/CRLF de Windows.

Blocker menor detectado después del GREEN:
- `phase8_provider_rating_report_regression.php` quedó con 1 única validación fallida: `Filtro Sin evaluar conserva ciclo no evaluado`;
- la validación anterior `Filtro Sin evaluar selecciona solo ciclos sin rating` sí pasó, confirmando que la lógica funcional del filtro es correcta;
- causa raíz: el test usa `($filtered[0]['provider_rating_score'] ?? 'x')===null`; en PHP el operador `??` devuelve el fallback también cuando la clave existe con valor `null`, por lo que esa aserción no puede confirmar un `null` legítimo;
- es un defecto del test, no de `ProviderParticipationService::applyFilters()`.

Siguiente acción exacta:
1. corregir únicamente la aserción del test usando `array_key_exists('provider_rating_score',$filtered[0]) && $filtered[0]['provider_rating_score']===null`;
2. sincronizar PC TEST;
3. reejecutar `phase8_provider_rating_report_regression.php` y gates de Task 5;
4. si todo queda GREEN, registrar cierre antes de consolidar los tres archivos funcionales y limpiar el aplicador temporal.

## Estado actual para retomar

- Tasks 1–4: cerradas.
- Task 5: funcionalmente implementada; un único blocker de aserción del test identificado.
- Cambios funcionales locales pendientes de commit en PC TEST: `ProviderParticipationService.php`, `ExternalReportController.php`, `external_report.php`.
- Rama de trabajo: `fase8-calidad-proveedor`.

## Próximas tareas del plan

- Task 5: corregir test, verificar GREEN completo, consolidar y limpiar aplicador temporal.
- Task 6+: endurecimiento/gates, CI y cierre documental según `docs/superpowers/plans/2026-09-15-fase8-calidad-proveedor-implementation.md`.
