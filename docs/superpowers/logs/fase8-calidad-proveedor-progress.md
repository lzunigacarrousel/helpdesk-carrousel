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

### Task 6 — Seguridad, historial y casos límite 🚧 RED PREPARADO

Objetivo:
- centralizar el criterio de ciclo evaluable en `ProviderRatingService::isCycleEvaluable()`;
- rechazar cierres implícitos y ciclos activos;
- mantener correcciones obsoletas/corruptas fuera del vigente;
- evitar aplicar rating de otro proveedor al ciclo;
- reutilizar el criterio en persistencia y UI;
- reforzar no fuga a proveedor/solicitante.

Cambios de test preparados, sin tocar aún código funcional:
- `02e7252 test: exigir criterio central de ciclo evaluable` actualiza `tests/phase8_provider_rating_service_regression.php`;
- `ad55f02 test: exigir criterio evaluable en persistencia` actualiza `tests/phase8_provider_rating_controller_regression.php`;
- `3915dba test: endurecer no fuga y criterio visual` actualiza `tests/phase8_provider_rating_ui_regression.php`.

Nuevas expectativas RED:
- existe `isCycleEvaluable()`;
- cierre explícito con `grant_event_id + revoke_event_id + revoked_at` sí es evaluable;
- cierre implícito por nuevo grant no es evaluable;
- ciclo activo no es evaluable;
- persistencia reutiliza el criterio central;
- corrección obsoleta no desplaza vigente;
- rating de otro proveedor no se aplica al ciclo;
- la vista interna reutiliza `ProviderRatingService::isCycleEvaluable()`;
- proveedor externo no recibe score/comentario;
- solicitante queda fuera del bloque interno de calidad.

Siguiente acción exacta en PC TEST:
1. `git pull --ff-only origin fase8-calidad-proveedor`;
2. confirmar `git status` limpio;
3. ejecutar `tests/phase8_provider_rating_service_regression.php`;
4. ejecutar `tests/phase8_provider_rating_controller_regression.php`;
5. ejecutar `tests/phase8_provider_rating_ui_regression.php`;
6. confirmar RED esperado únicamente en `isCycleEvaluable`/reutilización central antes de implementar GREEN.

## Estado actual para retomar

- Tasks 1–5: cerradas y limpias.
- Task 6: RED preparado remotamente; pendiente ejecutar en PC TEST.
- Rama: `fase8-calidad-proveedor`.

## Próximas tareas del plan

- Task 6: ejecutar RED, implementar `isCycleEvaluable()` y defensas, correr GREEN acumulado.
- Task 7+: CI/cierre documental y gate integral según `docs/superpowers/plans/2026-09-15-fase8-calidad-proveedor-implementation.md`.
