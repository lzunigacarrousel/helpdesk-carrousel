# CHANGELOG

> **Estado canónico · 2026-09-13**
>
> La rama estable y fuente de verdad es `main`. La entrada `v2.4.0-dev` documenta trabajo experimental que quedó preservado en el tag `archive/v2-rebuild-20260913` y **no fue integrado en `main`**. Se conserva aquí como referencia histórica. Las entradas anteriores permanecen como historial del desarrollo integrado antes del checkpoint estable actual.

## Fase 5 · Actividades / visitas · 2026-09-14
- Nueva entidad operativa `ticket_activities` ligada obligatoriamente a tickets, con tipos visita en sitio, soporte remoto, seguimiento, intervención de proveedor y otra atención.
- Nueva tabla `ticket_activity_participants` para participantes; `ticket_attachments.activity_id` permite asociar evidencia reutilizando el almacenamiento existente.
- Permisos `activities.view`, `activities.create`, `activities.manage` y `activities.cancel` integrados a los perfiles operativos y de consulta correspondientes.
- `TicketActivityService` centraliza reglas, scope y transiciones; `TicketActivityController` expone operaciones POST con CSRF para programar, reprogramar, iniciar, finalizar, cancelar y gestionar participantes.
- Reprogramaciones, inicio, finalización y cancelación conservan trazabilidad mediante `ticket_events` y auditoría; no se crean tablas paralelas de historial.
- Crear, reprogramar, iniciar, finalizar o cancelar una actividad no cambia automáticamente el estado ni cierra el ticket.
- Workspace de soporte incorpora Actividades del caso, próximas/activas, historial, formulario progresivo y acciones según estado.
- Solicitante incorpora **Próxima atención** y recibe únicamente tipo/estado/fechas/ubicación y `requester_summary` cuando soporte publica la actividad; no se exponen responsable, proveedor ni detalle técnico interno.
- Manual, README y roadmap documentan el flujo de actividades y dejan Fase 6 — Agenda como siguiente fase, todavía no implementada y dependiente de `ticket_activities`.
## v2.4.0-dev · ARCHIVADO / NO INTEGRADO EN MAIN · Pulido operativo + comunicación simple · 2026-09-09
- El formulario público elimina la captura duplicada `Resumen breve`: el usuario describe el problema una sola vez y el `subject` se genera automáticamente para mantener compatibilidad con tickets, listados y reportes existentes sin borrar columnas.
- Jerarquía visual transversal reforzada con superficies suaves reutilizables, secciones mejor diferenciadas, botones secundarios menos dominantes y reducción de microtexto sin perder controles táctiles ni responsive.
- Dashboard de soporte incorpora un resumen compacto de colaboración externa: casos activos, casos esperando proveedor, proveedores participando y respuestas del día; el Dashboard de gestión muestra el mismo contexto respetando el alcance del perfil.
- El workspace del ticket simplifica tecnicismos visibles y convierte el seguimiento en una conversación profesional con canales claramente identificados: Solicitante, Equipo de soporte, Colaborador y Conversación interna.
- Las notas internas existentes evolucionan visualmente a `Conversación interna`; siguen siendo privadas del equipo de soporte y no se envían ni muestran a solicitantes o proveedores.
- La vista del colaborador externo prioriza problema, conversación, respuesta/evidencia y solución final; se eliminan paneles redundantes de permisos y lenguaje técnico innecesario.
- El Manual incorpora búsqueda instantánea, filtrado de secciones/FAQ, accesos por tarea y contenido simplificado según perfil.
- Tutoriales flotantes se actualizan al formulario de un solo campo, conversación por canales, colaboración externa, manual buscable y lenguaje menos técnico.
- El correo HTML incrusta el logo local mediante CID con PHPMailer y conserva URL pública como respaldo; se simplifica texto, CTA y pie sin mostrar enlaces crudos.
- Se mantienen las reglas de notificación existentes: respuestas/cambios relevantes a partes involucradas, grupo de Sistemas solo cuando requiere atención general y ninguna salida de correo por conversación interna.
- No hay migración de BD en esta fase; se conserva `subject`, `description`, comentarios, adjuntos, notificaciones y trazabilidad existentes.
- Checks estáticos actualizados para bloquear la reaparición del resumen duplicado y validar logo embebido, búsqueda del manual, colaboración externa y separación de conversaciones.

## v2.3.6-dev · Correo confiable + diagnóstico de entregas · 2026-09-09
- El modo `log` deja de contabilizar correos como enviados: las entregas locales quedan registradas como `SKIPPED / Modo prueba`, diferenciándolas de un envío SMTP real.
- `MailService` valida configuración básica, normaliza TLS/SSL, incorpora timeout y devuelve el resultado real de entrega; los errores visibles permanecen genéricos y los detalles quedan en trazabilidad administrativa.
- Nueva configuración opcional `app_url` permite usar una URL estable en botones y logo de los correos, evitando enlaces `localhost`, IP LAN o host temporal cuando el Helpdesk esté publicado.
- Los correos HTML conservan la línea Carrousel, agregan preheader, eliminan URLs visibles en crudo y reducen texto redundante; la versión de texto tampoco expone enlaces largos.
- OTP se integra a `notification_events / notification_deliveries` sin almacenar el código temporal en la trazabilidad; si el correo falla, el OTP recién generado se invalida correctamente.
- La creación de colaboradores externos deja de enviar correo fuera del sistema de notificaciones y pasa a registrar estado de entrega igual que asignación/revocación de casos.
- Se agrega `Correo y notificaciones` en Administración con estado del canal, prueba controlada, enviados del día, fallos, pendientes, modo prueba y últimas entregas.
- Los administradores pueden reintentar correos normales fallidos o pendientes; los OTP no se reenvían y siempre deben solicitarse de nuevo.
- `VERIFICAR_ESTABILIDAD_V2.sql` incorpora controles de enviados sin fecha, fallidos sin motivo y pendientes antiguos, además de contadores separados para enviados, fallidos, pendientes y modo prueba.
- Ayuda contextual, tutorial guiado y Manual incluyen el diagnóstico de correo y explican la diferencia entre SMTP real y registro local.
- No se agrega migración: se reutilizan los estados y columnas existentes de `notification_deliveries`.

## v2.3.5-dev · UX compacta + ayuda progresiva · 2026-09-09
- El formulario público aprovecha mejor pantallas amplias sin reducir objetivos táctiles ni convertir el flujo en una pantalla densa; se amplía el contenedor, se compactan márgenes y se mantiene lectura cómoda en tablet y móvil.
- Encabezado de `Reportar un problema` reducido y alineado para evitar espacio muerto, manteniendo logo, título y contexto en una sola zona visual.
- Paso `¿Qué está pasando?` incorpora ayuda lateral no técnica con qué describir, desde cuándo, a quién afecta y qué se intentó; en tablet/móvil se integra debajo del formulario sin obstaculizar la captura.
- Se agrega explicación desplegable `¿Qué pasa después de enviarlo?` sin añadir campos ni pasos obligatorios.
- El formulario público carga ahora el motor de tutoriales guiados; el recorrido explica datos, ubicación, clasificación, descripción y envío directamente sobre la pantalla.
- Tutoriales enriquecidos con una segunda capa `Consejo`, manteniendo textos principales breves y detalle adicional solo dentro del recorrido.
- Ayuda contextual amplía instrucciones por perfil y aclara que perfil, alcance y atención de soporte son conceptos separados.
- Manual convertido en una guía más interactiva: accesos por tarea frecuente, índice, flujos visuales y preguntas frecuentes mediante `details`, sin depender de JavaScript para consultar la información.
- El manual sigue adaptándose a solicitante, colaborador, técnico, gestión y administración para no exponer instrucciones que no corresponden al perfil.
- Se mantienen controles accesibles de al menos 44–48 px en tablet/móvil y el formulario conserva una sola columna en pantallas pequeñas.
- Checks estáticos ampliados para validar tutorial público, ayuda lateral, manual interactivo y componentes de UX progresiva.

## v2.3.4-dev · Perfiles + alcances + pantalla amplia · 2026-09-09
- Se separa formalmente `perfil`, `alcance` y `operación de soporte`: Gerencia y Supervisor dejan de considerarse técnicos por el simple hecho de poder consultar información.
- Nuevo helper `Auth::isSupportOperator()` distingue a Administrador, Semiadmin y Técnico como únicos perfiles que atienden tickets; Gerencia y Supervisor quedan como perfiles de consulta.
- `Auth::profileLabel()` muestra contexto correcto en la interfaz: Administrador, Semiadministrador, Técnico, Gerencia, Supervisor, Usuario o Colaborador.
- Gerencia entra directamente al Dashboard de gestión y conserva acceso a informes globales, problemas conocidos y conocimiento sin recibir acciones para tomar, responder, poner en espera o resolver tickets.
- Supervisor obtiene Dashboard/Informes con alcance derivado de su asignación organizacional; región, parque o área limitan los tickets visibles tanto en pantalla como en exportación XLSX.
- `ScopeService` reemplaza referencias heredadas a permisos/tablas antiguas y unifica alcance global, organizacional y de equipo de soporte.
- La exportación Excel aplica el mismo alcance del usuario y registra el alcance utilizado dentro del resumen del archivo y auditoría.
- Administración de usuarios incorpora perfiles predefinidos con descripción de capacidad, alcance y si atiende soporte; cada usuario muestra además su alcance efectivo de forma legible.
- Nueva migración incremental `ACTUALIZAR_PERFILES_ALCANCES_V2.sql`: asegura permisos de gestión, limpia permisos operativos de Gerencia/Supervisor y sincroniza membresía del equipo IT para Admin/Semiadmin/Técnico.
- En pantallas anchas se incrementa el ancho útil de Dashboard, Gestión, Cola, Administración, búsqueda y workspace de tickets para reducir espacio muerto sin afectar 1366×768, tablet o móvil.
- Checks estáticos ampliados para validar perfiles, alcances, separación de soporte y aplicación del scope en informes/XLSX.

## v2.3.3-dev · Contexto por perfil + lenguaje visible · 2026-09-09
- Se elimina del dashboard externo el bloque `Acceso restringido y seguro`; el usuario ya no recibe lenguaje técnico de permisos como contenido principal.
- Dashboard de solicitante y colaborador describe capacidades concretas en lenguaje de tarea: revisar casos, responder, adjuntar información y consultar soluciones.
- La vista externa deja de repetir la marca en títulos, botones, ayudas y conversación; se reemplaza por términos funcionales como `Equipo de soporte`, `Responsable`, `Enviar actualización` y `Mis casos`.
- La prioridad interna deja de mostrarse en listados y encabezado del colaborador externo; se conserva únicamente el contexto necesario para ejecutar el trabajo compartido.
- El listado externo deja de mostrar frases negativas como `No tienes acceso a...`; ahora explica de forma positiva qué información concentra ese espacio.
- Shell corporativo reduce redundancia de marca: sidebar `Helpdesk / Corporación Carrousel`, topbar `Helpdesk` y etiquetas de usuario adaptadas a `Administrador/Técnico`, `Usuario` o `Colaborador` según contexto.
- `Externo` deja de mostrarse como etiqueta de rol al colaborador; la interfaz usa `Colaborador`, mientras el valor técnico de autorización permanece sin cambios en backend.
- El acceso público cambia `Acceso de soporte` por `Iniciar sesión` y simplifica el texto de validación por código temporal.
- Se agregan checks estáticos que bloquean la reaparición de textos redundantes o demasiado técnicos en las vistas externas principales.

## v2.3.2-dev · Notificaciones reales + correo + vista externa · 2026-09-09
- La campana deja de ser un contador de carga de trabajo y se convierte en centro persistente de notificaciones con novedades no leídas, historial reciente y acción para marcar como leído.
- `notification_events` y `notification_deliveries` se aprovechan como infraestructura real; se añade canal `IN_APP`, contenido legible, URL de acción y fecha de lectura mediante migración incremental.
- Movimientos importantes del ticket generan notificaciones: creación, toma, asignación/reasignación, devolución a cola, cambio de estado, motivo de espera, respuesta pública, nota interna, resolución y colaboración externa.
- Las respuestas públicas notifican a las partes involucradas; las notas internas permanecen únicamente dentro del equipo de Soporte y no se envían al solicitante.
- Los correos del Helpdesk usan una plantilla Carrousel consistente con logo, franja multicolor, jerarquía clara y botón de acción; se eliminan las URLs visibles en crudo del HTML.
- La entrega por correo queda registrada como PENDING/SENT/FAILED con cantidad de intentos y último error para poder diagnosticar notificaciones que no salieron.
- El dashboard de proveedor/solicitante reutiliza los mismos componentes operativos del resto del sistema: resumen con padding correcto, métricas legibles y casos recientes presentados como fichas accionables.
- Se incorpora `components.css` como capa pequeña de componentes nuevos para notificaciones y experiencia externa, evitando seguir acumulando arreglos dentro de hojas sin relación semántica.
- Verificación de estabilidad añade controles de notificaciones internas y contadores de entregas/fallos; checks estáticos validan rutas, servicio, controlador, JS, CSS y plantilla de correo.

## v2.3.1-dev · Consistencia visual + ayuda integrada · 2026-09-09
- Alineación transversal del contenido interno sobre un mismo eje visual y ancho máximo consistente; Dashboard, Cola, Tickets, ITSM, Gestión y Administración dejan de sentirse como módulos separados.
- Dashboard corrige tarjetas sin padding interno y mantiene jerarquía operativa sin contenido pegado a bordes.
- Informes reorganizados para lectura real: KPIs en 4 columnas, detalle por ticket en bloques 2×2, textos más legibles y secciones renombradas a Qué ocurrió / Cuánto tomó / Cómo avanzó / Qué aprendimos.
- Tutorial flotante contextual por pantalla con pasos guiados, resaltado del elemento activo, navegación Anterior/Siguiente y recordatorio flotante de primera visita.
- Panel de ayuda contextual incorpora acceso directo a Tutorial guiado y Manual completo.
- Nuevo Manual del Helpdesk integrado y adaptado por perfil: solicitudes, soporte, Problem Management, Knowledge Management, gestión y administración según permisos.
- Sidebar incorpora Ayuda de esta pantalla, Tutorial guiado y Manual del Helpdesk como funciones diferenciadas.
- Asset version incrementada para evitar reutilizar CSS/JS anterior después de actualizar la rama.
- Checks estáticos ampliados para validar ruta, controlador, vista de manual y motor de tutorial contextual.

## v2.3.0-dev · ITSM Problemas + Conocimiento · 2026-09-09
- Problem Management operativo sobre las tablas existentes: listado con filtros, creación, edición, estados, propietario, causa raíz, workaround y solución permanente.
- Tickets recurrentes se relacionan mediante `problem_occurrences`; el sistema recalcula automáticamente cantidad de ocurrencias, primera aparición y última aparición.
- Creación de problema conocido desde un ticket o desde varios números de ticket, sin duplicar los casos originales.
- Workspace del problema con tickets relacionados, conocimiento relacionado, solución principal y timeline respaldado por auditoría.
- Knowledge Management operativo sobre `knowledge_articles`: listado, borradores, edición, publicación explícita, visibilidad interna/pública y archivado.
- Creación de artículo desde una resolución de ticket o desde un problema conocido; el contenido se prellena con información técnica y excluye datos personales del solicitante.
- Relación Problema ↔ Artículo mediante `problem_solutions`, incluyendo artículo marcado como solución principal.
- Workspace del ticket incorpora Problema conocido relacionado, workaround disponible y Posibles soluciones.
- Sugerencias sin IA externa combinan categoría, parque, términos del caso, recurrencia, tags de problemas y resoluciones reutilizables anteriores.
- Búsqueda global ampliada y agrupada en Tickets, Problemas conocidos y Conocimiento respetando permisos y visibilidad.
- Sidebar incorpora la sección Conocimiento según permisos; proveedores externos no reciben acceso a los módulos internos.
- Dashboard de soporte añade métricas funcionales de problemas abiertos, problemas en investigación y artículos en borrador según permisos.
- Auditoría registra creación/edición de problemas, relaciones ticket-problema, vínculos problema-artículo, creación/edición/publicación/archivado de conocimiento.
- Nueva migración incremental `ACTUALIZAR_ITSM_PROBLEMAS_CONOCIMIENTO_V2.sql`, sin borrado de datos, para asegurar tablas/permisos ITSM en TEST.
- Verificación de estabilidad ampliada con consistencia de recurrencias, números ITSM, solución principal y publicación de artículos.
- Checks estáticos ampliados para controladores, servicios, vistas, rutas y migración de Problemas + Conocimiento.

## v2.2.0-dev · UX operacional · 2026-09-09
- Dashboard de soporte reorganizado por prioridad operativa: SLA vencidos, próximos a vencer, críticos, trabajo propio, cola y actividad reciente.
- Navegación de soporte simplificada con accesos directos a Centro de soporte, Mis casos y Disponibles; se conserva Mis solicitudes como espacio personal.
- Centro de soporte convertido en cola operacional con filtros rápidos, filtros avanzados plegables, prioridad visual del problema, SLA legible y vista móvil tipo lista/card.
- Workspace del ticket reorganizado con composición 70/30 en escritorio, panel contextual sticky, encabezado sin duplicaciones y resumen del problema como contenido principal.
- Acciones del ticket adaptadas al estado y jerarquizadas; reasignación queda dentro de Más acciones.
- Flujo En espera conserva la lógica existente y muestra motivo/detalle de forma contextual para operación e informes.
- Conversación rediseñada como hilo de soporte: respuesta al usuario y Nota interna claramente diferenciadas; la nota interna indica que solo la ve Soporte.
- Adjuntos muestran nombre, tamaño y acción de descarga; el selector visual ya no promueve archivos CSV.
- Resolución estructurada reorganizada como Qué encontramos / Qué hicimos / Cómo evitarlo, con acción principal Guardar solución y resolver.
- Tickets resueltos destacan una tarjeta de Solución y los casos similares muestran acceso compacto Ver solución.
- Responsive específico para iPad: sidebar compacta en horizontal y drawer en vertical; workspace mantiene dos columnas cuando existe espacio suficiente.
- Dashboard de solicitante y externo simplificado para priorizar solicitudes abiertas, historial y casos recientes.

## v2.1.0-dev · P0/P1 · 2026-09-09
- Acceso, OTP y primer ingreso alineados visualmente con la pantalla de autenticación de Caja Chica Carrousel: fondo corporativo oscuro, contenedor centrado, panel de marca y tarjeta de acceso con las mismas proporciones base.
- Buscador global `/buscar` con acceso por perfil y atajo `Ctrl/Cmd + K`.
- El workspace del ticket prioriza el problema reportado sobre los datos administrativos.
- Nuevo flujo de espera con motivo obligatorio y detalle opcional para explicar pausas operativas.
- Dashboard e Informes incorporan motivos de espera y trazabilidad de cambios de estado.
- Exportación operativa únicamente en Excel `.xlsx`, con hojas Resumen, Tickets y Esperas.
- Se retiró del flujo activo la exportación CSV heredada.
- `schema_migrations` inicia el control de actualizaciones incrementales de BD.
- Evidencias de `storage/ticket_uploads` quedan protegidas fuera de Git.
- CSP reforzada con nonce para JavaScript inline autorizado.
- Ayuda contextual actualizada a búsqueda, resolución, espera e informes XLSX.
- CI de GitHub valida Composer, sintaxis PHP y checks estáticos en `v2-rebuild`.

## v1.0.1 · Fase 1.1 · 2026-09-07
- Alineación visual real con el shell de PayOutParques: sidebar azul, topbar, tarjetas, botones, tablas y footer institucional.
- Logo Carrousel incorporado como recurso local.
- Tema Claro / Oscuro / Sistema persistido en `carrousel-theme`.
- Login y OTP rediseñados; OTP usa `autocomplete="one-time-code"`.
- `luis@carrousel.com.gt` definido como Administrador principal en instalación limpia.
- Nuevo `database/ACTUALIZAR_FASE1_1.sql` para corregir una BD TEST ya instalada sin borrarla.
- Modo OTP `log` continúa disponible en TEST; SMTP permanece configurable solo fuera de Git.

## v1.0.0 · Fase 1
- Base inicial: OTP, usuarios, roles, permisos, scopes, organización y auditoría.
