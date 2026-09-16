# Log de continuidad — Fase 8 · Calidad IT → proveedor

**Rama:** `fase8-calidad-proveedor`  
**Fecha de checkpoint:** 2026-09-16  
**Objetivo:** valoración interna 1–5 de IT por ciclo finalizado de participación de proveedor, con correcciones inmutables, captura dentro del ticket e informe de proveedores.  
**BD:** 0 cambios estructurales. Fuente de verdad de calidad: `ticket_events`.

## Regla operativa del log

Actualizar después de cada RED/GREEN importante, cambio funcional, blocker/resolución, cierre de Task, pausa o antes de integración. No guardar secretos ni credenciales.

## Diseño aprobado de Fase 8

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

## Historial y Excel administrativo por proveedor ✅ CERRADO TÉCNICAMENTE

Diseño implementado:
- `Historial` → `/admin/externos/informe?provider=<id>`;
- `Excel` → `/admin/externos/informe/exportar?provider=<id>`;
- `Editar` se conserva;
- sin BD nueva ni controller nuevo.

TDD:
- RED aislado en 4 expectativas nuevas;
- GREEN completo de `tests/phase8_provider_rating_report_regression.php`.

Consolidación:
- `a060825 ui: agregar historial y excel por proveedor`.
- herramienta temporal retirada.

## Historial visible para el propio proveedor ✅ CONSOLIDADO TÉCNICAMENTE

### Hallazgo real

Con la cuenta `Pruebas Comunicacion`, después de revocar su participación:
- `Mis casos` mostraba `Activos 0`, `En espera 0`, `Finalizados 0`;
- aparecía `No tienes casos asignados`;
- la URL del caso previo respondía `Ese caso no está disponible para tu cuenta.`.

Causa:
- `TicketController::index()` para `EXTERNAL` filtraba `eta.revoked_at IS NULL`;
- la fila histórica permanecía en `external_ticket_access`, pero desaparecía del listado;
- `TicketViewController` exige acceso vigente, por lo que el detalle revocado permanece correctamente bloqueado.

### Diseño seguro aprobado

- `Mis casos` incluye accesos vigentes e históricos revocados.
- Métricas: `Activos`, `En espera`, `Finalizados`, `Total`.
- Para proveedor, `Finalizados` se determina por `eta.revoked_at`, no por estado global posterior del ticket.
- Se muestran `granted_at` y `revoked_at` como asignación/finalización de participación.
- Un caso revocado se muestra como historial **sin enlace al detalle**.
- No se reabre conversación, adjuntos, resolución ni cambios posteriores a la revocación.
- No se expone score/comentario de calidad.
- Acceso vigente conserva comportamiento actual.
- 0 cambios de BD; se reutiliza `external_ticket_access`.

### RED TDD ✅ CONFIRMADO

Test: `tests/phase8_external_case_history_regression.php`.

Resultado inicial:
- 2 comprobaciones base `[OK]`;
- 10 fallos esperados de historial externo;
- privacidad de valoración interna `[OK]`;
- working tree limpio.

### Blocker EOL ✅ RESUELTO

Primer aplicador abortó por anclas LF/CRLF mixtas sin dejar cambios parciales. Se corrigió en:
- `1800ff9 fix: tolerar EOL mixto en historial externo`.

### GREEN TDD ✅ CONFIRMADO

Después del fix EOL:
- aplicador: `[OK] Historial seguro para proveedor externo aplicado. Casos revocados quedan visibles sin reabrir el detalle. No se modifico la BD.`;
- sintaxis `TicketController.php` y `tickets/index.php`: `[OK]`;
- `tests/phase8_external_case_history_regression.php`: GREEN completo;
- `tests/phase8_provider_rating_ui_regression.php`: GREEN completo;
- `git diff --check`: sin errores, solo warnings LF→CRLF normales de Windows;
- privacidad de score/comentario interno preservada;
- sin cambios de BD.

### Consolidación funcional ✅

Commit funcional confirmado y pusheado:
- `2ad58d5 feat: conservar historial de casos para proveedores`.

Archivos funcionales del commit:
- `app/Controllers/TicketController.php`;
- `app/Views/tickets/index.php`.

Estado después del push:
- rama `fase8-calidad-proveedor` sincronizada con `origin/fase8-calidad-proveedor`;
- working tree limpio.

### Siguiente paso exacto

1. retirar `tools/apply_phase8_external_case_history.php`;
2. registrar limpieza en este log;
3. sincronizar PC TEST;
4. validar visualmente con cuenta `Pruebas Comunicacion`:
   - métricas `Activos`, `En espera`, `Finalizados`, `Total`;
   - tarjeta histórica con `Participación finalizada`;
   - fechas de asignación/finalización;
   - tarjeta histórica sin enlace al detalle;
   - ninguna valoración interna visible.

## Estado exacto actual

- Tasks 1–7: ✅ cerradas.
- Task 8 técnico base: ✅ GREEN.
- Task 8 funcional/visual: ⏳ en progreso.
- Historial/Excel administrativo por proveedor: ✅ consolidado.
- Historial para el propio proveedor: ✅ consolidado técnicamente en `2ad58d5`; ⏳ validación visual real pendiente.
- Hallazgo visual actor/fecha: ⏳ pendiente.
- Rama: `fase8-calidad-proveedor`.
- No se ha fusionado a `main`.
- No iniciar Fase 9 todavía.

## Integración a main

**NO HACER MERGE todavía.** Solo preparar integración después de completar checklist funcional/visual, resolver hallazgos, repetir gate técnico si hubo cambios, actualizar este log y obtener aprobación explícita del usuario.

## Próxima fase

Roadmap: **Fase 9 — Conocimiento**, únicamente después del cierre formal de Task 8/Fase 8.
