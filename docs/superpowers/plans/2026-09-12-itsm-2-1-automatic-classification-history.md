# ITSM 2.1 Automatic Classification + History Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Simplificar el alta pública automatizando clasificación ITSM y mostrar identidad actual sin alterar el contexto histórico de cada ticket.

**Architecture:** `TicketClassificationService` concentra la inferencia. `TicketController` consume esa inferencia al crear el caso. Las consultas internas usan `users`/`user_assignments` para identidad actual y conservan `tickets.park_id/area_id` para el lugar histórico.

**Tech Stack:** PHP 8+, MariaDB, HTML/CSS/JS, pruebas de regresión PHP CLI.

**Spec:** `docs/superpowers/specs/2026-09-12-itsm-2-1-automatic-classification-history-design.md`

## Global Constraints
- No pedir términos ITSM al solicitante.
- No reescribir snapshot histórico de tickets.
- Mantener `case_type` intacto.
- Mantener reclasificación manual con auditoría.
- No requiere nueva migración SQL.

---

### Task 1: Clasificación automática
- [ ] Crear regresión que falle con el formulario ITSM visible.
- [ ] Implementar inferencia de tipo, impacto y urgencia.
- [ ] Cambiar alta pública para consumir la inferencia.
- [ ] Ejecutar regresiones ITSM.

### Task 2: Simplificación UX
- [ ] Retirar Tipo/Impacto/Urgencia del formulario público.
- [ ] Ocultar clasificación ITSM en detalle para solicitante.
- [ ] Actualizar tutorial y Manual.
- [ ] Ejecutar regresiones UX.

### Task 3: Identidad actual e histórico
- [ ] Vincular tickets sin `requester_user_id` al crear/editar usuario por correo.
- [ ] Mostrar identidad actual en cola, detalle, búsqueda, gestión y Excel.
- [ ] Mantener ubicación del caso desde `tickets.park_id/area_id`.
- [ ] Usar correo actual para notificaciones de tickets vinculados.
- [ ] Ejecutar regresión histórico/identidad.

### Task 4: Cierre
- [ ] Ejecutar sintaxis PHP de archivos tocados.
- [ ] Ejecutar suite completa ITSM 2.1.
- [ ] Revisar `git diff --check` y prueba visual antes de commit.
