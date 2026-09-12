# ITSM 2.1 — Clasificación automática e identidad histórica — Diseño

## Objetivo
Mantener la clasificación ITSM útil para soporte sin trasladar terminología técnica ni preguntas adicionales al solicitante. Separar además la identidad/ubicación organizacional actual del usuario del contexto histórico del ticket.

## Experiencia del solicitante
`/crear-ticket` vuelve al flujo simple: datos, ubicación opcional, catálogo humano, descripción y envío. El solicitante no responde Tipo, Impacto ni Urgencia y no ve la tarjeta de Clasificación ITSM en su ticket.

## Clasificación interna
`TicketClassificationService` infiere `request_type` desde el código de categoría, Impacto/Urgencia de forma conservadora desde el texto y calcula la prioridad con la matriz existente. Soporte mantiene `/tickets/classification` como autoridad para corregir Tipo, Impacto, Urgencia y prioridad manual con auditoría.

## Identidad actual vs contexto histórico
`tickets.requester_name`, `requester_email`, `requester_phone`, `park_id` y `area_id` permanecen como snapshot histórico. Las vistas internas, búsqueda, informes, Excel y notificaciones usan `users` cuando `requester_user_id` está vinculado. La ubicación del caso siempre se obtiene de `tickets.park_id/area_id`; la ubicación actual se obtiene de la asignación activa del usuario.

Al crear o editar un usuario desde Administración se vinculan tickets antiguos con el mismo correo cuando `requester_user_id` es NULL. Esta operación solo establece identidad; no reescribe snapshots históricos.

## Seguridad y trazabilidad
No se modifica el esquema ITSM 2.1 ya migrado. No se reescriben tickets históricos. Los cambios manuales de clasificación conservan permisos, evento, auditoría y recalculo SLA ya implementados.
