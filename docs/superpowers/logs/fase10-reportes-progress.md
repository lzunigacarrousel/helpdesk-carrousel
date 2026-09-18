# Fase 10 — Reportes · progreso

Fecha de inicio: 2026-09-18
Rama vigente: `main`

## Estado inicial

- Fases 1–8: implementadas.
- Fase 9 Conocimiento: gate local PC TEST GREEN.
- Fase 10 Reportes: iniciada.
- Fase 11 Manual: siguiente.
- Fase 12 Validación integral: cierre transversal final.
- Producción: sin cambios.

## Decisiones

- No reconstruir reportes existentes.
- `/gestion/informes` será el centro de navegación y consolidación.
- Reutilizar servicios/datasets existentes.
- No crear BD nueva por defecto.
- Gerencia/Supervisión siguen siendo perfiles de consulta.
- Mantener exportaciones XLSX existentes y scope backend.

## Task 1 — Hub de reportes

Estado: en implementación.

Objetivo:
- accesos compactos a informes ya existentes;
- evitar navegación dispersa;
- conservar una sola acción principal por bloque;
- respetar permisos.

## Task 1 — Hub de reportes · IMPLEMENTADA

- `/gestion/informes` incorpora hub compacto.
- Accesos: Tickets y SLA, Agenda/Actividades, Proveedores, Equipo de soporte.
- Los accesos se muestran según rol/capacidad.
- Gerencia/Supervisión siguen en modo consulta; no se agregaron acciones operativas.
- Responsive: 4 columnas escritorio, 2 tablet, 1 móvil.
- Se conserva exportación XLSX del informe general.
- `tests/phase10_reports_hub_regression.php` creado.
- `VALIDAR_FASE10.bat` creado.
- CI incorpora la regresión inicial de Fase 10.
- BD: sin cambios.

Siguiente: Task 2 — resumen de Conocimiento dentro del Centro de informes.

## Task 2 — Resumen de Conocimiento · IMPLEMENTADA

- Reutiliza `KnowledgeMetricsService`; no crea dataset paralelo.
- Estado actual: artículos activos/archivados, publicados para soporte, disponibles para solicitantes, borradores y en revisión.
- Actividad del período: sugerencias, aperturas, usos como referencia y tickets con referencia.
- Las métricas operativas vinculadas a tickets respetan `ScopeService`.
- El bloque solo aparece con capacidad `knowledge.view` o perfil administrativo equivalente.
- La UI usa un panel compacto de 4 indicadores principales + actividad secundaria; no agrega un dashboard saturado.
- Responsive integrado para escritorio/tablet/móvil.
- `tests/phase10_knowledge_report_regression.php` agregado a gate y CI.
- BD: sin cambios.

Siguiente: Task 3 — resumen de Actividades / Agenda dentro del Centro de informes, reutilizando `ticket_activities` y `AgendaService`.

## Task 3 — Resumen de Actividades / Agenda · IMPLEMENTADA

- Reutiliza `AgendaService::activities()`; no duplica la lógica de Agenda.
- Resume actividades que coinciden con el período seleccionado.
- Indicadores: programadas, en curso, finalizadas, canceladas y atrasadas.
- Contexto secundario: total de actividades y conflictos de horario.
- Respeta `ScopeService` heredado desde Agenda y el filtro de parque del Centro de informes.
- El bloque solo aparece con capacidad `activities.view` o perfil administrativo equivalente.
- El hub dirige primero al resumen y desde ahí ofrece `Abrir agenda` con el mismo rango de fechas.
- Responsive integrado para escritorio/tablet/móvil.
- `tests/phase10_activity_report_regression.php` agregado a gate y CI.
- BD: sin cambios.

Siguiente: Task 4 — consolidación de Tickets / SLA y consistencia pantalla ↔ XLSX.

## Task 4 — Tickets / SLA / XLSX · IMPLEMENTADA

- Se creó `TicketReportFilterService` como fuente única de filtros, alcance, etiquetas y descripción del reporte.
- Pantalla y XLSX comparten exactamente período, parque, categoría, responsable, estado y prioridad.
- El filtro de categoría padre incluye sus subcategorías tanto en pantalla como en Excel.
- `ScopeService` se aplica desde el mismo servicio compartido.
- Los KPIs de pantalla se calculan sobre todo el conjunto filtrado; solo la tabla visual limita a 500 filas.
- La UI informa cuando el detalle visual está limitado y aclara que Excel contiene todo el filtro.
- XLSX incorpora Documentados %, primera respuesta, resolución, trabajo efectivo, espera y cambios de estado.
- Estado/prioridad usan catálogos canónicos compartidos.
- `tests/phase10_ticket_sla_xlsx_regression.php` agregado a gate y CI.
- BD: sin cambios.

Siguiente: Task 5 — consolidación de Proveedores dentro del Centro de informes sin duplicar el informe especializado.

## Task 5 — Resumen de Proveedores · IMPLEMENTADA

- El Centro de informes incorpora un resumen ejecutivo sin duplicar la tabla especializada.
- Indicadores: proveedores, participaciones, activas, sin respuesta, calidad promedio y devoluciones.
- Contexto secundario: entregas, respuestas, archivos y primera respuesta promedio.
- El acceso al informe especializado conserva el mismo rango de fechas.
- `ProviderParticipationService::scopedRows()` aplica `ScopeService` sobre los tickets relacionados.
- El informe especializado de proveedores también respeta alcance tanto en pantalla como en XLSX.
- Calidad reutiliza `ProviderRatingService`; no se crea otro modelo de valoración.
- `tests/phase10_provider_report_regression.php` agregado a gate y CI.
- BD: sin cambios.

Siguiente: Task 6 — consolidación de Equipo de soporte dentro del Centro de informes sin duplicar `/gestion/equipo`.

## Task 6 — Resumen de Equipo de soporte · IMPLEMENTADA

- Se creó `SupportTeamReportService` como fuente compartida de integrantes, resumen especializado y resumen del Centro de informes.
- `SupportTeamController` ya no duplica las consultas de integrantes ni la agregación principal.
- Centro de informes: integrantes, tickets del período, resueltos, primera respuesta y NPS.
- Contexto secundario: activos, en proceso, en espera, resolución promedio y calificación promedio.
- El resumen usa `TicketReportFilterService`, por lo que hereda período, filtros y `ScopeService`.
- La pantalla especializada `/gestion/equipo` conserva gestión de integrantes y exportación XLSX; el Centro no duplica su tabla.
- Responsive integrado mediante el mismo patrón visual de Reportes.
- `tests/phase10_support_team_report_regression.php` agregado a gate y CI.
- BD: sin cambios.

Siguiente: Task 7 — cierre de exportaciones y consistencia de los informes especializados (general, proveedores y equipo), seguido por Task 8 UI/responsive y closeout de Fase 10.

## Task 7 — Exportaciones especializadas · IMPLEMENTADA

- Se revisó como una sola familia: informe general, Proveedores y Equipo de soporte.
- General mantiene filtros/Scope compartidos y documenta filtros en el XLSX.
- Proveedores ahora documenta filtros + alcance en el archivo y alinea el resumen con pantalla: proveedores, participaciones, activas, sin respuesta, primera respuesta, entregas, devoluciones y calidad.
- Equipo de soporte incorpora `from/to` en pantalla especializada, conserva el período al exportar y separa claramente métricas del período de carga actual.
- El Centro de informes conserva el período al abrir `/gestion/equipo`.
- Auditoría de exportación de Equipo registra filtros; Proveedores registra filtros y alcance.
- Todas las exportaciones siguen usando `XlsxExportService` y el smoke OOXML común.
- `tests/phase10_specialized_exports_regression.php` agregado a gate y CI.
- BD: sin cambios.

Siguiente: Task 8 — UI/responsive, documentación de cierre y gate final de Fase 10.

## Task 8 — UI / responsive / closeout · IMPLEMENTADA EN CÓDIGO

- Centro de informes conserva tablet en 2 columnas cuando aporta y pasa métricas a 1 columna en móvil pequeño (<=430px).
- Equipo de soporte pasa filtros/resumen/botonera a 1 columna en móvil pequeño.
- Informe de proveedores apila resumen, acciones y filtros en móvil pequeño.
- Paneles anclados del Centro usan `scroll-margin-top` para no quedar ocultos por el topbar.
- Se mantiene contrato global de botones; no se crean variantes locales.
- README marca Fase 10 como implementada y Fase 11 Manual como siguiente.
- CHANGELOG incorpora Fase 10 canónica y aclara el uso actual de `2.4.0-dev` frente al bloque histórico archivado.
- `tests/phase10_ui_responsive_regression.php` y `tests/phase10_closeout_regression.php` agregados a gate y CI.
- `VALIDAR_FASE10.bat` pasa a ser gate final de la fase.
- BD: sin cambios.
- Producción: sin cambios.

### Estado para cierre

Pendiente únicamente:
1. sincronizar PC TEST;
2. ejecutar regresiones nuevas;
3. ejecutar `VALIDAR_FASE10.bat` completo;
4. registrar evidencia GREEN;
5. iniciar Fase 11 — Manual.

La validación transversal claro/oscuro + 1920 + 1366 + iPad H/V + móvil se consolida finalmente en Fase 12, sin reabrir funcionalidad de Reportes salvo defecto real.
