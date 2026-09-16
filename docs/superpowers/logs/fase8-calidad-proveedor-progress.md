# Log de continuidad — Fase 8 · Calidad IT → proveedor

**Rama:** `fase8-calidad-proveedor`  
**Fecha de checkpoint:** 2026-09-16  
**Objetivo:** valoración interna 1–5 de IT por ciclo finalizado de participación de proveedor, con correcciones inmutables, captura dentro del ticket e informe de proveedores.  
**BD:** 0 cambios estructurales. Fuente de verdad: `ticket_events`.

## Regla operativa del log

Actualizar este archivo después de cada RED/GREEN importante, cambio funcional, blocker/resolución, cierre de Task, pausa o antes de integración. No guardar secretos ni credenciales.

## Diseño aprobado

- Evaluación por ciclo exacto de participación.
- Solo ciclo terminado mediante `EXTERNAL_REVOKED` es evaluable.
- Escala: 1 Muy deficiente, 2 Deficiente, 3 Adecuado, 4 Bueno, 5 Excelente.
- Comentario obligatorio para 1–2 estrellas y para toda corrección.
- Evaluación opcional; no bloquea cierre ni flujo del ticket.
- Roles: `ADMIN`, `SEMIADMIN`, `TECHNICIAN`, con scope válido.
- Proveedor y solicitante no ven score/comentario interno.
- Primera valoración: `PROVIDER_RATED`.
- Corrección: `PROVIDER_RATING_CORRECTED`.
- Correcciones son eventos nuevos; nunca se modifica/elimina una valoración anterior.
- Último evento válido del ciclo es la valoración vigente.
- Cada rating referencia `external_user_id` + `grant_event_id`.
- `Sin evaluar` no vale 0 ni entra al promedio.

## Flujo de fases

### Task 1 — Identidad exacta del ciclo ✅
`grant_event_id`, `revoke_event_id`, `rowsForTicket()` y cierre implícito sin `revoke_event_id`.

### Task 2 — Dominio inmutable de valoración ✅
`ProviderRatingService`: escala, validación, valoración vigente, correcciones, enriquecimiento y resumen.

### Task 3 — Persistencia + autorización backend ✅
`rateCycle()`, `correctCycle()`, `FOR UPDATE`, controller, CSRF, scope, auditoría y rutas POST. Sin UPDATE/DELETE de ratings.

### Task 4 — UI interna ✅ CERRADA
Commit funcional: `67160ba ui: evaluar proveedores desde el ticket`.

### Task 5 — Informe + filtros + XLSX ✅ CERRADA
Commit funcional: `a8811c6 feat: integrar calidad de proveedores en informes`.

### Task 6 — Seguridad y casos límite ✅ CERRADA
Commit funcional: `8b17a4a feat: endurecer reglas de calidad proveedor`.

### Task 7 — CI, Manual y documentación ✅ CERRADA Y LIMPIA
Commit de cierre: `15ac1c4 docs: cerrar fase 8 calidad proveedor`.

### Task 8 — Gate integral y validación PC TEST 🚧 EN CURSO

#### Gate técnico base ✅ GREEN

Confirmado previamente en PC TEST:
- sintaxis PHP GREEN en archivos modificados;
- 6 regresiones Fase 8 GREEN;
- 5 regresiones Fase 7 GREEN;
- `project_quality.php`, `xlsx_smoke.php`, `static_checks.php` GREEN;
- `git diff --check` sin errores;
- `git diff --name-only origin/main...HEAD -- database` sin salida;
- 0 cambios en `database/` respecto a `main`.

## Validación manual registrada

### Checkpoint A — ciclo activo ✅
- bloque `Calidad del proveedor` visible;
- participación `Activa`;
- `Sin evaluar`;
- no aparece formulario;
- mensaje operativo correcto.

### Checkpoint B — cierre explícito evaluable ✅
- participación finalizada por flujo normal;
- aparece `Evaluar proveedor`;
- opciones 1★–5★ visibles;
- regla de comentario visible.

### Checkpoint C — primera valoración real ✅
- se guardó `1★ · Muy deficiente` con comentario;
- valoración vigente reconstruida correctamente;
- actor y fecha visibles;
- aparece `Registrar corrección`.

Pendiente aún:
- rechazo de 1–2★ sin comentario;
- primera valoración 3–5★ sin comentario;
- corrección sin comentario debe rechazarse;
- corrección válida con comentario;
- cierre implícito no evaluable;
- privacidad EXTERNAL/REQUESTER;
- filtros del informe y XLSX en uso real;
- visual claro/oscuro, PC, iPad/tablet y móvil.

## Hallazgo visual pendiente

En el bloque `REGISTRO`, actor y fecha aparecen con separación insuficiente, por ejemplo `Luis Fernando Zuniga16/09/2026 09:45`. Debe corregirse antes de cerrar Task 8 y luego repetir gate técnico.

## Historial y Excel por proveedor — TDD ✅

Hallazgo: en `Proveedores registrados` cada fila solo tenía `Editar`; el informe global ya soportaba `provider=<id>` y la exportación XLSX reutiliza esos filtros.

Diseño aprobado:
- `Historial` → `/admin/externos/informe?provider=<id>`;
- `Excel` → `/admin/externos/informe/exportar?provider=<id>`;
- conservar `Editar`;
- sin BD nueva ni controller nuevo.

RED confirmado en `tests/phase8_provider_rating_report_regression.php`: fallaron únicamente las 4 expectativas nuevas de Historial/Excel.

GREEN confirmado en PC TEST:
- sintaxis de `app/Views/admin/externals.php`: OK;
- test de informe Fase 8: GREEN completo;
- `git diff --check`: sin errores;
- único archivo funcional modificado: `app/Views/admin/externals.php`.

### Consolidación funcional ✅

Commit realizado y subido:
- `a060825 ui: agregar historial y excel por proveedor`.

Estado reportado por PC TEST después del push:
- rama `fase8-calidad-proveedor` sincronizada con `origin/fase8-calidad-proveedor`;
- working tree limpio.

Pendiente inmediato de este subcambio:
- retirar `tools/apply_phase8_provider_row_actions.php` del repositorio;
- registrar la limpieza;
- sincronizar PC TEST;
- validar visualmente que cada proveedor muestre `Historial · Excel · Editar` y que ambos enlaces funcionen.

## Estado exacto actual

- Tasks 1–7: ✅ cerradas.
- Task 8 técnico base: ✅ GREEN.
- Task 8 funcional/visual: ⏳ en progreso.
- Historial/Excel por proveedor: ✅ funcionalmente consolidado en `a060825`, pendiente limpieza de herramienta y validación visual.
- Hallazgo visual actor/fecha: ⏳ pendiente.
- Rama de trabajo: `fase8-calidad-proveedor`.
- No se ha fusionado a `main`.
- No iniciar Fase 9 todavía.

## Integración a main

**NO HACER MERGE todavía.** Solo preparar integración después de completar checklist funcional/visual, resolver hallazgos, repetir gate técnico si hubo cambios, actualizar este log y obtener aprobación explícita del usuario.

## Próxima fase

Roadmap: **Fase 9 — Conocimiento**, únicamente después del cierre formal de Task 8/Fase 8.
