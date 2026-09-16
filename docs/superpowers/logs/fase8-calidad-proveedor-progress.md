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
Implementado: `grant_event_id`, `revoke_event_id`, `rowsForTicket()` y cierre implícito sin `revoke_event_id`. Regresiones verdes.

### Task 2 — Dominio inmutable de valoración ✅
Implementado `ProviderRatingService` con escala, validación, reconstrucción de vigente, enriquecimiento, resumen y opciones. Regresiones verdes.

### Task 3 — Persistencia + autorización backend ✅
Implementado `rateCycle()`, `correctCycle()`, lectura de eventos, `FOR UPDATE`, controller, CSRF, scope, auditoría y rutas POST. Sin UPDATE/DELETE de ratings.

### Task 4 — UI interna de calidad del proveedor ✅ CERRADA
Commit funcional: `67160ba ui: evaluar proveedores desde el ticket`.

Incluye bloque interno `#provider-quality`, primera evaluación, corrección, actor/fecha/comentario vigentes y no fuga a `show_external.php`.

Limpieza:
- `tools/apply_phase8_provider_rating_ui.php` eliminado en `1bf5d8e`.
- `tools/normalize_phase8_task4_eol.php` eliminado en `e302a49`.

### Task 5 — Informe de proveedores + valoración + XLSX ✅ CERRADA Y LIMPIA
Commit funcional: `a8811c6 feat: integrar calidad de proveedores en informes`.

Implementado:
- filtro `rating=UNRATED|1|2|3|4|5`;
- dataset enriquecido antes de filtrar;
- resumen de calidad por proveedor;
- filtro y valoración vigente en pantalla;
- XLSX con score, comentario y hoja de calidad;
- 0 cambios de BD.

GREEN final:
- Fase 8 report/UI/controller/service/cycle: GREEN;
- Fase 7 filters/participation: GREEN;
- `project_quality.php`: GREEN;
- `git diff --check`: limpio salvo advertencias LF/CRLF de Windows.

Limpieza:
- cierre registrado en `20be230`;
- `tools/apply_phase8_provider_rating_report.php` eliminado en `b07b827`;
- limpieza registrada en `f0503aa`;
- no quedan herramientas temporales de Task 5.

### Task 6 — Seguridad, historial y casos límite ✅ CERRADA Y LIMPIA

Commit funcional:
- `8b17a4a feat: endurecer reglas de calidad proveedor`.

Implementado:
- `ProviderRatingService::isCycleEvaluable()` centraliza el criterio;
- exige `grant_event_id>0`, `revoke_event_id>0` y `revoked_at` informado;
- cierre explícito por `EXTERNAL_REVOKED`: evaluable;
- cierre implícito por nuevo grant: no evaluable;
- ciclo activo: no evaluable;
- `enrichCycles()` ignora ratings de ciclos no evaluables;
- `requireEvaluableCycle()` reutiliza el criterio central en primera valoración y corrección;
- `tickets/show.php` reutiliza `ProviderRatingService::isCycleEvaluable($cycle)` para habilitar formularios;
- cierre implícito muestra explicación y no formulario de evaluación;
- corrección obsoleta no desplaza la vigente;
- rating de otro proveedor no se aplica al ciclo;
- proveedor y solicitante siguen sin recibir calidad interna;
- 0 cambios de BD.

Verificación GREEN acumulada previa al commit:
- `phase8_provider_rating_service_regression.php`: GREEN;
- `phase8_provider_rating_controller_regression.php`: GREEN;
- `phase8_provider_rating_ui_regression.php`: GREEN;
- `phase8_provider_cycle_identity_regression.php`: GREEN;
- `phase8_provider_rating_report_regression.php`: GREEN;
- regresiones Fase 7 participation/activity/returns/filters: GREEN;
- `project_quality.php`: GREEN;
- `git diff --check`: sin errores; solo advertencias LF/CRLF de Windows.

Consolidación y limpieza:
- `git diff --cached --stat` mostró únicamente `ProviderRatingService.php` y `tickets/show.php`;
- commit `8b17a4a` subido correctamente a `origin/fase8-calidad-proveedor`;
- working tree quedó limpio y sincronizado después del push;
- cierre funcional registrado en `8f79804`;
- `tools/apply_phase8_provider_rating_hardening.php` eliminado en `6a64d31`;
- no quedan herramientas temporales de Task 6.

### Task 7 — CI y cierre documental ⏭️ SIGUIENTE

Pendiente según plan:
- crear/ejecutar `tests/phase8_provider_rating_closeout_regression.php`;
- integrar gates de Fase 8 en `.github/workflows/helpdesk-ci.yml`;
- actualizar `README.md`, `CHANGELOG.md` y `app/Views/help/manual.php`;
- documentar explícitamente que Fase 8 usa `ticket_events` y no requiere cambios estructurales de BD;
- ejecutar gate integral antes del cierre de la fase.

## Estado actual para retomar

- Tasks 1–6: cerradas y limpias.
- Task 7: siguiente paso.
- Rama: `fase8-calidad-proveedor`.
- PC TEST quedó `working tree clean` tras el commit funcional de Task 6; debe hacer `git pull` para recibir la limpieza y este log actualizado.

## Próximas tareas del plan

- Task 7: CI + documentación + closeout regression.
- Luego: gate integral, sincronización final y preparación para integrar a `main` según `docs/superpowers/plans/2026-09-15-fase8-calidad-proveedor-implementation.md`.
