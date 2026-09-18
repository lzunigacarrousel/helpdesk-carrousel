# Fase 10 — Reportes consolidados

Fecha: 2026-09-18
Rama vigente: `main`

## Objetivo

Consolidar los reportes ya existentes sin reconstruirlos ni crear otro sistema paralelo.

La Fase 10 debe convertir `/gestion/informes` en el punto central de consulta para Gerencia, Supervisión, Admin y perfiles con permiso de reportes, reutilizando los módulos existentes de tickets, SLA, actividades, proveedores, equipo y conocimiento.

## Principios

- No crear tablas nuevas por defecto.
- Reutilizar `ScopeService`, `TicketLifecycleService`, `ProviderParticipationService`, `ProviderRatingService`, `KnowledgeMetricsService` y `XlsxExportService`.
- Los filtros y exportaciones deben respetar alcance backend.
- Gerencia/Supervisión consultan; no reciben acciones operativas.
- La pantalla principal debe ser compacta: resumen + accesos a informes + detalle.
- No duplicar datasets ni lógica de métricas entre pantalla y XLSX.
- Mantener claro/oscuro, responsive y contrato visual canónico de botones.

## Alcance Fase 10

1. Centro de informes único y navegable.
2. Consolidación del informe general de tickets/SLA ya existente.
3. Acceso directo a Agenda/Actividades, Proveedores y Equipo.
4. Indicadores de Conocimiento dentro del centro de informes cuando el perfil tenga permiso.
5. Consistencia de filtros, copy, botones y tablas.
6. Exportación XLSX consistente con filtros/alcance.
7. Regresiones y gate `VALIDAR_FASE10.bat`.
8. README/CHANGELOG/log de continuidad.

## Fuera de alcance

- Power BI.
- Nuevas integraciones externas.
- Nuevos frameworks.
- Nuevas tablas salvo evidencia indispensable.
- Predicciones/IA.
- Cambios de producción.

## Criterios de aceptación

- `/gestion/informes` funciona como centro real de reportes.
- Cada acceso visible corresponde a una ruta permitida para el perfil.
- Gerencia y Supervisión no obtienen acciones de operación.
- Conocimiento aporta métricas legibles sin exponer tecnicismos.
- XLSX sigue abriendo correctamente.
- PC TEST y gate Fase 10 quedan GREEN antes de producción.
