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
Validación con `helpdesk_mis_casos_20260916_120336.xlsx`:
- archivo existe en `C:\Users\Luis Fernando Zuniga\Downloads\helpdesk_mis_casos_20260916_120336.xlsx`;
- `Test-Path` devuelve `True`;
- Microsoft Excel lo abre correctamente cuando se abre por su ruta real;
- hoja `Mis casos` visible sin reparación;
- encabezados: `Ticket`, `Asunto`, `Categoría`, `Ubicación`, `Participación`, `Asignado`, `Finalizado`;
- fila `HD-2026-000001` correcta;
- participación `Finalizada`;
- asignado `15/09/2026 13:20`;
- finalizado `16/09/2026 09:37`;
- no aparecen valoración interna, comentario de calidad, correo del solicitante ni resolución interna.

Incidencia previa aclarada:
- Excel había mostrado un mensaje buscando `helpdesk_proveedores_20260916_104604.xlsx`;
- el diagnóstico confirmó que ese mensaje correspondía a una referencia previa/reciente distinta del archivo nuevo;
- el archivo nuevo `helpdesk_mis_casos_20260916_120336.xlsx` existe y abre correctamente;
- no se requiere cambio adicional en `XlsxExportService` por esta incidencia.

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

## Excel seguro para el propio proveedor ✅ CONSOLIDADO, LIMPIO Y VALIDADO

### Diseño
- botón `Descargar Excel` en `Mis casos` solo para cuentas `EXTERNAL`;
- endpoint dedicado `GET /mis-tickets/exportar`;
- controller dedicado `ExternalCaseHistoryController`;
- consulta limitada por `eta.user_id=?` con `Auth::id()`;
- columnas: `Ticket`, `Asunto`, `Categoría`, `Ubicación`, `Participación`, `Asignado`, `Finalizado`;
- activas e históricas revocadas incluidas;
- sin valoración interna, comentario de calidad, NPS, correo del solicitante, comentarios, auditoría o resolución interna;
- 0 cambios de BD.

### RED/GREEN técnico ✅
- `tests/phase8_external_case_export_regression.php`: GREEN completo;
- `tests/phase8_external_case_history_regression.php`: GREEN completo;
- `tests/phase8_provider_rating_ui_regression.php`: GREEN completo;
- `tests/xlsx_smoke.php`: GREEN completo;
- `git diff --check`: sin errores.

### Consolidación funcional ✅
Commit funcional:
- `56615a6 feat: exportar historial de casos para proveedores`.

Archivos funcionales:
- `app/Controllers/ExternalCaseHistoryController.php`;
- `public/index.php`;
- `app/Views/tickets/index.php`.

### Limpieza ✅
- `847e050 docs: registrar consolidacion excel externo proveedor`;
- `43b6df9 chore: retirar aplicador excel externo proveedor`;
- aplicador temporal retirado.

### Validación real ✅
- descarga real confirmada con `Pruebas Comunicacion`;
- archivo localizado en Downloads;
- apertura directa en Microsoft Excel confirmada;
- columnas y datos seguros verificados.

## Pendientes de Task 8
- rechazo de 1–2★ sin comentario;
- primera valoración 3–5★ sin comentario;
- corrección sin comentario debe rechazarse;
- corrección válida con comentario;
- cierre implícito no evaluable;
- privacidad REQUESTER adicional;
- filtros del informe y XLSX administrativo en uso real;
- corregir separación visual actor/fecha en `REGISTRO`;
- visual claro/oscuro, PC, iPad/tablet y móvil;
- repetir gate técnico integral al final.

## Hallazgo visual pendiente

En el bloque `REGISTRO`, actor y fecha aparecen con separación insuficiente, por ejemplo `Luis Fernando Zuniga16/09/2026 09:45`. Debe corregirse antes de cerrar Task 8 y luego repetir gate técnico.

## Estado exacto actual
- Tasks 1–7: ✅ cerradas.
- Task 8 técnico base: ✅ GREEN.
- Task 8 funcional/visual: ⏳ en progreso.
- Historial/Excel administrativo: ✅.
- Historial propio del proveedor: ✅.
- Excel propio del proveedor: ✅ consolidado, limpio y validado en Microsoft Excel.
- Rama: `fase8-calidad-proveedor`.
- No se ha fusionado a `main`.
- No iniciar Fase 9 todavía.

## Integración a main
**NO HACER MERGE todavía.** Solo después de completar validación funcional/visual, resolver hallazgos, repetir gate técnico, actualizar este log y obtener aprobación explícita del usuario.

## Próxima fase
Roadmap: **Fase 9 — Conocimiento**, únicamente después del cierre formal de Task 8/Fase 8.
