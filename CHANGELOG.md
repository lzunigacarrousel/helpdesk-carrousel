# CHANGELOG

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
