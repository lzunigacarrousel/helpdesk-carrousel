# Fase 12 — Plan de implementación / validación

## Task 1 — Preflight y baseline
- inventario canónico de fases, gates, tests y SQL;
- estado Git/versionado;
- gate transversal inicial;
- documentación de continuidad.

## Task 2 — Base de datos
- backup PC TEST;
- instalación limpia canónica;
- `VERIFICAR_INSTALACION.sql`;
- `VERIFICAR_ESTABILIDAD_V2.sql`;
- verificadores Fase 5 y Fase 9;
- validar idempotencia de migraciones incrementales aplicables;
- confirmar protección de `helpdesk_carrousel`.

## Task 3 — Seguridad y perfiles
- ADMIN / SEMIADMIN;
- TECHNICIAN;
- MANAGEMENT;
- SUPERVISOR;
- REQUESTER;
- EXTERNAL;
- scopes de parque/región/área;
- notas internas y datos internos aislados.

## Task 4 — Flujos E2E
- crear ticket;
- tomar/asignar/reasignar;
- conversación pública/interna/proveedor;
- espera/continuar;
- actividades/agenda;
- resolver/confirmar/reabrir;
- problema conocido/conocimiento;
- reportes/XLSX.

## Task 5 — Comunicación
- OTP;
- ticket nuevo/tomado/asignado/reasignado;
- respuesta/cambio de estado/espera/continuar;
- resolución/reapertura;
- proveedor agregado/respondió/revocado;
- nota interna sin correo;
- campana y navegación.

## Task 6 — Visual y responsive
- claro/oscuro;
- 1920x1080;
- 1366x768;
- iPad horizontal;
- iPad vertical;
- móvil;
- pantallas críticas por perfil.

## Task 7 — Estabilidad / aceptación
- errores PHP/JS;
- exportaciones;
- archivos/adjuntos;
- tiempos básicos de carga;
- navegación y enlaces;
- checklist de aceptación.

## Task 8 — Closeout
- README/CHANGELOG;
- gate final;
- resultado de defects;
- versión final coherente;
- decisión separada de despliegue.
