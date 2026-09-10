# Helpdesk Carrousel V2 — Maduración funcional

Fecha: 2026-09-10
Rama de trabajo: `ui-normalization-working`
Estado: diseño aprobado para ejecución por fases.

## Objetivo

Convertir la implementación actual de Helpdesk Carrousel V2 en una plataforma de Service Desk más madura sin reconstruirla, sin repetir la normalización visual y sin debilitar seguridad, permisos, trazabilidad o funcionalidades ya operativas.

Principio central:

> Mejorar lo que ya existe sin romper lo que funciona.

La etapa debe priorizar cambios de lógica, presentación y explotación de datos existentes. Los cambios estructurales de base de datos son una excepción y requieren justificación previa.

## Fuente de verdad

La única rama de desarrollo para esta etapa es:

`ui-normalization-working`

La línea visual vigente se considera congelada y continúa definida por:

- `FernandoZL/PayOutParques` como referencia visual y de interacción.
- `docs/ESTANDAR_VISUAL_CARROUSEL.md` como estándar local del Helpdesk.

No se realizará una nueva normalización visual general.

## Bases de datos

### V2 activa

`carrousel_helpdesk`

### Histórica protegida

`helpdesk_carrousel`

La base histórica no debe eliminarse, modificarse, recrearse ni reutilizarse como V2.

Una inconsistencia documental sobre estos nombres se corrige en documentación. No se modifica lógica funcional únicamente por una inconsistencia documental.

## Regla estructural de BD

La meta de la etapa es **cero cambios estructurales de BD siempre que sea razonablemente posible**.

Antes de crear tabla, columna, índice, relación, enum o catálogo se debe intentar resolver la necesidad con las estructuras existentes, especialmente:

- `tickets`
- `ticket_events`
- `ticket_comments`
- `ticket_attachments`
- `ticket_resolutions`
- `ticket_feedback`
- `external_ticket_access`
- `external_profiles`
- `known_problems`
- `problem_occurrences`
- `knowledge_articles`
- `notification_events`
- `notification_deliveries`
- `audit_logs`
- `users`
- `user_assignments`
- `parks`
- `areas`
- `regions`
- `support_teams`
- `support_scopes`
- SLA existente.

No se permiten `ALTER TABLE` sueltos, SQL temporal, migraciones manuales olvidadas ni estructuras creadas directamente en phpMyAdmin. Si una nueva estructura se aprueba, debe incorporarse en el SQL canónico y en sus verificadores para que una instalación limpia produzca exactamente el esquema vigente.

## Criterio para una nueva tabla

Antes de aprobarla se debe demostrar:

1. problema real de negocio;
2. imposibilidad razonable de representarlo con estructuras existentes;
3. cardinalidad dentro del ticket;
4. necesidad de identidad propia;
5. consulta independiente;
6. uso en reportes;
7. uso en frontend;
8. permisos;
9. auditoría;
10. pruebas;
11. integración en `database/INSTALAR.sql`;
12. verificación de uso e integridad para evitar una tabla abandonada.

Si alguno de estos puntos no está claro, la tabla no se crea.

## Perfiles de experiencia

### Solicitante

Debe trabajar en lenguaje simple y no técnico. La interfaz debe traducir estados internos a mensajes como solicitud recibida, en revisión, necesitamos información, trabajando con proveedor, gestión en curso, visita requerida, solución disponible o solicitud finalizada.

### IT

Necesita prioridad, SLA, parque, área, solicitante, responsable, historial, conversaciones, notas internas, evidencias, esperas, problemas conocidos, conocimiento, proveedor, actividades, resolución y reaperturas.

### Proveedor externo

Solo puede consultar tickets explícitamente compartidos. Puede responder y adjuntar según permisos. Nunca debe acceder a notas internas, administración, otros proveedores, otros tickets o reportes internos.

### Gerencia / Supervisión

Debe recibir lenguaje de negocio: qué ocurre, dónde, desde cuándo, cuánto tarda, qué se repite, carga, cuellos de botella, proveedores y satisfacción.

## Maduración sin cambios de esquema

La mayor parte de la etapa debe resolverse sin cambios de BD:

- copy y categorías visibles del solicitante;
- autocompletado desde usuario/asignación;
- lectura de SLA y porcentaje consumido;
- filtros de cola;
- reconstrucción de tiempos por motivo de espera;
- confirmación y reapertura;
- primera respuesta y actividad de proveedores;
- presentación de posibles soluciones;
- reportes operativos y gerenciales;
- notificaciones nuevas sobre infraestructura existente;
- manual integrado.

## Gate especial de feedback

`ticket_feedback` actualmente almacena `nps_score` 0–10 y los reportes interpretan NPS estadísticamente.

La interfaz objetivo desea una experiencia de cinco estrellas. No se debe mapear 1–5 a 0–10 de forma arbitraria ni modificar `ticket_feedback` sin presentar primero un diseño de compatibilidad histórica y significado de la métrica.

El flujo Sí/No puede reutilizarse desde el modelo existente:

- No → reapertura actual.
- Sí → confirmación/cierre actual.

El almacenamiento de estrellas queda sujeto a aprobación específica en la fase de feedback.

## Actividades / visitas

`WAITING_VISIT` describe una razón de espera, pero no representa una visita con identidad propia.

La fase de actividades debe evaluar primero `ticket_events`. Si se confirma que una intervención necesita programación, técnico, parque, fecha/hora, estado actual, reprogramaciones y consultas independientes, se presentará el diseño de una entidad genérica `ticket_activities`.

Tipos candidatos:

- `REMOTE`
- `ONSITE_VISIT`
- `PROVIDER`
- `FOLLOW_UP`

No crear tablas separadas para visitas, sesiones remotas, visitas de proveedor o seguimientos.

Si `ticket_activities` se aprueba:

- la agenda consultará esa misma tabla;
- los hitos relevantes también quedarán reflejados en `ticket_events`;
- la auditoría seguirá usando `audit_logs`;
- comentarios y adjuntos seguirán siendo la fuente de conversación/evidencia;
- estados `WAITING_VISIT`, `WAITING_PURCHASE` y `WAITING_PROVIDER` seguirán expresando el estado global del ticket, no duplicarán la actividad.

## Proveedores

Mantener `external_ticket_access`, `external_profiles`, comentarios EXTERNAL, adjuntos, eventos y reporte existentes.

La maduración debe derivar de estas fuentes:

- fecha de asignación;
- primera respuesta;
- tiempo hasta primera respuesta;
- duración;
- respuestas;
- archivos;
- trabajo realizado;
- actividad actual;
- devoluciones/reaperturas.

El proveedor nunca resuelve técnicamente el ticket: puede declarar trabajo realizado, pero IT valida y registra la resolución oficial.

## Calidad IT → proveedor

Separar conceptual y estadísticamente:

- usuario → califica IT;
- IT → califica proveedor.

Primero se intentará representar una evaluación de proveedor como evento inmutable asociado a un ciclo de participación. Solo si esa representación resulta insuficiente se presentará una estructura nueva antes de implementarla.

## Conocimiento

No agregar IA externa.

Potenciar `SolutionSuggestionService`, resoluciones reutilizables, problemas conocidos y `knowledge_articles`. El técnico debe ver posibles soluciones con tipo, coincidencia, resumen y enlace directo, y poder crear conocimiento desde una solución existente cuando el modelo actual lo soporte.

## Reportes

Los reportes deben responder preguntas de negocio, no llenar dashboards de gráficas.

Áreas principales:

- IT: volumen, abiertos/cerrados, reabiertos, SLA, primera respuesta, resolución, vencidos, carga, documentación y recurrencia;
- tiempos: cola, trabajo IT y esperas por usuario/proveedor/compra/visita/aprobación/tercero;
- parques: casos, categorías, recurrencias, tiempos, satisfacción y tendencia;
- proveedores: participaciones, primera respuesta, duración, actividad, resolución, reaperturas y valoración IT;
- actividades: visitas, técnico, parque, duración, reprogramaciones, resultado y resolución en primera visita, si la entidad se aprueba.

## Notificaciones

Reutilizar exclusivamente `notification_events` y `notification_deliveries`. No crear otro sistema.

Los nuevos avisos deberán ser útiles y no repetitivos. Visitas, reprogramaciones y respuestas de proveedor son candidatos naturales a notificación.

## Seguridad

No debilitar:

- OTP;
- roles;
- scopes;
- permisos;
- CSRF;
- auditoría;
- sesiones;
- protección de archivos;
- separación INTERNAL / EXTERNAL.

La autorización debe existir en backend. Ocultar un botón no constituye autorización.

## Responsive y tema

Toda función nueva debe validar:

- escritorio 1920;
- laptop 1366;
- iPad/tablet aproximadamente 1024/768;
- móvil <=760.

No se reconstruirá el responsive general. Se reutilizarán componentes y tokens existentes, compatibles con modo claro, oscuro y system.

## Fuera de alcance de V2

No implementar en esta etapa:

- Control IT ↔ Helpdesk;
- API entre apps;
- CMDB;
- inventario externo;
- QR;
- SSO;
- WhatsApp;
- chatbot;
- IA externa;
- geolocalización;
- GPS;
- rutas;
- app móvil nativa;
- SAP;
- Semnox;
- Payout.

No preparar estructuras “por si acaso”.

## Orden de ejecución aprobado

1. baseline/documentación;
2. UX solicitante;
3. operación/SLA;
4. feedback;
5. actividades/visitas;
6. agenda;
7. proveedores;
8. calidad proveedor;
9. conocimiento;
10. reportes;
11. manual;
12. validación integral.

Cada fase se implementa y valida por separado. No mezclar varias fases grandes en un mismo cambio.
