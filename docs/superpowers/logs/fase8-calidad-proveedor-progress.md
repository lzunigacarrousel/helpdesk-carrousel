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

### Task 4 — UI interna de calidad del proveedor ✅ GREEN, PENDIENTE DE COMMIT LOCAL

Arquitectura confirmada:
- `/tickets/view` entra por `TicketViewController`.
- usuarios internos son delegados a `TicketController::show()`.
- usuarios `EXTERNAL` renderizan `tickets/show_external.php` por separado.
- ratings se cargan solo en `TicketController::show()` y `tickets/show.php`.
- `show_external.php` permanece sin score, comentario ni bloque de calidad.

Implementado localmente:
- `TicketController` carga `ProviderParticipationService` y `ProviderRatingService`;
- obtiene `rowsForTicket($id)` y los enriquece con valoraciones;
- expone `providerCycles`, `providerRatingLabels` y `canRateProviders`;
- bloque interno `#provider-quality`;
- ciclo activo muestra que aún no puede evaluarse;
- ciclo finalizado sin valoración permite `Evaluar proveedor`;
- ciclo evaluado permite `Registrar corrección`;
- formularios conservan `external_user_id`, `grant_event_id` y `corrected_rating_event_id`;
- muestra score vigente, etiqueta, actor, fecha y comentario interno;
- la UI explica que el comentario es obligatorio para 1–2 estrellas y toda corrección;
- proveedor externo y solicitante no reciben datos internos de valoración.

#### RED/GREEN de Task 4

RED inicial:
- 23 validaciones fallaron, todas correspondientes a UI interna inexistente.
- validaciones de no fuga externa ya estaban GREEN.

GREEN actual:
- `phase8_provider_rating_ui_regression.php`: completamente GREEN.
- `phase8_provider_rating_controller_regression.php`: GREEN.
- `phase8_provider_rating_service_regression.php`: GREEN.
- `phase8_provider_cycle_identity_regression.php`: GREEN.
- regresión Fase 7 de proveedores: GREEN.
- `project_quality.php`: GREEN en la ejecución completa previa de Task 4.
- sintaxis PHP de `TicketController.php` y `show.php`: GREEN.

#### Blocker CRLF/LF — RESUELTO ✅

Problema detectado:
- el primer aplicador falló en Windows porque un ancla multilínea esperaba LF y la copia local usaba CRLF;
- luego `git diff --check` reportó `trailing whitespace` por `^M` en las líneas nuevas de `show.php`.

Correcciones realizadas:
- `tools/apply_phase8_provider_rating_ui.php` quedó idempotente y tolerante a CRLF;
- se creó `tools/normalize_phase8_task4_eol.php`;
- ambos archivos modificados de Task 4 fueron normalizados a LF.

Verificación después de normalizar:
- no aparecieron nuevamente errores de `trailing whitespace`;
- `git diff --check` solo mostró advertencias de Git para Windows: `LF will be replaced by CRLF the next time Git touches it`;
- esas advertencias provienen de la configuración de finales de línea de Git y no constituyen un fallo de `diff --check`.

**Estado local actual:**
- `app/Controllers/TicketController.php` modificado y listo para commit;
- `app/Views/tickets/show.php` modificado y listo para commit;
- working tree sin otros cambios funcionales locales;
- herramientas temporales siguen versionadas remotamente hasta consolidar el cambio funcional.

**Siguiente acción exacta:**
1. traer esta actualización del log con `git pull --ff-only origin fase8-calidad-proveedor`;
2. stage de `TicketController.php` y `show.php`;
3. `git diff --cached --check`;
4. commit `ui: evaluar proveedores desde el ticket`;
5. push de la rama;
6. confirmar `git status` limpio;
7. actualizar este log marcando Task 4 cerrada por commit;
8. eliminar las herramientas temporales de Task 4;
9. iniciar Task 5 con RED para informe + filtro + resumen + XLSX.

## Próximas tareas del plan

- Task 5: integrar rating en Informe de proveedores + filtro + resumen + XLSX.
- Task 6+: endurecimiento/gates, CI y cierre documental según `docs/superpowers/plans/2026-09-15-fase8-calidad-proveedor-implementation.md`.
