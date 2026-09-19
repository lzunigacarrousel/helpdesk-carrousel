# Fase 12 — Validación integral · progreso

Fecha de inicio: 2026-09-19
Rama: `main`

## Estado inicial

- Fase 11 Manual: gate final PC TEST GREEN.
- Fases 5–11: implementadas; validación transversal pendiente.
- Fase 12: iniciada.
- APP_VERSION: `2.4.0-dev`.
- Producción: sin cambios.

## Decisiones

- No reconstruir funcionalidad existente.
- Unificar la validación final en un gate transversal, evitando ejecutar gates anidados redundantes.
- Separar validación automatizada, SQL, E2E y visual/manual.
- Toda corrección real debe agregar o reforzar una regresión.
- No aprobar producción automáticamente al cerrar Fase 12.

## Task 1 — Preflight y baseline

Estado: en implementación.


## Task 1 — Preflight y baseline · IMPLEMENTADA EN CÓDIGO

- README marca Fase 12 como **EN CURSO — cierre transversal y readiness**.
- Se creó spec, plan y log canónico de Fase 12.
- `tests/phase12_release_baseline_regression.php` valida estado de fases 5–11, versión, artefactos SQL, protección de BD histórica y wiring del gate.
- `VALIDAR_FASE12.bat` se creó como gate transversal inicial sin encadenar gates antiguos completos.
- El gate cubre baseline, instalación, solicitante, tickets/SLA, feedback, Actividades, Agenda, Proveedores/Calidad, Conocimiento, Reportes, Manual, navegación, modo oscuro, botones, checks estáticos, rutas/CSS y XLSX.
- El gate declara explícitamente que su resultado es preliminar y **no autoriza producción**.
- CI incorpora el preflight de Fase 12.
- APP_VERSION permanece en `2.4.0-dev`.
- BD: sin cambios.
- Producción: sin cambios.

Siguiente: Task 2 — integridad de BD, instalación limpia, verificadores e idempotencia de migraciones vigentes en PC TEST.


### Corrección de baseline — regresiones históricas de roadmap

- Primer `VALIDAR_FASE12.bat` llegó hasta el final y solo reportó dos fallos:
  - `phase10_closeout_regression.php` todavía exigía que Fase 11 fuera “SIGUIENTE”.
  - `phase11_closeout_regression.php` todavía exigía que Fase 12 fuera “SIGUIENTE”.
- Ambos fallos son expectativas históricas obsoletas: Fase 11 ya está implementada y Fase 12 está actualmente EN CURSO.
- Se actualizaron únicamente los asserts de roadmap.
- No se modificó funcionalidad, README, configuración, BD ni producción.
- Debe repetirse primero ambos closeouts y después `VALIDAR_FASE12.bat`.


### Segunda corrección de baseline — Fase 8 + diagnóstico de gate

- El segundo gate mostró todas las validaciones visibles GREEN, pero terminó en ERROR porque el inicio del log quedó fuera del texto copiado.
- Se revisó la porción temprana del gate y se detectó otra expectativa histórica en `phase8_provider_rating_closeout_regression.php`: todavía exigía que Fase 9 fuera “Siguiente fase”.
- El assert se actualizó para validar el estado canónico actual: Fase 9 implementada y pendiente únicamente de la validación transversal de Fase 12.
- Se mejoró `VALIDAR_FASE12.bat` para acumular y mostrar un **RESUMEN DE FALLOS** al final; así, aunque la salida larga del terminal se trunque, el bloque final indicará exactamente qué sección falló.
- No se modificó funcionalidad, BD ni producción.


## Task 1 — Preflight y baseline · CERRADA

- PC TEST ejecutó `VALIDAR_FASE12.bat` completamente GREEN.
- Se corrigieron únicamente asserts históricos de roadmap en closeouts de Fase 8, 10 y 11.
- No se detectaron defectos funcionales en el baseline transversal.
- Producción permanece bloqueada.

## Task 2 — Integridad de BD · IMPLEMENTADA EN CÓDIGO / PENDIENTE PC TEST

- Se creó `database/VERIFICAR_FASE12_BD_20260919.sql` como verificador final de Fase 5 + Fase 9 + conocimiento versionado.
- Se creó `VALIDAR_FASE12_BD.bat`.
- El gate:
  1. exige `carrousel_helpdesk`;
  2. protege `helpdesk_carrousel`;
  3. crea backup previo;
  4. ejecuta verificación canónica;
  5. ejecuta estabilidad V2;
  6. valida Fase 5 y Fase 9;
  7. toma snapshot de conteos sensibles;
  8. reejecuta Fase 5 dos veces;
  9. reejecuta Fase 9 dos veces;
  10. compara snapshot antes/después;
  11. compara fingerprint estructural de la base histórica;
  12. vuelve a ejecutar verificadores y exige PASS.
- Los controles de estabilidad previos a los totales deben quedar en 0; cualquier hallazgo detiene el gate.
- Se creó `tests/phase12_database_integrity_regression.php` y se agregó a CI + gate transversal.
- BD de producción: sin cambios.

Pendiente: ejecutar `VALIDAR_FASE12_BD.bat` en PC TEST y registrar evidencia GREEN o defectos reales.


## Task 2 — Integridad de BD · CERRADA

- PC TEST ejecutó `VALIDAR_FASE12_BD.bat` GREEN.
- Backup previo: creado correctamente.
- Instalación canónica: 43 tablas, 7 perfiles canónicos activos y estructura válida.
- Estabilidad V2: todos los controles previos a totales quedaron en 0.
- Fase 5: estructura/permisos/migración OK.
- Fase 9: `PASS` antes y después de idempotencia.
- Migraciones Fase 5 y Fase 9: reejecutadas dos veces sin alterar conteos sensibles.
- Base histórica `helpdesk_carrousel`: fingerprint estructural sin cambios.
- Producción: sin cambios.

## Task 3 — Seguridad, perfiles, permisos y scopes · IMPLEMENTADA EN CÓDIGO / PENDIENTE PC TEST

- Se creó `database/VERIFICAR_FASE12_SEGURIDAD_20260919.sql`.
- Se creó `tests/phase12_security_scope_regression.php`.
- Se creó `VALIDAR_FASE12_SEGURIDAD.bat` en modo solo lectura.
- Matriz esperada:
  - ADMIN: todos los permisos;
  - SEMIADMIN: administración + operación amplia;
  - TECHNICIAN: operación de soporte sin privilegios administrativos;
  - MANAGEMENT: consulta ejecutiva, sin operación de tickets;
  - SUPERVISOR: consulta dentro de alcance, sin operación;
  - REQUESTER: solicitudes propias + respuesta pública + conocimiento;
  - EXTERNAL: solo colaboración en casos compartidos.
- Se validan scopes de Supervisor por asignación y de Técnico por `support_scopes`.
- Se valida que Externo no entre a consultas internas y requiera `external_ticket_access` vigente + caso SPECIAL + EXTERNAL_ALLOWED.
- Se valida que conversación interna y adjuntos INTERNAL nunca sean visibles fuera de soporte.
- Nota interna conserva `email=false`.
- Gate transversal y CI incorporan la regresión estática.
- BD: sin cambios en Task 3.
- Producción: sin cambios.

Pendiente: ejecutar regresión estática y `VALIDAR_FASE12_SEGURIDAD.bat` en PC TEST.


## Task 3 — Seguridad, perfiles, permisos y scopes · CERRADA

- PC TEST ejecutó `VALIDAR_FASE12_SEGURIDAD.bat` GREEN.
- Matriz SQL final: `PASS`.
- ADMIN/SEMIADMIN: capacidades administrativas/operativas válidas.
- TECHNICIAN: operación sin privilegios administrativos.
- MANAGEMENT: consulta ejecutiva sin operación de tickets.
- SUPERVISOR: consulta por scope, sin operación.
- REQUESTER: información propia.
- EXTERNAL: colaboración limitada a casos compartidos.
- Notas/adjuntos internos y Portal permanecen aislados para externos.
- Producción: sin cambios.

## Task 4 — Flujos E2E · IMPLEMENTADA EN CÓDIGO / PENDIENTE PC TEST

- Se creó `tests/phase12_e2e_contract_regression.php`.
- Se creó `tests/phase12_e2e_transactional.php`.
- Se creó `VALIDAR_FASE12_E2E.bat`.
- Contrato cubierto: creación, claim/asignación, conversación PUBLIC/INTERNAL/EXTERNAL, espera por proveedor, continuar, actividad, referencia, resolución, reapertura, cierre/NPS y reportes/XLSX.
- El ensayo transaccional:
  - exige `DB_NAME=carrousel_helpdesk`;
  - usa Técnico, Solicitante, Colaborador, categoría y artículo vigentes;
  - crea un ticket temporal;
  - recorre los tres canales de conversación;
  - habilita colaboración externa;
  - valida PENDING/IN_PROGRESS;
  - crea y completa una actividad;
  - registra referencia de conocimiento;
  - resuelve, reabre, vuelve a resolver, guarda NPS y cierra;
  - ejecuta `ROLLBACK` al finalizar o ante excepción.
- Los AUTO_INCREMENT de MariaDB pueden avanzar aun con rollback; no quedan filas de negocio temporales persistidas.
- El contrato estático se agregó al gate transversal y CI; el ensayo con BD queda solo para PC TEST.
- Producción: sin cambios.

Pendiente: ejecutar sintaxis + contrato estático y `VALIDAR_FASE12_E2E.bat` en PC TEST.


### Ajuste de Task 4 — fixtures E2E autocontenidos

- Primer intento de PC TEST detectó tres fallos del validador, no de la aplicación:
  - assert estático de “continuar atención” dependía de espacios exactos;
  - no existía usuario activo con rol exacto TECHNICIAN;
  - no existía artículo de conocimiento interno vigente.
- El contrato estático ahora valida por fragmentos semánticos y no por formato.
- El ensayo transaccional selecciona un operador de soporte activo priorizando TECHNICIAN, luego SEMIADMIN y ADMIN.
- El ensayo crea artículo + revisión de conocimiento temporales dentro de la misma transacción, los usa como referencia y luego hace ROLLBACK.
- Se corrigió la cantidad de placeholders del INSERT temporal.
- El rollback final comprueba ausencia tanto del ticket como del artículo temporal.
- No se modificó funcionalidad de la app ni datos persistentes.

## Task 4 — Flujos E2E · CERRADA

- PC TEST ejecutó `VALIDAR_FASE12_E2E.bat` GREEN.
- Contrato estático: GREEN.
- Ensayo transaccional real: GREEN.
- Se validaron creación, claim/asignación, PUBLIC/INTERNAL/EXTERNAL, proveedor, PENDING/continuar, actividad, referencia, resolución, reapertura, NPS y cierre.
- Ticket y artículo temporales desaparecieron mediante ROLLBACK.
- Producción: sin cambios.

## Task 5 — Correo y notificaciones · IMPLEMENTADA EN CÓDIGO / PENDIENTE PC TEST

- Se creó `tests/phase12_communication_regression.php`.
- Se creó `tests/phase12_mail_health.php`.
- Se creó `database/VERIFICAR_FASE12_COMUNICACION_20260919.sql`.
- Se creó `VALIDAR_FASE12_COMUNICACION.bat`.
- Se creó checklist manual para SMTP/Gmail/Outlook.
- `MailService` conserva CID `carrousel-logo` y `addEmbeddedImage()` con fallback a URL canónica.
- OTP pasa por NotificationService, conserva hash/rate limit y no admite reintento de una entrega antigua.
- Se validan eventos: nuevo, tomado, reasignado, cola, respuesta pública, nota interna, proveedor respondió, espera/cambio de estado, resolución, reapertura, feedback, proveedor agregado y revocado.
- Nota interna exige `email=false`.
- Administradores reciben eventos operativos dentro del Helpdesk y no se incluye Gerencia en la audiencia administrativa.
- SQL controla entregas huérfanas, correo de nota interna, correo operativo a Gerencia, duplicados, destinatarios, intentos OTP y URLs locales en correos realmente SENT.
- `/admin/correo` amplía etiquetas humanas para eventos actuales.
- El gate automatizado **no envía correos reales**.
- Prueba SMTP real queda como acción explícita en `/admin/correo`.
- BD: sin cambios de esquema.
- Producción: sin cambios.

Pendiente: ejecutar contrato, health check y `VALIDAR_FASE12_COMUNICACION.bat` en PC TEST. Después completar la prueba manual SMTP/Gmail y Outlook si está disponible.

### Ajuste Task 5 — bootstrap de health check y baseline histórica

- Primer intento de PC TEST:
  - contrato de comunicación: GREEN;
  - health check falló porque cargaba `config/config.php` directamente y no definía `APP_ROOT`; la aplicación real entra por `bootstrap.php`;
  - SQL detectó 78 entregas SENT históricas con action_url local.
- Se corrigió `tests/phase12_mail_health.php` para cargar el bootstrap real.
- No se borran ni reescriben las 78 entregas históricas.
- `VALIDAR_FASE12_COMUNICACION.bat` crea una línea base local en `storage/logs/phase12_communication_baseline.txt` (ruta ignorada por Git).
- El SQL informa URLs locales históricas, pero solo bloquea nuevas entregas SENT con localhost/IP privada posteriores al baseline.
- La línea base se implementa sin cambios de esquema ni escritura en BD.
- Se robusteció el BAT con `EnableDelayedExpansion` para Windows CMD.
- Producción: sin cambios.

### Segundo ajuste Task 5 — ejecución CMD + URL canónica

- PC TEST confirmó SMTP, Composer y PHPMailer disponibles.
- El único health check real pendiente es `APP_CANONICAL_CONFIGURED=false`: `config/local.php` tiene `app_url` vacía y la URL resultante es localhost.
- Este bloqueo se mantiene intencional: con `mail_mode=smtp`, la URL canónica es obligatoria antes de enviar correo real.
- El primer BAT corregido todavía falló al consultar MAX(id) mediante `for /f` con mysql y dejó baseline local en 0.
- MariaDB también rechazó `source ...` dentro de `--execute`.
- El BAT ahora consulta MAX(id) redirigiendo a archivo temporal, repara automáticamente baseline 0 y compone un SQL temporal que se ejecuta por stdin.
- La regresión de comunicación cubre estos comportamientos para evitar reincidencia.
- No se borraron las 78 URLs locales históricas y no se modificó BD.
- Producción: sin cambios.
