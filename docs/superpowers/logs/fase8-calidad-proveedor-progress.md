# Log de continuidad — Fase 8 · Calidad IT → proveedor

**Rama:** `fase8-calidad-proveedor`
**Objetivo:** valoración interna 1–5 de IT por ciclo finalizado de participación de proveedor, con correcciones inmutables, captura dentro del ticket e informe de proveedores.
**BD:** 0 cambios estructurales previstos. Fuente de verdad: `ticket_events`.

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

### Task 4 — UI interna de calidad del proveedor 🚧 EN CURSO

Test RED creado: `tests/phase8_provider_rating_ui_regression.php`.

Arquitectura confirmada:
- `/tickets/view` entra por `TicketViewController`.
- Usuarios internos son delegados a `TicketController::show()`.
- Usuarios EXTERNAL renderizan `tickets/show_external.php` por separado.
- Por tanto ratings se cargan solo en `TicketController::show()` y `tickets/show.php`; `show_external.php` debe permanecer sin ratings.

#### Estado local al último corte

Se ejecutó `tools/apply_phase8_provider_rating_ui.php` en Windows.

Resultado:
- `[OK] TicketController importa servicios de calidad.`
- luego falló: `TicketController carga ciclos y valoraciones: esperaba 1 coincidencia y encontro 0.`
- `app/Controllers/TicketController.php` quedó modificado localmente solo con el import de `ProviderParticipationService` y `ProviderRatingService`.
- `app/Views/tickets/show.php` no fue modificado todavía.
- resto de regresiones Fase 8/Fase 7 y `project_quality.php` permanecieron verdes.

**Causa raíz:** el aplicador usa un ancla multilínea con `\n`, mientras la copia Windows puede usar CRLF (`\r\n`). El reemplazo de una sola línea funcionó; el ancla multilínea no.

**Siguiente acción:** hacer `tools/apply_phase8_provider_rating_ui.php` idempotente y tolerante a CRLF; después volver a ejecutarlo sin restaurar el import ya aplicado.

## Próximas tareas del plan

- Task 4: terminar UI interna y dejar pruebas verdes.
- Task 5: integrar rating en Informe de proveedores + filtro + resumen + XLSX.
- Task 6+: endurecimiento/gates, CI y cierre documental según plan `docs/superpowers/plans/2026-09-15-fase8-calidad-proveedor-implementation.md`.

## Regla de continuidad

Actualizar este archivo al cerrar cada task, al encontrar un blocker importante y antes de fusionar/eliminar la rama. No guardar secretos, credenciales ni datos sensibles en este log.
