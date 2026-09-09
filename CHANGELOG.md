# CHANGELOG

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
