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
- limpieza registrada en `4a2791a`;
- no quedan herramientas temporales de Task 6.

### Task 7 — CI, Manual y cierre documental ✅ CERRADA Y LIMPIA

Commit de cierre:
- `15ac1c4 docs: cerrar fase 8 calidad proveedor`.

Incluye:
- CI con los seis gates de Fase 8;
- README con Fase 8 implementada y Fase 9 como siguiente;
- README con eventos inmutables y `BD: sin cambios`;
- CHANGELOG con `PROVIDER_RATED`, `PROVIDER_RATING_CORRECTED`, separación de `ticket_feedback.nps_score` y cero cambios de BD;
- Manual integrado con escala 1–5, comentario obligatorio, correcciones, `Sin evaluar`, privacidad e Informe de proveedores;
- corrección estable en `phase7_provider_participation_regression.php` para que Fase 7 verifique que Fase 8 permanece en el roadmap sin fijarla eternamente como la siguiente fase.

GREEN definitivo:
- `phase7_provider_participation_regression.php`: GREEN;
- `phase8_provider_rating_closeout_regression.php`: GREEN;
- `project_quality.php`: GREEN;
- `xlsx_smoke.php`: GREEN;
- `git diff --check`: sin errores; solo advertencias LF/CRLF de Windows.

Consolidación y limpieza:
- commit `15ac1c4` subido a `origin/fase8-calidad-proveedor`;
- PC TEST quedó `working tree clean` después del push;
- cierre registrado en `bc39c41`;
- `tools/apply_phase8_closeout.php` eliminado en `e9e48f1`;
- `tools/fix_phase7_roadmap_regression.php` eliminado en `9f39b79`;
- no quedan herramientas temporales de Task 7.

### Task 8 — Gate integral y validación PC TEST 🚧 GATE TÉCNICO GREEN

Gate técnico ejecutado completo en PC TEST sobre `fase8-calidad-proveedor` sincronizada con `origin/fase8-calidad-proveedor`.

Resultado técnico confirmado:
- `git status`: limpio antes y después del gate;
- sintaxis PHP GREEN en `ProviderParticipationService`, `ProviderRatingService`, `ProviderRatingController`, `TicketController`, `ExternalReportController`, `tickets/show.php`, `management/external_report.php` y `public/index.php`;
- las seis regresiones Fase 8: GREEN;
- las cinco regresiones Fase 7 ejecutadas: GREEN;
- `project_quality.php`: GREEN;
- `xlsx_smoke.php`: GREEN;
- `static_checks.php`: GREEN;
- `git diff --check`: sin errores;
- `git diff --name-only origin/main...HEAD -- database`: sin salida, confirmando 0 cambios en `database/` respecto a `main`;
- rama local visible: `fase8-calidad-proveedor`, con `main` local y remotos `origin/fase8-calidad-proveedor` / `origin/main`;
- working tree final: `nothing to commit, working tree clean`.

Diff acumulado contra `origin/main`:
- 22 archivos modificados/agregados;
- 3234 inserciones y 291 eliminaciones;
- incluye implementación funcional, tests Fase 8, documentación de diseño/plan/log, CI/README/CHANGELOG/Manual y el ajuste estable de regresión Fase 7;
- no incluye archivos de `database/`.

Archivos principales en el diff acumulado:
- `.github/workflows/helpdesk-ci.yml`;
- `CHANGELOG.md`, `README.md`;
- `app/Controllers/ExternalReportController.php`, `ProviderRatingController.php`, `TicketController.php`;
- `app/Services/ProviderParticipationService.php`, `ProviderRatingService.php`;
- `app/Views/help/manual.php`, `management/external_report.php`, `tickets/show.php`;
- `public/index.php`;
- seis tests Fase 8 + ajuste de `phase7_provider_participation_regression.php`;
- documentos de diseño, plan y log de Fase 8.

Pendiente para cerrar Task 8:
- validación funcional manual en PC TEST;
- validación visual en modo claro/oscuro;
- revisión en laptop 1366px, tablet/iPad y móvil;
- comprobar captura de primera valoración, corrección, comentario obligatorio 1–2 estrellas, `Sin evaluar`, privacidad para EXTERNAL/REQUESTER, filtros del Informe de proveedores y XLSX;
- registrar evidencia/manual findings en este log;
- no fusionar a `main` hasta aprobación explícita.

Siguiente acción exacta en PC TEST:
1. abrir un ticket con ciclo de proveedor finalizado explícitamente;
2. validar primera valoración 3–5 estrellas sin comentario obligatorio;
3. validar 1–2 estrellas con comentario obligatorio;
4. registrar una corrección y confirmar historial/vigente;
5. revisar un ciclo activo y un cierre implícito: no deben ser evaluables;
6. entrar como proveedor/solicitante y confirmar que score/comentario no aparecen;
7. revisar `/admin/externos/informe`, filtros `Sin evaluar` / estrellas y exportación XLSX;
8. repetir revisión visual en claro/oscuro y tamaños laptop/tablet/móvil;
9. reportar cualquier detalle antes de merge.

## Estado actual para retomar

- Tasks 1–7: cerradas y limpias.
- Task 8: gate técnico GREEN; validación manual/visual pendiente.
- Rama: `fase8-calidad-proveedor`.
- PC TEST sincronizada y limpia.

## Próximas tareas del plan

- Task 8: validación funcional/visual final.
- Después, preparar integración a `main` únicamente con aprobación explícita del usuario.
