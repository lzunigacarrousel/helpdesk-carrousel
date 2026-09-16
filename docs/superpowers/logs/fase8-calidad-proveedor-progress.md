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

### Task 8 — Gate integral y validación PC TEST ✅ CERRADA

## Validaciones y extensiones cerradas en Task 8

### Ciclo y calidad
- ciclo activo visible como no evaluable ✅
- cierre explícito evaluable ✅
- cierre implícito por nuevo grant no evaluable ✅ cubierto por regresión automatizada
- primera valoración real `1★ · Muy deficiente` con comentario ✅
- reglas 1–2★ sin comentario rechazadas ✅ cubiertas por regresión automatizada
- reglas 3–5★ sin comentario aceptadas ✅ cubiertas por regresión automatizada
- corrección válida real a `4★ · Bueno` con comentario ✅
- segunda corrección válida real a `5★ · Excelente` con comentario `pruebas de registro de conexion` ✅
- contador visible `2 corrección(es)` ✅
- valoración vigente reconstruida correctamente ✅
- corrección sin comentario rechazada manualmente por validación de formulario ✅

### Historial administrativo por proveedor
- `Historial` → `/admin/externos/informe?provider=<id>` ✅
- `Excel` → `/admin/externos/informe/exportar?provider=<id>` ✅
- filtros y resumen de calidad cubiertos por regresión automatizada ✅
- XLSX administrativo cubierto por regresión automatizada ✅
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
- causa final de persistencia visual: caché de `case-focus.css` con query string fijo.
- `case-focus.css` usa versión dinámica por `filemtime()` ✅
- validación visual del usuario: actor y fecha correctamente separados ✅
- commit funcional: `3598380 fix: versionar dinamicamente css de calidad proveedor`.
- aplicador temporal retirado: `54784d3 chore: retirar aplicador cache bust calidad proveedor`.

## Gate final de Fase 8 — 2026-09-16 ✅

Se creó `VALIDAR_FASE8.bat` para ejecutar el gate integral en PC TEST y evitar validaciones manuales repetitivas.

Resultado ejecutado por el usuario en `C:\xampp\htdocs\HelpdeskCarrousel`:

- Fase 7 participación proveedor ✅
- Fase 7 catálogo proveedor ✅
- Fase 8 identidad de ciclos ✅
- Fase 8 reglas de valoración ✅
- Fase 8 controller y autorización ✅
- Fase 8 UI y privacidad ✅
- Fase 8 informes y XLSX admin ✅
- Fase 8 historial propio proveedor ✅
- Fase 8 Excel propio proveedor ✅
- Fase 8 cierre documental y CI ✅
- `tests/static_checks.php` ✅
- `tests/project_quality.php` ✅
- `tests/xlsx_smoke.php` ✅
- `git diff --check` ✅
- `git status` ✅ working tree clean
- salida final: `[OK] GATE FINAL FASE 8 COMPLETADO SIN FALLOS`

CI remoto:

- workflow `Helpdesk Carrousel CI` sobre commit `dc332db` ✅ `success`
- run `35148901904` ✅ completado sin fallos
- CI también incluye ahora `phase8_external_case_history_regression.php` y `phase8_external_case_export_regression.php`.

## Estado exacto actual

- Tasks 1–8: ✅ cerradas.
- Fase 8 técnica: ✅ GREEN.
- Fase 8 funcional: ✅ validada.
- Historial/Excel administrativo: ✅.
- Historial propio proveedor: ✅.
- Excel propio proveedor: ✅.
- Overlay descarga: ✅ validado visualmente.
- REGISTRO actor/fecha: ✅ validado visualmente.
- Privacidad de proveedor/solicitante: ✅ cubierta por regresiones.
- Reglas de comentario y ciclo evaluable: ✅ cubiertas por regresiones y prueba manual de corrección.
- Rama local del usuario: ✅ limpia y al día al finalizar el gate.
- Rama remota: `fase8-calidad-proveedor`.
- No se ha fusionado a `main`.

## Integración a main

**Fase 8 está lista para integración.** No hacer merge automático sin aprobación explícita del usuario.

Antes del merge: sincronizar este último checkpoint documental en PC TEST y confirmar `git status` limpio.

## Próxima fase

Roadmap: **Fase 9 — Conocimiento**. Iniciar únicamente después de integrar/cerrar formalmente Fase 8 según decisión del usuario.
