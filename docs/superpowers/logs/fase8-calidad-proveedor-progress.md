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
`67160ba ui: evaluar proveedores desde el ticket`.

### Task 5 — Informe + filtros + XLSX ✅ CERRADA
`a8811c6 feat: integrar calidad de proveedores en informes`.

### Task 6 — Seguridad y casos límite ✅ CERRADA
`8b17a4a feat: endurecer reglas de calidad proveedor`.

### Task 7 — CI, Manual y documentación ✅ CERRADA Y LIMPIA
`15ac1c4 docs: cerrar fase 8 calidad proveedor`.

### Task 8 — Gate integral y validación PC TEST 🚧 EN CURSO

#### Gate técnico base ✅ GREEN
- sintaxis PHP GREEN en archivos modificados;
- regresiones Fase 8 y Fase 7 GREEN;
- `project_quality.php`, `xlsx_smoke.php`, `static_checks.php` GREEN;
- `git diff --check` sin errores;
- 0 cambios en `database/` respecto a `main`.

## Validación manual registrada

### Checkpoint A — ciclo activo ✅
Bloque `Calidad del proveedor` visible, participación activa, `Sin evaluar`, sin formulario.

### Checkpoint B — cierre explícito evaluable ✅
Participación finalizada por flujo normal; aparece `Evaluar proveedor`, escala 1★–5★ y regla de comentario.

### Checkpoint C — primera valoración real ✅
Se guardó `1★ · Muy deficiente` con comentario; valoración vigente, actor, fecha y `Registrar corrección` visibles.

### Checkpoint D — historial externo visible ✅
Con `Pruebas Comunicacion`: `Activos 0`, `En espera 0`, `Finalizados 1`, `Total 1`; caso histórico visible como `Participación finalizada`, con fechas de asignación/finalización y sin score/comentario interno.

### Checkpoint E — Excel externo real ✅
- `helpdesk_mis_casos_20260916_120336.xlsx` existe y abre correctamente en Microsoft Excel;
- hoja `Mis casos` sin reparación;
- columnas seguras: `Ticket`, `Asunto`, `Categoría`, `Ubicación`, `Participación`, `Asignado`, `Finalizado`;
- fila `HD-2026-000001` correcta;
- sin valoración interna, comentario de calidad, correo del solicitante ni resolución interna.

### Checkpoint F — descarga desde la propia UI ✅
- botón `Descargar Excel` visible para `Pruebas Comunicacion`;
- Chrome genera `helpdesk_mis_casos_20260916_121620.xlsx` y marca la descarga como `Hecho`.

### Checkpoint G — corrección válida de valoración ✅
- valoración vigente `4★ · Bueno`;
- `1 corrección(es)`;
- comentario interno `resultado bueno 4 prueba`;
- actor `Luis Fernando Zuniga`;
- fecha `16/09/2026 12:33`.

**Pendiente:** comprobar rechazo real de una corrección con comentario vacío.

### Checkpoint H — sincronización post-limpieza UI ✅
PC TEST sincronizada después de retirar `tools/apply_phase8_ui_polish.php`:
- rama local `fase8-calidad-proveedor` al día con `origin/fase8-calidad-proveedor`;
- `git status`: `nothing to commit, working tree clean`.

### Checkpoint I — validación visual post-fix
- descarga de Excel: ✅ usuario confirma que el overlay ya no queda bloqueado;
- bloque `REGISTRÓ`: ❌ continúa mostrando `Luis Fernando Zuniga16/09/2026 12:33` sin separación visual;
- el HTML nuevo y las clases de Fase 8 sí están en `show.php`;
- `case-focus.css` contiene las reglas nuevas;
- causa identificada: `app/Views/shared/app_start.php` carga `case-focus.css` con `$assetVersion='20260911-UXHELP1'` fijo, por lo que Chrome puede reutilizar una copia antigua del CSS aun cuando el archivo cambió;
- solución diseñada: versionar `case-focus.css` con `filemtime()` para que cada cambio del archivo genere una URL distinta y fuerce recarga del asset;
- RED preparado en `tests/phase8_provider_rating_ui_regression.php` para exigir el cache bust dinámico;
- aplicador temporal preparado en `tools/apply_phase8_case_focus_cache_bust.php`;
- no se modifica BD ni dominio de valoración.

Commits de preparación:
- `c67d385 test: exigir cache bust de css calidad proveedor`;
- `0205cde tool: aplicar cache bust de css calidad proveedor`;
- `84011b7 fix: corregir aplicador de cache bust case focus`.

## Historial y Excel administrativo por proveedor ✅
- `Historial` → `/admin/externos/informe?provider=<id>`;
- `Excel` → `/admin/externos/informe/exportar?provider=<id>`;
- commit funcional: `a060825 ui: agregar historial y excel por proveedor`.

## Historial para el propio proveedor ✅
- accesos vigentes e históricos revocados visibles en `Mis casos`;
- histórico revocado sin enlace al detalle;
- métricas `Activos`, `En espera`, `Finalizados`, `Total`;
- sin valoración interna;
- commit funcional: `2ad58d5 feat: conservar historial de casos para proveedores`.

## Excel seguro para el propio proveedor ✅
- endpoint dedicado `GET /mis-tickets/exportar`;
- controller `ExternalCaseHistoryController`;
- consulta limitada por `Auth::id()`;
- commit funcional `56615a6 feat: exportar historial de casos para proveedores`;
- aplicador temporal retirado;
- archivo real y descarga desde navegador validados.

## Hallazgos UI Task 8

### Overlay global al descargar Excel — ✅ VALIDADO VISUALMENTE
- RED: `3a197b2 test: exigir descarga externa sin overlay bloqueado`;
- enlace externo usa `data-no-loading="1"`;
- falso negativo del test corregido en `c169136`;
- regresión PC TEST GREEN completa;
- validación visual posterior: usuario confirma descarga funcional sin overlay persistente.

### REGISTRO actor/fecha — ⚠️ CSS correcto, cache bust pendiente
- RED inicial: `f0b8e2f test: exigir separacion visual de registro proveedor`;
- bloque `provider-rating-registration` y fecha `provider-rating-registered-at` ya están consolidados;
- CSS específico ya está en `case-focus.css`;
- regresión de contenido GREEN, pero navegador sigue usando CSS anterior por query string estático;
- nuevo RED exige versión dinámica de `case-focus.css` por `filemtime()`.

### Consolidación UI previa ✅
- `d66545a fix: pulir descarga y registro de calidad proveedor`;
- `51af5d2 docs: registrar consolidacion ui task 8`;
- `72650e0 chore: retirar aplicador ui task 8`.

## Pendientes de Task 8
- ejecutar RED de cache bust de `case-focus.css`;
- aplicar GREEN del cache bust y consolidarlo;
- validar visualmente actor/fecha separados en `REGISTRO`;
- rechazo de 1–2★ sin comentario;
- primera valoración 3–5★ sin comentario;
- corrección sin comentario debe rechazarse;
- cierre implícito no evaluable;
- privacidad REQUESTER adicional;
- filtros del informe y XLSX administrativo en uso real;
- visual claro/oscuro, PC, iPad/tablet y móvil;
- repetir gate técnico integral al final.

## Estado exacto actual
- Tasks 1–7: ✅ cerradas.
- Task 8 técnico base: ✅ GREEN.
- Task 8 funcional/visual: ⏳ en progreso.
- Historial/Excel administrativo: ✅.
- Historial propio del proveedor: ✅.
- Excel propio del proveedor: ✅.
- Descarga Excel sin overlay: ✅ validada visualmente.
- Corrección válida 4★ con comentario: ✅.
- Separación actor/fecha: ⚠️ markup/CSS consolidados, navegador con asset stale; cache bust preparado.
- Rama: `fase8-calidad-proveedor`.
- No se ha fusionado a `main`.
- No iniciar Fase 9 todavía.

## Integración a main
**NO HACER MERGE todavía.** Solo después de completar validación funcional/visual, resolver hallazgos, repetir gate técnico, actualizar este log y obtener aprobación explícita del usuario.

## Próxima fase
Roadmap: **Fase 9 — Conocimiento**, únicamente después del cierre formal de Task 8/Fase 8.
