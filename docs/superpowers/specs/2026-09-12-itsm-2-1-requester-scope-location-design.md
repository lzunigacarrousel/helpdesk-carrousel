# ITSM 2.1 — Alcance del solicitante y ubicación histórica

## Objetivo

Mantener `/crear-ticket` simple, pero garantizar que `tickets.park_id` represente el parque real del caso para Dashboard, informes y KPIs.

## Reglas

- `PARK`: la cuenta representa un parque fijo. Todo ticket nuevo usa ese parque y el solicitante no puede cambiarlo.
- `SUPERVISOR`: puede reportar únicamente sobre los parques comprendidos por su asignación activa (región o parque).
- Resto de cuentas internas (IT, Gerencia, Administración, Procesos, Auditoría, personas/departamentos): pueden seleccionar cualquier parque activo o dejarlo sin especificar.
- `tickets.park_id` es la fuente estadística del parque del caso.
- Cambiar datos administrativos de una cuenta no reescribe automáticamente un ticket que ya tenga parque.
- Una cuenta `PARK` puede completar en lote únicamente tickets históricos vinculados que tengan `park_id IS NULL`.
- Soporte/Admin puede corregir la ubicación de un ticket individual, con motivo obligatorio y trazabilidad `LOCATION_CHANGED`.

## Separación conceptual

- `users.requester_entity_type`: qué representa la cuenta.
- `user_assignments`: estructura organizacional / asignación.
- `tickets.park_id` y `tickets.area_id`: dónde ocurrió el caso.
