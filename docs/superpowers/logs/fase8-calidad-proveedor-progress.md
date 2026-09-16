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

### Task 4 — UI interna de calidad del proveedor 🚧 EN CURSO

Test RED creado: `tests/phase8_provider_rating_ui_regression.php`.

Arquitectura confirmada:
- `/tickets/view` entra por `TicketViewController`.
- Usuarios internos son delegados a `TicketController::show()`.
- Usuarios EXTERNAL renderizan `tickets/show_external.php` por separado.
- Ratings se cargan solo en `TicketController::show()` y `tickets/show.php`.
- `show_external.php` permanece sin score, comentario ni bloque de calidad.

#### Avance GREEN funcional

El aplicador corregido tolerante a CRLF se ejecutó correctamente:
- `[OK] TicketController importa servicios de calidad: ya aplicado.`
- `[OK] TicketController carga ciclos y valoraciones.`
- `[OK] TicketController expone calidad a la vista.`
- `[OK] Vista interna incorpora calidad del proveedor.`
- sintaxis PHP de controller y vista: OK.

`tests/phase8_provider_rating_ui_regression.php`: completamente GREEN.

También permanecen GREEN:
- `phase8_provider_rating_controller_regression.php`
- `phase8_provider_rating_service_regression.php`
- `phase8_provider_cycle_identity_regression.php`
- `phase7_provider_participation_regression.php`
- `project_quality.php`

La UI interna ya cubre:
- bloque `#provider-quality`;
- ciclo activo no evaluable;
- primera evaluación de ciclo finalizado;
- corrección de valoración existente;
- score, comentario, actor y fecha vigentes;
- referencia exacta a `external_user_id`, `grant_event_id` y rating vigente;
- regla visible de comentario obligatorio;
- no fuga a proveedor externo.

#### Blocker menor actual: normalización EOL

`git diff --check` detectó `trailing whitespace` en las líneas nuevas de `app/Views/tickets/show.php`, mostrando `^M` al final.

**Causa raíz:** el aplicador preservó CRLF de Windows al insertar el bloque, mientras el diff espera LF para las líneas versionadas. No es un fallo funcional ni de PHP; es únicamente normalización de finales de línea antes del commit.

**Estado local actual:**
- `app/Controllers/TicketController.php` modificado.
- `app/Views/tickets/show.php` modificado.
- no hay commit local todavía para Task 4.

**Corrección preparada:**
- creado `tools/normalize_phase8_task4_eol.php`;
- normaliza `TicketController.php` y `show.php` a LF;
- no toca BD;
- después debe repetirse `git diff --check` y los gates de Task 4.

## Próximas tareas del plan

- Task 4: ejecutar normalizador EOL, verificar nuevamente y cerrar commit.
- Task 5: integrar rating en Informe de proveedores + filtro + resumen + XLSX.
- Task 6+: endurecimiento/gates, CI y cierre documental según `docs/superpowers/plans/2026-09-15-fase8-calidad-proveedor-implementation.md`.
