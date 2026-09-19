# Fase 12 — Checklist de aceptación operativa

Completar en PC TEST antes del closeout final.

## A. Estado técnico automatizado

- [x] `VALIDAR_FASE12.bat` GREEN.
- [x] `VALIDAR_FASE12_BD.bat` GREEN.
- [x] `VALIDAR_FASE12_SEGURIDAD.bat` GREEN.
- [x] `VALIDAR_FASE12_E2E.bat` GREEN.
- [x] `VALIDAR_FASE12_COMUNICACION.bat` GREEN.
- [x] `VALIDAR_FASE12_VISUAL.bat` GREEN.
- [x] `VALIDAR_FASE12_ESTABILIDAD.bat` GREEN.

## B. Runtime y almacenamiento

- [ ] PHP no muestra warnings/notices al usuario.
- [ ] `storage/logs` es escribible.
- [ ] `storage/ticket_uploads` es escribible.
- [ ] Adjuntar PDF válido funciona.
- [ ] Adjuntar imagen JPG/PNG/WebP funciona.
- [ ] Archivo >10 MB es rechazado con mensaje legible.
- [ ] Tipo no permitido es rechazado.
- [ ] Nota interna con adjunto no se expone a solicitante/proveedor.
- [ ] Adjunto de proveedor no se expone al solicitante.
- [ ] Descarga de adjunto no deja overlay bloqueado.

## C. Exportaciones

- [ ] Informe general XLSX descarga y abre en Excel sin reparación.
- [ ] Filtros visibles se reflejan en la exportación.
- [ ] Exportación de proveedor abre correctamente.
- [ ] Exportaciones respetan permisos/scope.

## D. Navegación

- [ ] Sidebar/topbar llevan a las rutas correctas.
- [ ] Campana abre el caso correcto.
- [ ] Marcar notificación no retrasa navegación.
- [ ] Ayuda/tutorial/manual abren sin romper la pantalla.
- [ ] No hay enlaces 404 en flujos principales.

## E. Rendimiento básico

- [ ] Dashboard carga sin espera anormal.
- [ ] Cola carga sin espera anormal.
- [ ] Ticket con conversación carga sin espera anormal.
- [ ] Agenda carga sin espera anormal.
- [ ] Centro de informes carga sin espera anormal.
- [ ] Búsqueda responde sin espera anormal.

## F. Visual

- [ ] Matriz `fase12-visual-responsive-validacion-manual.md` completada.
- [ ] Claro y oscuro revisados.
- [ ] 1920×1080 revisado.
- [ ] 1366×768 revisado.
- [ ] iPad horizontal revisado.
- [ ] iPad vertical revisado.
- [ ] Móvil revisado.

## G. Comunicación

- [x] Gate automatizado de correo/notificaciones GREEN.
- [ ] URL canónica pública del Helpdesk definida.
- [ ] `phase12_mail_health.php --require-canonical` GREEN.
- [ ] SMTP real probado desde `/admin/correo`.
- [ ] Gmail revisado.
- [ ] Outlook revisado o marcado No disponible.

## H. Defectos abiertos

Registrar cualquier defecto pendiente:

| ID | Severidad | Ruta | Perfil | Estado | Observación |
| --- | --- | --- | --- | --- | --- |
| | | | | | |

### Criterio de cierre

No cerrar Fase 12 con defectos bloqueantes. Pendientes externos no bloqueantes deben quedar documentados con causa, responsable y condición de cierre.

El cierre técnico de Fase 12 no autoriza automáticamente producción.
