# ITSM 2.1 Classification Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Diferenciar Incidentes de Solicitudes de servicio y calcular prioridad con Impacto + Urgencia sin reutilizar `tickets.case_type`.

**Architecture:** `tickets.case_type` conserva exclusivamente la semántica `NORMAL/SPECIAL` usada para colaboración externa. ITSM 2.1 agrega `request_type`, `impact`, `urgency` y `priority_source`; la matriz vive en `TicketClassificationService`, mientras `TicketClassificationController` permite reclasificación auditada por soporte. Los tickets históricos conservan su prioridad y se marcan como `LEGACY`.

**Tech Stack:** PHP 8+, MariaDB 10.4+, PDO, HTML/CSS/JS existente, pruebas PHP CLI.

**Spec:** conversación de diseño ITSM 2.1 previa a la implementación.

## Global Constraints

- Rama objetivo: `itsm-2-incident-service-request`.
- Punto base: commit `8e484e9`.
- No reutilizar ni cambiar la semántica de `tickets.case_type`.
- No ejecutar SQL histórico desde `database/`; `database/INSTALAR.sql` sigue siendo el esquema canónico.
- Los tickets existentes conservan `priority` y usan `priority_source='LEGACY'`.
- El usuario final ve lenguaje humano: “Tengo un problema” / “Necesito algo”.
- La prioridad nueva se calcula por Impacto + Urgencia; soporte puede ajustarla con permiso y auditoría.
- La reclasificación no reinicia el reloj: recalcula el SLA simple desde `created_at`.
- Todo se prueba primero en PC TEST; no hacer commit/push desde el aplicador.

---

### Task 1: Modelo y matriz de prioridad

**Files:**
- Create: `app/Services/TicketClassificationService.php`
- Modify: `database/INSTALAR.sql`
- Modify: `database/VERIFICAR_INSTALACION.sql`
- Test: `tests/itsm21_classification_regression.php`

**Interfaces:**
- Produces: `TicketClassificationService::calculatePriority(string $impact,string $urgency): string`
- Produces: etiquetas/opciones para tipo, impacto, urgencia y prioridad.

- [ ] Escribir la regresión y confirmar que falla sobre `8e484e9`.
- [ ] Agregar campos `request_type`, `impact`, `urgency`, `priority_source`.
- [ ] Agregar permiso `tickets.classify` a ADMIN, SEMIADMIN y TECHNICIAN.
- [ ] Implementar matriz y etiquetas.
- [ ] Ejecutar `php tests/itsm21_classification_regression.php`.

### Task 2: Alta pública con lenguaje humano

**Files:**
- Modify: `app/Controllers/TicketController.php`
- Modify: `app/Views/tickets/public_create.php`
- Create: `public/assets/css/itsm-classification.css`
- Modify: `app/Views/shared/app_start.php`

**Interfaces:**
- Consumes: `TicketClassificationService`.
- Produces en ticket nuevo: `request_type`, `impact`, `urgency`, `priority`, `priority_source='CALCULATED'`.

- [ ] Agregar “¿Qué necesitas?” con dos opciones.
- [ ] Preguntar “¿A quién está afectando?” y “¿Qué tan pronto necesitas resolverlo?”.
- [ ] Validar códigos en backend.
- [ ] Calcular prioridad exclusivamente con la matriz para tickets nuevos.
- [ ] Guardar clasificación en evento `CREATED`.
- [ ] Ejecutar regresión.

### Task 3: Reclasificación auditada por soporte

**Files:**
- Create: `app/Controllers/TicketClassificationController.php`
- Modify: `public/index.php`
- Modify: `app/Controllers/TicketController.php`
- Modify: `app/Views/tickets/show.php`

**Interfaces:**
- Produces: `POST /tickets/classification`.
- Requiere: permiso `tickets.classify`.
- Evento: `CLASSIFICATION_CHANGED`.
- Auditoría: `TICKET_CLASSIFICATION_CHANGED`.

- [ ] Verificar alcance del ticket con `ScopeService`.
- [ ] Permitir cambiar tipo/impacto/urgencia.
- [ ] Calcular prioridad sugerida.
- [ ] Permitir override de prioridad; si difiere, exigir nota.
- [ ] Recalcular SLA simple desde `created_at`, no desde el momento del cambio.
- [ ] Mostrar origen de prioridad.
- [ ] Ejecutar regresión.

### Task 4: Cola, detalle y ayuda

**Files:**
- Modify: `app/Views/tickets/queue.php`
- Modify: `app/Views/tickets/show.php`
- Modify: `public/assets/js/help-tour.js`
- Modify: `app/Views/shared/help_widget.php`
- Modify: `app/Views/help/manual.php`

**Interfaces:**
- Cola filtra por `request_type`.
- Cola muestra tipo y contexto de impacto/urgencia.
- Detalle muestra clasificación completa.

- [ ] Agregar filtro Incidente / Solicitud de servicio.
- [ ] Mostrar impacto y urgencia junto a prioridad.
- [ ] Actualizar tutorial de Solicitar ayuda.
- [ ] Actualizar Manual sin exponer jerga innecesaria al solicitante.
- [ ] Ejecutar suite completa.

### Task 5: Migración PC TEST

**Files externos al repositorio:**
- `MIGRAR_ITSM_2_1_20260912.sql`
- `VERIFICAR_ITSM_2_1_20260912.sql`

- [ ] Agregar columnas de forma idempotente.
- [ ] Clasificar tickets históricos como Incidente/Solicitud solo cuando el catálogo lo permite; no inventar impacto/urgencia.
- [ ] Conservar prioridad histórica y marcar `LEGACY`.
- [ ] Crear/asignar permiso `tickets.classify`.
- [ ] Verificar estructura y permisos en DBeaver.
- [ ] Ejecutar pruebas PHP completas y prueba funcional en navegador.
