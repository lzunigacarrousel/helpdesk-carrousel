# ITSM 2.1 Requester Scope + Historical Location Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Clasificar cuentas solicitantes por entidad, restringir parques según su alcance y permitir corregir ubicación histórica con auditoría.

**Architecture:** `RequesterLocationPolicyService` centraliza la política de parques. Administración define si una cuenta representa Parque, Persona o Área/Departamento. `TicketLocationController` modifica únicamente la ubicación histórica del ticket con motivo y evento auditable.

**Tech Stack:** PHP 8+, MariaDB 10.4+, MVC existente, PDO, Git.

**Spec:** `docs/superpowers/specs/2026-09-12-itsm-2-1-requester-scope-location-design.md`

## Global Constraints

- No simplificar `case_type` ni reutilizarlo para ITSM.
- No sobrescribir tickets históricos que ya tengan parque durante backfill.
- Supervisión solo puede reportar parques dentro de su asignación.
- Cuenta Parque nunca puede reportar otro parque.
- Dashboard continúa usando `tickets.park_id`.

---

### Task 1: Modelo de cuenta solicitante
- [ ] Agregar `users.requester_entity_type`.
- [ ] Actualizar instalación canónica y verificación.
- [ ] Migrar PC TEST con default `PERSON`.

### Task 2: Política de ubicación
- [ ] Crear `RequesterLocationPolicyService`.
- [ ] Cuenta Parque => parque fijo.
- [ ] Supervisor => región/parque asignado.
- [ ] Resto => cualquier parque opcional.
- [ ] Validar nuevamente en backend.

### Task 3: Administración e histórico
- [ ] Exponer tipo de cuenta en `/admin/users`.
- [ ] Exigir parque cuando la cuenta representa Parque.
- [ ] Detectar tickets vinculados sin parque.
- [ ] Permitir backfill solo de `park_id IS NULL`, con motivo y eventos.

### Task 4: Corrección individual
- [ ] Crear POST `/tickets/location`.
- [ ] Exigir permiso, CSRF y motivo.
- [ ] Registrar antes/después, evento y auditoría.
- [ ] Mostrar control en detalle interno.

### Task 5: Verificación
- [ ] Ejecutar regresión específica.
- [ ] Ejecutar suite ITSM 2.1 completa.
- [ ] Prueba manual: Parque fijo, Supervisor restringido, Corporativo libre, backfill y corrección individual.
