# External Experience Normalization Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Unificar Usuarios, Proveedores externos y ticket externo con una sola línea visual y de ayuda, sin cambiar BD ni reglas funcionales.

**Architecture:** Mantener controladores, rutas, permisos y datos. Cambiar vistas y ayuda contextual mediante TDD. Cada superficie tiene una regresión RED independiente.

**Tech Stack:** PHP 8+, HTML/CSS, JavaScript vanilla, MariaDB existente, pruebas CLI PHP.

**Spec:** `docs/superpowers/specs/2026-09-12-external-experience-normalization-design.md`

## Global Constraints
- No cambiar BD, OTP, permisos, alcance, auditoría ni histórico.
- No cambiar compartir/revocar ni conversión interno↔externo.
- No exponer información interna al proveedor.
- Mantener CSRF y formularios actuales.
- Responsive: 3 columnas escritorio, 2 tablet, 1 móvil.

### Task 1: Alinear Usuarios
**Files:** `app/Views/admin/users.php`, nuevo `tests/admin_users_alignment_regression.php`.

- [ ] Crear RED que exija clases de alineación para `¿Qué representa esta cuenta?`, `Responsable directo` y grid organizacional; conservar `requester_entity_type`, `manager_user_id`, alta y edición.
- [ ] Ejecutar la prueba y confirmar que solo falla el layout nuevo.
- [ ] Implementar grid consistente en alta y edición, sin cambiar nombres de campos, endpoints ni `sync(form)`.
- [ ] Ejecutar `admin_users_alignment_regression.php`, `admin_users_edit_ux_regression.php` y lint PHP.
- [ ] Commit: `fix: alinear formularios de usuarios`.

### Task 2: Normalizar Proveedores externos
**Files:** `app/Views/admin/externals.php`, nuevo `tests/external_admin_ux_regression.php`.

- [ ] Crear RED que exija botón `Editar`, fila independiente de ancho completo, panel oculto inicialmente, cierre explícito, un solo editor abierto y responsive; conservar editar, desactivar, convertir, crear y compartir.
- [ ] Ejecutar RED.
- [ ] Mover edición, reconversión y desactivación fuera de la celda Acciones a una fila completa debajo del proveedor.
- [ ] Agregar JS para abrir/cerrar un único editor y mantener `aria-expanded`.
- [ ] Ejecutar nueva regresión más `external_provider_conversion_regression.php`, `internal_external_reversibility_regression.php` y lint.
- [ ] Commit: `feat: normalizar directorio de proveedores externos`.

### Task 3: Normalizar ticket del proveedor
**Files:** `app/Views/tickets/show.php`, nuevo `tests/external_ticket_ux_regression.php`.

- [ ] Crear RED que exija `external-case-shell`, `external-problem-card`, `external-participation-card`, `external-conversation-card`, `external-reply-card`, `external-solution-card`, y que mantenga comentarios/adjuntos públicos.
- [ ] Ejecutar RED.
- [ ] Para `$isExternal`, componer una vista enfocada en `Qué necesita Carrousel`, estado, `Tu participación`, conversación pública, respuesta/adjunto y solución; reutilizar las variables y permisos existentes.
- [ ] Mantener ITSM, SLA, ubicación histórica, problemas conocidos, sugerencias, acciones de soporte y conversación interna fuera de la rama externa.
- [ ] Ejecutar GREEN y lint.
- [ ] Commit: `feat: unificar experiencia del ticket externo`.

### Task 4: Manual y tutorial
**Files:** `app/Views/help/manual.php`, `public/assets/js/help-tour.js`, opcional `app/Views/shared/help_widget.php`, nuevo `tests/external_help_ux_regression.php`.

- [ ] Crear RED para casos compartidos, responder, adjuntar, revocación, conversión interno↔externo y nuevos selectores visuales.
- [ ] Ejecutar RED.
- [ ] Actualizar manual por perfil y tutorial con editor expandido, `Tu participación`, respuesta y solución.
- [ ] Ejecutar `external_help_ux_regression.php`, `ux_help_notifications_regression.php`, `ux_tour_visual_regression.php` y lint.
- [ ] Commit: `docs: alinear ayuda con experiencia de externos`.

### Task 5: Gate final
- [ ] Ejecutar todas las regresiones nuevas.
- [ ] Ejecutar conversiones/reversibilidad, ITSM 2.1, alcance, ayuda y tutorial existentes.
- [ ] Ejecutar lint de las cuatro vistas, `git diff --check` y `git status`.
- [ ] QA manual: Usuarios; crear/editar/compartir/revocar/desactivar/convertir proveedor; OTP/dashboard/Mis casos/ticket/respuesta/adjunto/solución como externo.
- [ ] Repetir en claro/oscuro y PC/iPad/móvil.
- [ ] Solo con aprobación visual, integrar `ux-admin-users-edit` a `main` y publicar.