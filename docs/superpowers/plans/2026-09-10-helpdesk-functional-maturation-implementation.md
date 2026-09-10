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

Estado: **IMPLEMENTADA / PENDIENTE VALIDACIÓN VISUAL EN PC TEST**.

Objetivo de BD: **0 cambios — cumplido**.

- [x] Simplificar `app/Views/tickets/public_create.php` sin reconstruir el flujo de tickets.
- [x] Presentar categorías en lenguaje humano sin cambiar `ticket_categories` ni sus IDs.
- [x] Agregar ayuda contextual corta por selección.
- [x] Reutilizar nombre, correo y teléfono del usuario autenticado; nombre/correo se toman del servidor y no de campos ocultos manipulables.
- [x] Reutilizar una única asignación activa para proponer parque/área cuando sea inequívoca.
- [x] Mantener opción secundaria `Reportar en otro lugar` para cambiar la ubicación propuesta.
- [x] Mantener `subject` generado, CSRF, creación de ticket, SLA, notificaciones y modelo actual.
- [x] Agregar `tests/requester_ux_smoke.php` y ejecutarlo en GitHub Actions.
- [x] Actualizar el Manual integrado para explicar el flujo humano y la ubicación automática.
- [x] Validar automáticamente sintaxis PHP/JS, static checks, dark theme, searchable selects, project quality y XLSX.
- [ ] Validación visual manual en PC TEST: usuario autenticado y no autenticado; 1920, 1366, iPad/tablet y móvil.

## Fase 3 — Operación IT y SLA

Estado: **PENDIENTE**.

Objetivo de BD: **0 cambios**.

- [ ] Centralizar presentación de SLA con tiempo restante, porcentaje utilizado y estado operativo.
- [ ] Corregir la cola para trabajar sobre todos los tickets visibles según scope, no solo propios + sin asignar.
- [ ] Conservar filtros: míos, sin asignar, por vencer, vencidos, espera, reabiertos y críticos.
- [ ] Mejorar cálculo histórico de esperas por `pending_reason_code`, incluyendo cambios de motivo sin cambio de estado.
- [ ] Alinear acciones alrededor del estado `RESOLVED` y confirmación del solicitante.

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

Estado: **PENDIENTE / HARD GATE DE BD**.

- [ ] Evaluar formalmente si `ticket_events` resuelve el caso.
- [ ] Si no, presentar diseño completo de `ticket_activities` antes de modificar BD.
- [ ] Cubrir programación, técnico, parque, inicio/fin estimados, motivo, reprogramación, inicio, finalización y resultado.
- [ ] Reutilizar comentarios, adjuntos, eventos, auditoría, notificaciones, resolución y motivos de espera.
- [ ] No crear tablas separadas para visitas/remoto/proveedor/follow-up.

## Fase 6 — Agenda

Estado: **PENDIENTE Y DEPENDE DE FASE 5**.

- [ ] Usar la misma entidad de actividades si se aprueba.
- [ ] No crear tabla de calendario adicional.
- [ ] Filtrar por técnico, parque, tipo y estado.
- [ ] Enlazar cada actividad a su ticket.
- [ ] Validar escritorio, laptop, iPad y móvil.

## Fase 7 — Proveedores

Estado: **PENDIENTE**.

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

- [ ] Actualizar manual integrado según funciones realmente aprobadas.
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