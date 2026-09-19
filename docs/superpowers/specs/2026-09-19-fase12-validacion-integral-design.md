# Fase 12 — Validación integral y readiness de liberación

Fecha de inicio: 2026-09-19
Rama: `main`

## Objetivo

Validar transversalmente las 11 fases funcionales ya implementadas, corregir únicamente defectos reales y producir una evidencia de readiness antes de cualquier decisión de despliegue.

## Principios

- No agregar módulos nuevos salvo que una prueba revele un defecto funcional real.
- No desplegar producción durante la fase.
- Mantener `2.4.0-dev` mientras la validación esté abierta.
- PC TEST es el entorno de validación funcional/visual.
- La base histórica `helpdesk_carrousel` permanece protegida.
- La base V2 activa es `carrousel_helpdesk`.
- Toda corrección debe incorporar regresión para evitar reincidencia.
- Un gate GREEN automatizado no sustituye la validación visual/manual de perfiles y dispositivos.

## Bloques de validación

1. Preflight y baseline de liberación.
2. Integridad de BD, instalación limpia y migraciones vigentes.
3. Seguridad, perfiles, permisos y scopes.
4. Flujos E2E de tickets, actividades, proveedores, conocimiento y reportes.
5. Correo y notificaciones.
6. Visual/responsive/accesibilidad: claro/oscuro, 1920, 1366, iPad H/V y móvil.
7. Estabilidad operativa, performance básica y checklist de aceptación.
8. Closeout, versión y decisión explícita de despliegue.

## Criterio de salida

Fase 12 solo puede cerrarse cuando:

- los gates automatizados estén GREEN;
- los verificadores SQL estén GREEN;
- los perfiles clave hayan sido validados;
- los flujos E2E críticos estén documentados;
- la matriz visual/dispositivo esté completada;
- no existan defectos bloqueantes abiertos;
- la decisión de producción sea explícita y separada del cierre técnico.

## BD

Task 1 no cambia BD. Cualquier corrección futura de esquema requiere migración incremental, idempotente y validada en PC TEST antes de producción.
