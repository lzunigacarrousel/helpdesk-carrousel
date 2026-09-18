# Helpdesk Carrousel — Backlog transversal de pulido operativo

Fecha de registro: 2026-09-16
Rama vigente: `main`
Propósito: conservar y ordenar los criterios de simplificación/UX recibidos para aplicarlos progresivamente sin mezclar alcances de fases.

## Regla principal

**La pantalla principal debe ser simple. La ayuda puede ser profunda.**

La potencia queda detrás; cada perfil debe ver únicamente lo necesario para su trabajo.

## Reglas de trabajo vigentes

- Este backlog no autoriza volver a ramas antiguas.
- Para Fase 9 se trabaja únicamente sobre `main`.
- No se despliega producción sin gate previo.
- No se agregan frameworks grandes ni WebSockets mientras no exista una fase específica que lo justifique.
- Los cambios de BD deben ser incrementales, idempotentes y probados primero en PC TEST.
- No destruir datos ni compatibilidad existente por simplificación visual.

## Clasificación

Estados usados:

- `FASE 9`: se aplica directamente en Conocimiento/autoservicio durante Fase 9.
- `TRANSVERSAL`: se aplicará cuando una fase toque esa superficie o en un pulido global posterior.
- `REGLA PERMANENTE`: criterio que debe respetarse desde ahora en cualquier cambio.

| # | Tema | Estado | Aplicación |
|---|---|---|---|
| 1 | Formulario público: un solo campo principal del problema | TRANSVERSAL | Simplificar `subject/description` sin borrar columnas ni perder compatibilidad. |
| 2 | Mejor jerarquía visual del formulario | TRANSVERSAL | Secciones claras, contraste suave y controles táctiles. |
| 3 | Aprovechamiento de pantalla | REGLA PERMANENTE | Evitar espacios muertos; ancho de lectura razonable. |
| 4 | Reducir tecnicismos | FASE 9 + REGLA PERMANENTE | Conocimiento/autoservicio usará copy legible; después extender globalmente. |
| 5 | Eliminar redundancia | FASE 9 + REGLA PERMANENTE | Evitar repetir estado, visibilidad, marca y ayudas en vistas tocadas. |
| 6 | Simplificar botones | FASE 9 + REGLA PERMANENTE | 1 principal, 2–3 secundarias; resto en `Más acciones`. |
| 7 | Reducir tags/badges | FASE 9 + REGLA PERMANENTE | Solo estado/prioridad/SLA/tipo cuando aporten. |
| 8 | Dashboard con colaboración externa | TRANSVERSAL | Integración compacta sin crear exceso de tarjetas. |
| 9 | Gerencia y Supervisión solo consulta | REGLA PERMANENTE | No mostrar acciones operativas si el perfil no las posee. |
| 10 | Experiencia simple de colaborador/proveedor | TRANSVERSAL | `Mis casos`, seguimiento, conversación y evidencia sin módulos internos. |
| 11 | Conversación tipo soporte en tres canales | TRANSVERSAL | Solicitante / interna / proveedor claramente separadas. |
| 12 | Notas internas como conversación entre técnicos | TRANSVERSAL | Reutilizar infraestructura existente; no crear sistema paralelo. |
| 13 | Chat con proveedor | TRANSVERSAL | Zona clara Soporte ↔ Proveedor sin notas internas. |
| 14 | Diseño de conversación | TRANSVERSAL | Hilo profesional con actor, rol, fecha, mensaje y adjuntos. |
| 15 | Logo en correos | TRANSVERSAL | Preferir CID embebido o URL pública absoluta segura. |
| 16 | Diseño/copy de correos | TRANSVERSAL | Tarjeta limpia, CTA principal, sin URLs crudas ni tecnicismos. |
| 17 | Correos por movimiento | TRANSVERSAL | Revisar matriz completa de eventos relevantes. |
| 18 | Evitar spam | REGLA PERMANENTE | Notificar por rol/evento; Gerencia no recibe operación ticket por ticket. |
| 19 | Administración de correo | TRANSVERSAL | Mantener SMTP, estados, intentos y reintentos sin datos sensibles. |
| 20 | Notificaciones internas útiles | TRANSVERSAL | Priorizar/agrupación de eventos importantes. |
| 21 | Manual interactivo | FASE 9 PARCIAL + TRANSVERSAL | En Fase 9 documentar workflow de conocimiento; evolución global posterior. |
| 22 | Tutoriales flotantes | FASE 9 PARCIAL + TRANSVERSAL | Actualizar tutorial de pantallas de Conocimiento; ampliar resto luego. |
| 23 | Ayuda flotante `?` | FASE 9 PARCIAL + TRANSVERSAL | Integrar ayuda relevante donde Fase 9 toque UI. |
| 24 | Menos blanco plano | FASE 9 + REGLA PERMANENTE | Usar superficies suaves de la línea Carrousel sin sobrecargar. |
| 25 | Tipografía legible | FASE 9 + REGLA PERMANENTE | Principal 14–16px aprox.; microtexto solo para metadata secundaria. |
| 26 | Responsive PC/iPad/móvil | FASE 9 + REGLA PERMANENTE | Validar 1920, 1366, iPad H/V y móvil. |
| 27 | BD: preferir compatibilidad antes que borrar columnas | REGLA PERMANENTE | Migraciones aditivas; aplica especialmente a Fase 9. |
| 28 | Auditoría | FASE 9 + REGLA PERMANENTE | Conservar trazabilidad sin exponer auditoría técnica al usuario final. |
| 29 | Performance / sin frameworks grandes | REGLA PERMANENTE | Mantener PHP + JS + CSS actuales. |
| 30 | Versionado | REGLA PERMANENTE | CHANGELOG y flujo Git de la fase vigente; no reutilizar regla antigua de `v2-rebuild`. |
| 31 | Pruebas obligatorias | FASE 9 PARCIAL + REGLA PERMANENTE | Aplicar subconjunto pertinente y mantener matriz completa para pulido global. |
| 32 | Resultado final estructurado | REGLA PERMANENTE | Al cierre de cada fase reportar implementado, simplificado, archivos, BD, pruebas, versión/commit y comandos PC TEST cuando corresponda. |

## Aplicación concreta en Fase 9

Fase 9 deberá aplicar directamente estos criterios en las superficies que modifica:

### Conocimiento

- reducir botonera y duplicidad de estados;
- lenguaje claro: `Borrador`, `En revisión`, `Publicado para soporte`, `Disponible para solicitantes`;
- evitar mostrar nombres técnicos de permisos/estados internos;
- historial y comparación legibles;
- distinguir versión interna vigente de versión pública vigente sin confundir al usuario;
- ayuda profunda fuera del flujo principal.

### Ticket interno

- sugerencias compactas de 3–5 resultados;
- no mostrar scores técnicos de ranking como decoración;
- acción clara `Usar como referencia`;
- no resolver automáticamente;
- trazabilidad visible solo en el nivel útil para soporte.

### Autoservicio

- máximo 3 artículos públicos relevantes;
- bloque discreto `Esto podría ayudarte`;
- lenguaje no técnico;
- siempre permitir continuar con la creación del ticket;
- nunca exponer conocimiento interno.

### Responsive/visual

- validar 1920x1080;
- validar 1366x768;
- validar iPad horizontal;
- validar iPad vertical;
- validar móvil;
- claro/oscuro;
- evitar espacios muertos y tarjetas innecesariamente altas.

### Manual/tutorial

Al implementar Fase 9, el contenido de ayuda relacionado deberá explicar:

1. qué es Conocimiento;
2. cómo crear un borrador;
3. cómo enviarlo a revisión;
4. qué significa publicación interna;
5. qué significa habilitarlo para solicitantes;
6. cómo comparar versiones;
7. cómo restaurar una versión;
8. cómo usar una referencia dentro de un ticket.

## Pendientes transversales principales después de Fase 9

Los bloques de mayor alcance que permanecen explícitamente pendientes son:

- simplificación global de `crear-ticket` y generación automática de `subject`;
- revisión transversal de espacios/jerarquía en dashboard, cola, informes, usuarios, externos, problemas y manual;
- consolidación global de copy;
- dashboard compacto de colaboración externa;
- separación/evolución completa de conversación pública, interna y proveedor;
- corrección robusta de logo CID en correos;
- matriz completa de correos por evento y anti-spam;
- agrupación de notificaciones internas;
- manual interactivo global por perfil;
- tutoriales flotantes ampliados;
- revisión visual/responsive completa de toda la aplicación.

## Actualización de estado real · 2026-09-18

El prompt original de pulido mencionaba la rama `v2-rebuild`, pero esa regla quedó obsoleta. La fuente de verdad vigente es `main`; no volver a `v2-rebuild`.

### Implementado en `main`

- Formulario público con un solo campo visible para describir el problema; `subject` se genera internamente para conservar compatibilidad.
- Jerarquía visual del formulario público, superficies suaves y mejor uso del ancho.
- Dashboard compacto de colaboración externa: casos activos, esperando proveedor, proveedores activos y respuestas recientes.
- Experiencia de proveedor/colaborador separada del portal interno.
- Conversación de ticket con tres canales: respuesta al usuario, colaboración con proveedor y conversación interna.
- Notas internas reutilizadas como conversación interna privada del equipo de soporte.
- Hilo profesional con autor, fecha, canal, mensaje y adjuntos.
- Logo de correo embebido por CID con PHPMailer y URL canónica como respaldo.
- Diseño/copy de correo simplificado y administración de correo con prueba, estados, intentos y reintento.
- Manual interactivo con buscador, accesos por tarea, FAQ y contenido condicionado por perfil.
- Tutorial flotante y ayuda rápida con acceso a tutorial/manual.
- Superficies visuales suaves, mejor tipografía y reglas responsive existentes.
- Dashboard/roles de Gerencia y Supervisión separados de la operación de soporte mediante permisos.
- Conocimiento versionado Fase 9, autoservicio, referencias, métricas y gobierno editorial.
- Contrato visual canónico de botones: principal, secundario y destructivo; integrado a gate/CI.
- Regla de BD aditiva/idempotente y preservación de auditoría/trazabilidad.
- Sin frameworks JS nuevos ni WebSockets.

### Parcial / requiere cierre transversal

- Copy global: gran parte fue simplificada, pero todavía debe revisarse cualquier tecnicismo o duplicación que aparezca durante smoke real.
- Redundancia global: queda revisión visual puntual de badges/tags/metadata en pantallas no recorridas recientemente.
- Correos por movimiento: infraestructura y eventos principales existen; falta smoke completo de la matriz funcional con SMTP real.
- Anti-spam: audiencias por solicitante/asignado/proveedor/soporte están implementadas; falta validar la matriz completa con casos reales de prueba.
- Responsive: CSS existe, pero falta evidencia manual de iPad horizontal/vertical y móvil para el cierre actual.
- Seguridad por perfil: permisos están automatizados, pero falta smoke manual TECHNICIAN / ADMIN-SEMIADMIN / solicitante / colaborador.
- CI remoto: gate local está disponible, pero debe quedar una ejecución remota GREEN o evidencia equivalente aprobada.

### Pendiente real

- Agrupación de notificaciones internas cuando múltiples eventos menores pertenecen al mismo caso. Actualmente las entregas in-app se registran individualmente.
- Pruebas reales de correo/logo en Gmail y Outlook cuando sea posible.
- Completar matriz manual de Fase 9 en iPad/móvil y acceso directo por URL.
- Reejecutar `VALIDAR_FASE9.bat` después de la estandarización global de botones.
- Resolver CI remoto.
- Emitir cierre final estructurado de Fase 9 con evidencia, commit y comandos PC TEST.
- Producción sigue fuera de alcance hasta aprobación explícita.

## Criterio final

El objetivo transversal se mantiene:

- solicitante: `Sé qué hacer`;
- técnico: `Entiendo qué ocurre y con quién debo comunicarme`;
- colaborador: `Solo veo lo necesario para ayudar`;
- Gerencia: `Puedo entender qué está pasando sin operar tickets`;
- Sistemas: trazabilidad, conocimiento, comunicación, reportes y control sin una interfaz innecesariamente compleja.
