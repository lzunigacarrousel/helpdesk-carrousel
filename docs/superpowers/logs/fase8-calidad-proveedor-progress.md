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

### Task 6 — Seguridad, historial y casos límite 🚧 RED CONFIRMADO

Objetivo:
- centralizar el criterio de ciclo evaluable en `ProviderRatingService::isCycleEvaluable()`;
- rechazar cierres implícitos y ciclos activos;
- mantener correcciones obsoletas/corruptas fuera del vigente;
- evitar aplicar rating de otro proveedor al ciclo;
- reutilizar el criterio en persistencia y UI;
- reforzar no fuga a proveedor/solicitante.

Tests RED preparados previamente:
- `02e7252 test: exigir criterio central de ciclo evaluable`;
- `ad55f02 test: exigir criterio evaluable en persistencia`;
- `3915dba test: endurecer no fuga y criterio visual`.

RED confirmado en PC TEST:
- rama sincronizada y `working tree clean` antes de ejecutar;
- `phase8_provider_rating_service_regression.php`: 4 fallos esperados;
- `phase8_provider_rating_controller_regression.php`: 2 fallos esperados;
- `phase8_provider_rating_ui_regression.php`: 2 fallos esperados.

Fallos exactos confirmados:
- falta `ProviderRatingService::isCycleEvaluable()`;
- no existe aún una única regla reutilizable para distinguir cierre explícito, cierre implícito y ciclo activo;
- persistencia todavía no reutiliza ese criterio central;
- vista interna todavía no llama `ProviderRatingService::isCycleEvaluable()`.

Defensas que ya permanecen GREEN durante el RED:
- corrección obsoleta no desplaza la valoración vigente;
- rating de otro proveedor no se aplica al ciclo;
- proveedor externo no recibe score ni comentario interno;
- solicitante queda fuera del bloque de calidad;
- controller carga calidad solo para soporte interno;
- roles, CSRF, scope, auditoría, rutas e inmutabilidad siguen verdes.

GREEN mínimo a implementar:
1. agregar `public static function isCycleEvaluable(array $cycle): bool`;
2. exigir `grant_event_id>0`, `revoke_event_id>0` y `revoked_at` no vacío;
3. reutilizar `isCycleEvaluable()` dentro de `requireEvaluableCycle()` para `rateCycle()` y `correctCycle()`;
4. reutilizar `ProviderRatingService::isCycleEvaluable($cycle)` en `tickets/show.php` para decidir si aparecen formularios;
5. no modificar BD ni visibilidad externa.

## Estado actual para retomar

- Tasks 1–5: cerradas y limpias.
- Task 6: RED confirmado; GREEN pendiente.
- Rama: `fase8-calidad-proveedor`.
- PC TEST estaba limpio al iniciar Task 6.

## Próximas tareas del plan

- Task 6: implementar GREEN mínimo, correr acumulado Fase 8 y regresiones, consolidar y limpiar herramienta temporal si se usa.
- Task 7+: CI/cierre documental y gate integral según `docs/superpowers/plans/2026-09-15-fase8-calidad-proveedor-implementation.md`.
