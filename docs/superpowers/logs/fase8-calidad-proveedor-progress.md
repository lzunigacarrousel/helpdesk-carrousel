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

### Checkpoint E — Excel externo real ✅ RESUELTO Y VALIDADO
- `helpdesk_mis_casos_20260916_120336.xlsx` existe y abre correctamente en Microsoft Excel;
- hoja `Mis casos` sin reparación;
- columnas seguras: `Ticket`, `Asunto`, `Categoría`, `Ubicación`, `Participación`, `Asignado`, `Finalizado`;
- fila `HD-2026-000001` correcta;
- sin valoración interna, comentario de calidad, correo del solicitante ni resolución interna.

### Checkpoint F — descarga desde la propia UI ✅
- botón `Descargar Excel` visible para `Pruebas Comunicacion`;
- Chrome genera `helpdesk_mis_casos_20260916_121620.xlsx` y marca la descarga como `Hecho`;
- rama local limpia y sincronizada durante la prueba.

### Checkpoint G — corrección válida de valoración ✅
Validación manual posterior:
- la valoración vigente cambió a `4★ · Bueno`;
- se muestra `1 corrección(es)`;
- comentario interno visible: `resultado bueno 4 prueba`;
- actor: `Luis Fernando Zuniga`;
- fecha registrada: `16/09/2026 12:33`;
- el evento de corrección quedó aplicado y reconstruido correctamente.

**Aún pendiente:** comprobar rechazo real de una corrección con comentario vacío.

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

## Excel seguro para el propio proveedor ✅ CONSOLIDADO Y VALIDADO
- endpoint dedicado `GET /mis-tickets/exportar`;
- controller `ExternalCaseHistoryController`;
- consulta limitada por `Auth::id()`;
- commit funcional `56615a6 feat: exportar historial de casos para proveedores`;
- aplicador temporal retirado;
- RED/GREEN técnico completo;
- archivo real y descarga desde navegador validados.

## Hallazgos UI nuevos

### 1. Overlay global queda abierto al descargar Excel
Reproducción manual:
- la descarga termina correctamente en Chrome;
- la página queda cubierta por `Procesando información · Abriendo...` indefinidamente.

Causa identificada:
- `public/assets/js/app.js` activa el loader global para enlaces normales;
- solo omite el loader cuando el enlace tiene `download`, `target="_blank"` o `data-no-loading="1"`;
- como la respuesta es una descarga y no una navegación, no ocurre `load/pageshow` para cerrar el overlay.

RED confirmado inicialmente:
- `tests/phase8_external_case_export_regression.php` fallaba únicamente en `Descarga Excel externa no deja overlay global bloqueado`.

GREEN local aplicado:
- `tools/apply_phase8_ui_polish.php` agregó `data-no-loading="1"` al enlace externo;
- el aplicador reportó `[OK] Descarga Excel externa ya no activa overlay global.`

Blocker de test detectado después del GREEN:
- la regresión siguió roja por una aserción incorrecta del propio test;
- el test estaba buscando barras invertidas literales alrededor de comillas dentro de una cadena PHP con comillas simples;
- la vista generada por el aplicador es correcta, pero la aserción no podía coincidir;
- commit de corrección del test: `c169136 test: corregir asercion de overlay en excel externo`.

Pendiente inmediato: hacer `git pull` y repetir la regresión con los cambios funcionales locales todavía sin commit.

### 2. REGISTRO concatena actor y fecha
Reproducción manual:
- se visualizaba `Luis Fernando Zuniga16/09/2026 12:33` sin separación.

Causa visual:
- `resolution-read-grid` hacía block solo al `span` de etiqueta;
- el `<strong>` del actor y `<small>` de la fecha quedaban inline sin gap.

RED confirmado:
- `Registro de valoración usa bloque visual propio`;
- `Fecha de registro queda separada del actor`;
- `CSS separa actor y fecha de la valoración`.

GREEN local aplicado y validado técnicamente:
- `provider-rating-registration` agregado al bloque;
- `provider-rating-registered-at` agregado a la fecha;
- CSS específico separa actor y fecha;
- las tres comprobaciones ahora están `[OK]`;
- todavía falta validación visual real en navegador.

Commits RED:
- `3a197b2 test: exigir descarga externa sin overlay bloqueado`;
- `f0b8e2f test: exigir separacion visual de registro proveedor`;
- `c0a7fad docs: registrar red ui task 8`.

Aplicador temporal:
- `f388e25 tool: aplicar ajustes ui finales task 8`;
- debe retirarse después de consolidar los tres archivos funcionales.

## Pendientes de Task 8
- repetir `phase8_external_case_export_regression.php` tras `c169136`;
- consolidar GREEN de overlay + REGISTRO si queda todo verde;
- validar visualmente overlay de descarga y separación actor/fecha;
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
- Excel propio del proveedor: ✅ consolidado, limpio y validado.
- Corrección válida 4★ con comentario: ✅.
- Overlay post-descarga: 🟡 GREEN funcional local aplicado; test corregido remotamente, revalidación pendiente.
- Separación actor/fecha: ✅ GREEN técnico local; validación visual pendiente.
- Rama: `fase8-calidad-proveedor`.
- Working tree local esperado: modificados `app/Views/tickets/index.php`, `app/Views/tickets/show.php`, `public/assets/css/case-focus.css`.
- No se ha fusionado a `main`.
- No iniciar Fase 9 todavía.

## Integración a main
**NO HACER MERGE todavía.** Solo después de completar validación funcional/visual, resolver hallazgos, repetir gate técnico, actualizar este log y obtener aprobación explícita del usuario.

## Próxima fase
Roadmap: **Fase 9 — Conocimiento**, únicamente después del cierre formal de Task 8/Fase 8.
