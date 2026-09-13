# External Experience Normalization Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Unificar Usuarios, Proveedores externos y ticket externo con una sola línea visual y capturar información operativa real y obligatoria del proveedor.

**Architecture:** Mantener identidad, permisos, OTP, auditoría, compartir/revocar y conversación existentes. Ampliar `external_profiles` con campos estructurados mínimos; reutilizar `ticket_comments` para conversación externa y `ticket_events.metadata_json` para registrar actualizaciones estructuradas del proveedor. Cada superficie se implementa con TDD y commits pequeños.

**Tech Stack:** PHP 8+, HTML/CSS, JavaScript vanilla, MariaDB 10.4+, pruebas CLI PHP.

**Spec:** `docs/superpowers/specs/2026-09-12-external-experience-normalization-design.md`

## Global Constraints
- No cambiar OTP, permisos, alcance, identidad ni histórico.
- No cambiar compartir/revocar ni conversión interno↔externo salvo exigir perfil externo completo.
- No exponer información interna al proveedor.
- Mantener CSRF y auditoría.
- Responsive: 3 columnas escritorio, 2 tablet, 1 móvil.
- Toda modificación de BD debe tener migración idempotente, `INSTALAR.sql` canónico y verificación.

### Task 1: Alinear Usuarios — COMPLETADO
**Files:** `app/Views/admin/users.php`, `tests/admin_users_alignment_regression.php`, `tests/admin_users_edit_ux_regression.php`.

- [x] RED de alineación.
- [x] Grid consistente en alta y edición.
- [x] `Qué representa` y `Responsable directo` alineados.
- [x] Responsive y regresión del editor expandidos en GREEN.
- [x] Commit: `fix: alinear formularios de usuarios`.

### Task 2A: Perfil operativo obligatorio de proveedor externo
**Files:**
- Modify: `database/INSTALAR.sql`
- Modify: `database/VERIFICAR_INSTALACION.sql`
- Create: `database/MIGRAR_EXTERNAL_PROFILES_V2_20260912.sql`
- Create: `database/VERIFICAR_EXTERNAL_PROFILES_V2_20260912.sql`
- Modify: `app/Controllers/ExternalController.php`
- Modify: `app/Views/admin/externals.php`
- Modify: `app/Views/admin/users.php` (formulario interno → externo)
- Test: `tests/external_provider_profile_regression.php`

- [ ] **Step 1:** Crear RED que exija `contact_position`, `service_name`, `support_reference`, migración idempotente y campos obligatorios en alta/edición/conversión.
- [ ] **Step 2:** Ejecutar RED y confirmar que falla solo por el nuevo contrato de perfil.
- [ ] **Step 3:** Agregar columnas canónicas a `external_profiles`; crear migración segura para instalaciones existentes y verificador específico.
- [ ] **Step 4:** Leer/escribir los nuevos campos desde `ExternalController::index`, `createUser`, `updateUser` y `convertInternal`.
- [ ] **Step 5:** Exigir organización, contacto, cargo, correo, teléfono y servicio principal en backend; mantener referencia y notas opcionales.
- [ ] **Step 6:** Actualizar alta/edición/conversión para enviar los nuevos nombres de campo sin tocar OTP ni identidad.
- [ ] **Step 7:** Ejecutar nueva regresión + `external_provider_conversion_regression.php` + `internal_external_reversibility_regression.php` + lint.
- [ ] **Step 8:** Commit: `feat: completar perfil operativo de proveedores externos`.

### Task 2B: Normalizar Administración de Proveedores
**Files:** `app/Views/admin/externals.php`, `tests/external_admin_ux_regression.php`.

- [ ] **Step 1:** Crear RED que exija alta en panel completo tipo Usuarios, botón `Editar`, fila independiente de ancho completo, cierre explícito, un solo editor y responsive.
- [ ] **Step 2:** Ejecutar RED.
- [ ] **Step 3:** Sustituir `<details>` de alta por panel administrado con botón `+ Registrar proveedor`.
- [ ] **Step 4:** Mover edición, reconversión y desactivación fuera de `Acciones` a una fila completa debajo del proveedor.
- [ ] **Step 5:** Agregar JS para abrir/cerrar un único editor y mantener `aria-expanded`.
- [ ] **Step 6:** Mostrar en directorio empresa, tipo, contacto, servicio, estado, casos y acción.
- [ ] **Step 7:** Ejecutar GREEN + regresiones de conversión/reversibilidad + lint.
- [ ] **Step 8:** Commit: `feat: normalizar administración de proveedores externos`.

### Task 3A: Captura estructurada de actualización del proveedor
**Files:**
- Modify: `app/Controllers/ConversationController.php`
- Modify: `app/Views/tickets/show.php`
- Test: `tests/external_ticket_update_regression.php`

- [ ] **Step 1:** Crear RED para `update_type` obligatorio con valores `PROGRESS`, `INFO_REQUEST`, `WORK_COMPLETED`.
- [ ] **Step 2:** Exigir `detail` siempre; para `PROGRESS`, exigir `progress_percent` 0..100 y `commitment_at`.
- [ ] **Step 3:** Mantener `provider_reference` y archivo opcionales; respetar `can_comment`/`can_upload`.
- [ ] **Step 4:** Ejecutar RED.
- [ ] **Step 5:** En backend externo, convertir los datos estructurados en comentario visible EXTERNAL y crear evento `EXTERNAL_UPDATE_ADDED` con metadata JSON.
- [ ] **Step 6:** No aplicar las nuevas validaciones a solicitantes ni soporte interno.
- [ ] **Step 7:** Ejecutar GREEN y lint.
- [ ] **Step 8:** Commit: `feat: estructurar actualizaciones de proveedores`.

### Task 3B: Normalizar ticket del proveedor
**Files:** `app/Views/tickets/show.php`, `tests/external_ticket_ux_regression.php`.

- [ ] **Step 1:** Crear RED que exija `external-case-shell`, `external-problem-card`, `external-participation-card`, `external-conversation-card`, `external-reply-card`, `external-solution-card`.
- [ ] **Step 2:** Ejecutar RED.
- [ ] **Step 3:** Para `$isExternal`, componer una vista enfocada en `Qué necesita Carrousel`, estado, `Tu participación`, conversación compartida, actualización estructurada y solución.
- [ ] **Step 4:** Reemplazar los botones que rellenan texto prefabricado por selector de tipo + campos condicionales.
- [ ] **Step 5:** Mantener ITSM, SLA, ubicación histórica, problemas conocidos, sugerencias, acciones de soporte y conversación interna fuera de la rama externa.
- [ ] **Step 6:** Responsive PC/tablet/móvil y claro/oscuro.
- [ ] **Step 7:** Ejecutar GREEN y lint.
- [ ] **Step 8:** Commit: `feat: unificar experiencia del ticket externo`.

### Task 4: Manual, tutorial y ayuda contextual
**Files:** `app/Views/help/manual.php`, `public/assets/js/help-tour.js`, opcional `app/Views/shared/help_widget.php`, `tests/external_help_ux_regression.php`.

- [ ] **Step 1:** Crear RED para perfil obligatorio, alta/edición, compartir/revocar, conversiones y tipos de actualización externa.
- [ ] **Step 2:** Ejecutar RED.
- [ ] **Step 3:** Actualizar manual de Administrador con perfil de proveedor y conversiones.
- [ ] **Step 4:** Actualizar manual/tutorial de proveedor con avance, solicitud de información, trabajo realizado y adjuntos.
- [ ] **Step 5:** Ejecutar `external_help_ux_regression.php`, `ux_help_notifications_regression.php`, `ux_tour_visual_regression.php` y lint.
- [ ] **Step 6:** Commit: `docs: alinear ayuda con experiencia de proveedores`.

### Task 5: Gate final
- [ ] Ejecutar `admin_users_alignment_regression.php` y `admin_users_edit_ux_regression.php`.
- [ ] Ejecutar `external_provider_profile_regression.php`.
- [ ] Ejecutar `external_admin_ux_regression.php`.
- [ ] Ejecutar `external_ticket_update_regression.php` y `external_ticket_ux_regression.php`.
- [ ] Ejecutar `external_help_ux_regression.php`.
- [ ] Ejecutar conversiones/reversibilidad, ITSM 2.1, alcance, ayuda y tutorial existentes.
- [ ] Ejecutar migración y verificación de perfil externo en PC TEST.
- [ ] Ejecutar lint de archivos modificados, `git diff --check` y `git status`.
- [ ] QA manual: Usuarios; crear/editar/compartir/revocar/desactivar/convertir proveedor; OTP/dashboard/Mis casos/ticket; avance/solicitud/trabajo realizado; adjuntos y solución.
- [ ] Repetir en claro/oscuro y PC/iPad/móvil.
- [ ] Solo con aprobación visual, integrar `ux-admin-users-edit` a `main` y publicar.