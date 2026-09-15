# Helpdesk Carrousel V2 — Functional Maturation Implementation Plan

**Rama:** `ui-normalization-working`

**Spec:** `docs/superpowers/specs/2026-09-10-helpdesk-functional-maturation-design.md`

**Objetivo:** madurar la implementación existente sin reconstruirla, manteniendo la línea visual actual, la seguridad y la base canónica.

## Reglas globales

- Fuente de verdad: `ui-normalization-working`.
- Base V2: `carrousel_helpdesk`.
- Base histórica protegida: `helpdesk_carrousel`.
- Cero cambios estructurales de BD por defecto.
- Ningún `ALTER TABLE` suelto ni migración manual temporal.
- Si se aprueba estructura nueva, actualizar `database/INSTALAR.sql`, `database/VERIFICAR_INSTALACION.sql`, `database/VERIFICAR_ESTABILIDAD_V2.sql` cuando corresponda, CI, quality gates y documentación.
- No repetir la normalización visual general.
- No debilitar OTP, permisos, scopes, CSRF, auditoría, sesiones ni separación INTERNAL/EXTERNAL.
- No mezclar dos fases funcionales grandes en un mismo cambio.

## Protocolo por fase

Para cada fase aprobada:

1. revisar implementación existente;
2. escribir o actualizar pruebas relacionadas cuando aplique;
3. implementar únicamente esa fase;
4. validar sintaxis PHP;
5. validar JavaScript;
6. ejecutar static checks;
7. ejecutar project quality;
8. ejecutar XLSX smoke;
9. ejecutar pruebas relacionadas;
10. validar permisos;
11. validar CSRF;
12. validar auditoría;
13. validar notificaciones;
14. validar claro;
15. validar oscuro;
16. validar responsive relevante;
17. revisar diff;
18. verificar ausencia de secretos;
19. actualizar documentación;
20. documentar exactamente qué cambió.

## Fase 1 — Baseline y documentación

Estado: **COMPLETADA EN DOCUMENTACIÓN**.

- [x] Auditar README, SQL canónico, verificadores, CI y documentación Superpowers.
- [x] Confirmar `carrousel_helpdesk` como V2 activa.
- [x] Confirmar `helpdesk_carrousel` como histórica protegida.
- [x] Corregir la referencia obsoleta del README que presentaba `v2-rebuild` como rama activa.
- [x] Marcar documentación de normalización UI como histórica.
- [x] Corregir la instrucción histórica errónea que decía usar `helpdesk_carrousel` como base activa.
- [x] Crear especificación y plan de maduración funcional.
- [x] No modificar código funcional, SQL, rutas, permisos, CSS ni JavaScript.

## Fase 2 — UX solicitante

Estado: **IMPLEMENTADA / VALIDADA EN PC TEST PARA FLUJO PRINCIPAL**.

Objetivo de BD: **0 cambios — cumplido**.

- [x] Simplificar `app/Views/tickets/public_create.php` sin reconstruir el flujo de tickets.
- [x] Presentar temas en lenguaje humano, basados en solicitudes históricas reales, sin cambiar `ticket_categories` ni sus IDs.
- [x] Agregar ayuda y ejemplo contextual según el tema seleccionado.
- [x] Reutilizar nombre, correo y teléfono del usuario autenticado; nombre/correo se toman del servidor y no de campos ocultos manipulables.
- [x] Reutilizar una única asignación activa para proponer parque/área cuando sea inequívoca.
- [x] Mantener opción secundaria `Reportar en otro lugar` para cambiar la ubicación propuesta.
- [x] Mantener `subject` generado, CSRF, creación de ticket, SLA, notificaciones y modelo actual.
- [x] Agregar `RequesterTopicService` y `tests/requester_ux_smoke.php`.
- [x] Actualizar el Manual integrado.
- [x] Validar automáticamente sintaxis PHP/JS, static checks, dark theme, searchable selects, project quality y XLSX.
- [ ] Completar validación responsive acumulada en 1920, 1366, iPad/tablet y móvil durante la validación integral.

## Fase 3 — Operación IT y SLA

Estado: **IMPLEMENTADA / PENDIENTE VALIDACIÓN VISUAL EN PC TEST**.

Objetivo de BD: **0 cambios — cumplido**.

- [x] Crear `SlaPresentationService` para centralizar tiempo restante, porcentaje utilizado y estado operativo.
- [x] Clasificar SLA como `Dentro de objetivo`, `Atención requerida`, `Próximo a vencer` o `Vencido` usando las fechas existentes.
- [x] Corregir la cola para consultar todos los tickets operativos visibles según `ScopeService`, no solo propios + sin asignar.
- [x] Mantener filtros `Míos`, `Sin asignar`, `Por vencer`, `Vencidos`, `En espera`, `Reabiertos` y `Críticos`, además de `Todos` y `En proceso`.
- [x] Mostrar responsable real y lectura SLA en la cola.
- [x] Aplicar el alcance también al backend al tomar un caso; no depender únicamente de la visibilidad del botón.
- [x] Permitir abrir desde la cola/reportes únicamente tickets autorizados por scope, conservando el aislamiento EXTERNAL.
- [x] Crear `TicketLifecycleService` para centralizar tiempos del ciclo de vida.
- [x] Separar históricamente minutos de espera por `pending_reason_code`, incluyendo `PENDING_REASON_CHANGED` sin cambio de estado.
- [x] Reutilizar el ciclo central en Informes y XLSX para evitar cálculos divergentes.
- [x] Añadir en XLSX la hoja de esperas con casos activos y minutos históricos por motivo.
- [x] Eliminar de la interfaz IT el cierre manual desde `RESOLVED`; la solución queda pendiente de confirmación del solicitante y se conserva `Reabrir`.
- [x] Agregar `tests/phase3_operational_smoke.php` al CI.
- [x] Actualizar Manual integrado con cola, SLA, espera histórica y cierre por confirmación.
- [ ] Validación visual manual en PC TEST del Centro de soporte y workspace en claro/oscuro; revisión específica 1366/iPad/móvil se consolida en Fase 12.

## Fase 4 — Feedback

Estado: **PENDIENTE / REQUIERE GATE DE DISEÑO PARA ESTRELLAS**.

Objetivo inicial de BD: **0 cambios**.

- [ ] UX `¿Tu problema quedó resuelto? Sí / No`.
- [ ] No → reutilizar reapertura actual.
- [ ] Sí → reutilizar confirmación/cierre actual.
- [ ] Mantener comentario opcional.
- [ ] Antes de persistir estrellas, diseñar compatibilidad con `nps_score` histórico.
- [ ] No modificar `ticket_feedback` sin aprobación específica.

## Fase 5 — Actividades / visitas

Estado: **IMPLEMENTADA / PENDIENTE VALIDACIÓN INTEGRAL FASE 12**.

- [x] Evaluar formalmente `ticket_events`: se conserva como historial inmutable y no sustituye el estado operativo consultable.
- [x] Diseñar e implementar `ticket_activities` y `ticket_activity_participants` con migración incremental, esquema canónico y verificadores.
- [x] Cubrir programación, responsable, participantes, parque, inicio/fin estimados, objetivo, reprogramación, inicio, finalización, cancelación y resultado.
- [x] Reutilizar `ticket_attachments` mediante `activity_id` opcional y conservar `ticket_events`, auditoría y notificaciones como trazabilidad.
- [x] Implementar permisos `activities.view/create/manage/cancel`, validación de scope y CSRF.
- [x] Implementar UI interna con visita en sitio, soporte remoto, seguimiento, intervención de proveedor y otra atención.
- [x] Mantener independencia entre actividad y ticket: ninguna transición de actividad cambia automáticamente el estado del caso.
- [x] Implementar resumen seguro **Próxima atención** para solicitante, limitado a información publicada por soporte.
- [x] No crear tablas separadas por tipo de actividad.
- [ ] Validación responsive acumulada y cierre transversal se consolidan en Fase 12.

## Fase 6 — Agenda

Estado: **IMPLEMENTADA / PENDIENTE VALIDACIÓN INTEGRAL FASE 12**.

Objetivo de BD: **0 cambios — cumplido**.

- [x] Usar la misma entidad de actividades aprobada en Fase 5: `ticket_activities`.
- [x] No crear tabla de calendario adicional.
- [x] Implementar vistas Calendario y Lista.
- [x] Filtrar por técnico/responsable, parque, tipo, estado y rango de fechas.
- [x] Enlazar cada actividad a su ticket y conservar el ticket como workspace operativo.
- [x] Aplicar `ScopeService` en backend para toda consulta de Agenda.
- [x] Separar actividades activas e historial sin duplicar datos.
- [x] Derivar atrasadas y conflictos desde fechas existentes, sin estados paralelos.
- [x] Mantener REQUESTER y EXTERNAL sin acceso a Agenda.
- [x] Mantener MANAGEMENT y SUPERVISOR como consulta sin operar.
- [ ] Validación responsive acumulada y cierre transversal se consolidan en Fase 12.
## Fase 7 — Proveedores

Estado: **SIGUIENTE FASE / PENDIENTE**.

Objetivo de BD: **0 cambios**.

- [ ] Mantener `external_ticket_access`, perfiles, comentarios y adjuntos actuales.
- [ ] Calcular asignación, primera respuesta, tiempo hasta primera respuesta, duración y actividad actual.
- [ ] Registrar trabajo realizado sin permitir que el proveedor cierre técnicamente el ticket.
- [ ] Incorporar devoluciones/reaperturas al análisis de participación.
- [ ] Conservar aislamiento de tickets y datos internos.

## Fase 8 — Calidad IT → proveedor

Estado: **PENDIENTE / GATE DE DISEÑO**.

- [ ] Separar métrica de satisfacción de usuario y valoración de proveedor.
- [ ] Intentar primero `ticket_events` para una evaluación inmutable por ciclo.
- [ ] Solo proponer tabla nueva si eventos resultan insuficientes.
- [ ] La métrica debe alimentar reportes; si no, no implementarla.

## Fase 9 — Conocimiento

Estado: **PENDIENTE**.

Objetivo de BD: **0 cambios previstos**.

- [ ] Potenciar `SolutionSuggestionService`.
- [ ] Mostrar caso similar, artículo o problema conocido con coincidencia, resumen y enlace.
- [ ] Facilitar creación de artículo desde resolución usando `knowledge_articles` existente.
- [ ] No integrar IA externa.

## Fase 10 — Reportes

Estado: **PENDIENTE**.

Objetivo de BD: **0 cambios previstos**.

- [ ] IT: volumen, abiertos, cerrados, reabiertos, SLA, primera respuesta, resolución, vencidos, carga y documentación.
- [ ] Tiempos: cola, trabajo IT y esperas desglosadas.
- [ ] Parques: casos, categorías, recurrencia, resolución, satisfacción y tendencia.
- [ ] Proveedores: participación, primera respuesta, duración, actividad, reaperturas y valoración IT cuando exista.
- [ ] Actividades: visitas, técnicos, reprogramaciones, duración y resultado cuando Fase 5 exista.
- [ ] Mantener exportaciones XLSX coherentes con filtros y scopes.

## Fase 11 — Manual

Estado: **PENDIENTE**.

- [ ] Consolidar el manual integrado según funciones realmente aprobadas; Fases 2 y 3 ya incorporaron su documentación contextual.
- [ ] Solicitante: crear, seguir, responder y confirmar solución.
- [ ] IT: tomar, reasignar, responder, conversación interna, espera, proveedor, visita y resolución.
- [ ] Proveedor: consultar, responder, adjuntar y registrar trabajo.
- [ ] Gerencia/Supervisión: interpretación de KPIs.

## Fase 12 — Validación integral

Estado: **PENDIENTE**.

- [ ] PHP syntax.
- [ ] JavaScript syntax.
- [ ] Static checks.
- [ ] Project quality.
- [ ] XLSX smoke.
- [ ] Pruebas funcionales acumuladas.
- [ ] Fresh MariaDB desde `database/INSTALAR.sql`.
- [ ] `VERIFICAR_INSTALACION.sql`.
- [ ] `VERIFICAR_ESTABILIDAD_V2.sql`.
- [ ] Permisos y scopes.
- [ ] CSRF y auditoría.
- [ ] Notificaciones y archivos.
- [ ] Claro / oscuro / system.
- [ ] 1920, 1366, iPad 1024/768 y móvil <=760.
- [ ] Diff final y revisión de secretos.

## Stop conditions

Detener implementación y pedir aprobación cuando:

- una fase requiera cambio estructural de BD;
- una métrica cambie significado histórico;
- sea necesario crear un permiso nuevo;
- una función pueda debilitar aislamiento EXTERNAL/INTERNAL;
- una prueba crítica falle de manera no explicada;
- el alcance de una fase empiece a mezclarse con otra fase grande.
