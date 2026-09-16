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
- Las correcciones agregan eventos; nunca editan ni borran valoraciones anteriores.
- Último evento válido del ciclo = valoración vigente.
- Cada rating referencia `external_user_id` + `grant_event_id`.
- `Sin evaluar` no vale 0 ni entra al promedio.

## Flujo de fases

### Task 1 — Identidad exacta del ciclo ✅
`grant_event_id`, `revoke_event_id`, `rowsForTicket()` y cierre implícito sin inventar `revoke_event_id`.

### Task 2 — Dominio inmutable de valoración ✅
`ProviderRatingService`: escala, validación, valoración vigente, correcciones, enriquecimiento y resumen.

### Task 3 — Persistencia + autorización backend ✅
`rateCycle()`, `correctCycle()`, `FOR UPDATE`, controller, CSRF, scope, auditoría y rutas POST.

### Task 4 — UI interna ✅
Commit: `67160ba ui: evaluar proveedores desde el ticket`.

### Task 5 — Informe + filtros + XLSX ✅
Commit: `a8811c6 feat: integrar calidad de proveedores en informes`.

### Task 6 — Seguridad y casos límite ✅
Commit: `8b17a4a feat: endurecer reglas de calidad proveedor`.

### Task 7 — CI, Manual y documentación ✅
Commit: `15ac1c4 docs: cerrar fase 8 calidad proveedor`.

### Task 8 — Gate integral y validación PC TEST 🚧 EN CURSO

## Validaciones y extensiones ya cerradas en Task 8

### Ciclo y calidad
- ciclo activo visible como no evaluable ✅
- cierre explícito evaluable ✅
- primera valoración real `1★ · Muy deficiente` con comentario ✅
- corrección válida real a `4★ · Bueno` con comentario ✅
- segunda corrección válida real a `5★ · Excelente` con comentario `pruebas de registro de conexion` ✅
- contador visible `2 corrección(es)` ✅
- valoración vigente reconstruida correctamente ✅
- **todavía no se ha probado corrección con comentario vacío** ⏳

### Historial administrativo por proveedor
- `Historial` → `/admin/externos/informe?provider=<id>` ✅
- `Excel` → `/admin/externos/informe/exportar?provider=<id>` ✅
- commit funcional: `a060825 ui: agregar historial y excel por proveedor`.

### Historial para el propio proveedor
- accesos vigentes e históricos revocados visibles en `Mis casos` ✅
- histórico revocado sin enlace al detalle ✅
- métricas `Activos`, `En espera`, `Finalizados`, `Total` ✅
- sin valoración interna ✅
- commit funcional: `2ad58d5 feat: conservar historial de casos para proveedores`.

### Excel seguro para el propio proveedor
- endpoint `GET /mis-tickets/exportar` ✅
- controller `ExternalCaseHistoryController` ✅
- consulta limitada por `Auth::id()` ✅
- columnas seguras: Ticket, Asunto, Categoría, Ubicación, Participación, Asignado, Finalizado ✅
- archivo real abre en Microsoft Excel ✅
- descarga real desde Chrome ✅
- no expone score, comentario interno, correo del solicitante ni resolución interna ✅
- commit funcional: `56615a6 feat: exportar historial de casos para proveedores`.

### Overlay de descarga Excel
- defecto reproducido: overlay `Procesando información · Abriendo...` quedaba abierto.
- solución: `data-no-loading="1"` en el enlace de descarga ✅
- regresión GREEN ✅
- validación visual real ✅
- commit funcional: `d66545a fix: pulir descarga y registro de calidad proveedor`.

### REGISTRO actor/fecha
- defecto reproducido: `Luis Fernando Zuniga16/09/2026 12:33`.
- markup `provider-rating-registration` + `provider-rating-registered-at` ✅
- CSS separa actor y fecha ✅
- causa final de persistencia visual: `case-focus.css` seguía usando query string fijo y Chrome reutilizaba asset anterior.
- RED cache bust: fallaron exactamente `Case focus usa versión dinámica por filemtime` y `Vista carga case-focus con versión dinámica`.
- GREEN: `case-focus.css` ahora usa versión dinámica por `filemtime()` ✅
- `phase8_provider_rating_ui_regression.php` GREEN completo ✅
- `phase8_external_case_export_regression.php` GREEN completo ✅
- `git diff --check` limpio ✅
- validación visual del usuario: actor y fecha ahora se muestran correctamente separados ✅
- commit funcional: `3598380 fix: versionar dinamicamente css de calidad proveedor`.
- aplicador temporal retirado: `54784d3 chore: retirar aplicador cache bust calidad proveedor`.

## Gate técnico base ya comprobado

- sintaxis PHP GREEN en archivos modificados.
- regresiones Fase 8/Fase 7 GREEN en checkpoints previos.
- `project_quality.php`, `xlsx_smoke.php`, `static_checks.php` GREEN en gate previo.
- `git diff --check` limpio.
- 0 cambios estructurales en `database/` respecto a `main`.

## Pendientes reales de Task 8

1. Validar manualmente rechazo de **1–2★ sin comentario**.
2. Validar manualmente primera valoración **3–5★ sin comentario**.
3. Validar manualmente que **toda corrección sin comentario** sea rechazada.
4. Confirmar cierre implícito por nuevo grant como no evaluable.
5. Privacidad adicional con usuario REQUESTER.
6. Probar filtros e XLSX administrativo con datos reales.
7. Validación visual final: claro/oscuro, PC, iPad/tablet y móvil.
8. Repetir gate técnico integral completo al final.
9. Actualizar documentación/log final y dejar rama limpia.
10. Solo con aprobación explícita: integrar a `main`.

## Estado exacto actual

- Tasks 1–7: ✅ cerradas.
- Task 8 técnico base: ✅ GREEN.
- Task 8 funcional/visual: ⏳ en progreso.
- Historial/Excel administrativo: ✅.
- Historial propio proveedor: ✅.
- Excel propio proveedor: ✅.
- Overlay descarga: ✅ validado visualmente.
- REGISTRO actor/fecha: ✅ validado visualmente.
- Cache bust `case-focus.css`: ✅ consolidado.
- Aplicador de cache bust: ✅ retirado.
- Correcciones válidas con comentario: ✅ 4★ y 5★ verificadas.
- Corrección sin comentario: ⏳ todavía pendiente.
- Rama local del usuario tras sincronización: ✅ limpia y al día con `origin/fase8-calidad-proveedor`.
- Siguiente prueba inmediata: **corrección sin comentario debe rechazarse**.
- Rama: `fase8-calidad-proveedor`.
- No se ha fusionado a `main`.
- No iniciar Fase 9 todavía.

## Integración a main

**NO HACER MERGE todavía.** Solo después de completar Task 8, repetir el gate técnico, actualizar este log y obtener aprobación explícita del usuario.

## Próxima fase

Roadmap: **Fase 9 — Conocimiento**, únicamente después del cierre formal de Fase 8.
