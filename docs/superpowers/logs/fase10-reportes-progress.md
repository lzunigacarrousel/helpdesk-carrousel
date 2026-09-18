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
