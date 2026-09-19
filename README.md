# Helpdesk Carrousel

Service Desk interno de Corporación Carrousel para registrar, atender, documentar y consultar solicitudes de soporte.

## Estado actual

- **Rama canónica:** `main`
- **Versión:** `2.4.0-dev`
- **Estado:** **PREPRODUCCIÓN TÉCNICA GREEN**
- **Base canónica:** `carrousel_helpdesk`
- **Base histórica protegida:** `helpdesk_carrousel`
- **Esquema limpio:** 42 tablas
- **Producción:** todavía no instalada

El gate técnico de preproducción valida esquema canónico, runtime PHP/MariaDB, almacenamiento, rutas, CSS, XLSX y conocimiento versionado. Antes del go-live siguen pendientes la matriz visual manual, la URL final del entorno y SMTP real.

## Capacidades

- Acceso por OTP, sesiones, roles, permisos y auditoría.
- Tickets, clasificación ITSM, prioridad, SLA y estados de atención.
- Conversación pública, conversación interna, adjuntos y trazabilidad.
- Resolución, confirmación del solicitante, reapertura y feedback.
- Actividades y visitas ligadas a tickets.
- Agenda operativa.
- Proveedores externos, participación, informes y valoración interna.
- Problemas conocidos y base de conocimiento versionada.
- Búsqueda global.
- Dashboards e informes con exportación XLSX.
- Notificaciones y correo.
- Manual y ayuda contextual.
- Administración de usuarios, asignaciones y alcances.

## Perfiles

- `ADMIN`
- `SEMIADMIN`
- `TECHNICIAN`
- `MANAGEMENT`
- `SUPERVISOR`
- `REQUESTER`
- `EXTERNAL`

Gerencia y Supervisión son perfiles de consulta y seguimiento. La operación de soporte corresponde a los perfiles autorizados.

## Estructura del repositorio

```text
HelpdeskCarrousel/
├── MAIN.bat
├── INSTALAR_PC_TEST.bat
├── INSTALAR_PRODUCCION.bat
├── VALIDAR_PREPRODUCCION.bat
├── README.md
├── CHANGELOG.md
├── composer.json
├── composer.lock
├── bootstrap.php
├── app/
├── config/
├── database/
├── docs/
├── public/
├── storage/
├── tests/
└── tools/
```

Los gates históricos de fases ya cerradas no forman parte de la raíz operativa. Su historial permanece disponible en Git, `CHANGELOG.md` y `docs/`.

## Uso diario

Ejecutar:

```bat
MAIN.bat
```

El menú canónico permite abrir el Helpdesk, revisar Git, actualizar `main`, validar preproducción, reinstalar PC TEST, respaldar la BD, preparar la configuración privada de producción, hacer commit/push e iniciar la instalación controlada de producción.

## PC TEST

La instalación limpia de pruebas se realiza únicamente con:

```bat
INSTALAR_PC_TEST.bat
```

Este instalador protege `helpdesk_carrousel`, respalda la base de pruebas, reconstruye `carrousel_helpdesk` desde `database/INSTALAR.sql` y exige que el espejo limpio termine en `PASS`.

No es necesario ejecutar SQL manual para reinstalar PC TEST.

## Gate de preproducción

Ejecutar:

```bat
VALIDAR_PREPRODUCCION.bat
```

Resultado esperado:

```text
[OK] PREPRODUCCION TECNICA GREEN
Repo: limpio
Esquema: canonico y sin legado
Datos operativos PC TEST: vacios
MAIN.bat: disponible
Produccion: NO modificada
```

## Preparar configuración de producción

Desde `MAIN.bat`, la opción **Preparar configuración PRODUCCION** toma el `config/local.php` de PC TEST y genera una copia privada en:

```text
dist/production-config/config/local.php
```

La copia conserva DB/SMTP/correo y cambia únicamente `app_url` al destino de producción seleccionado. El script no muestra contraseñas en pantalla.

`dist/` está ignorado por Git, por lo que este archivo **no se sube a GitHub**. Debe transferirse al servidor por un canal privado y colocarse como `config/local.php`.

Si la copia conserva `mail_mode=log` o faltan datos SMTP, el preparador lo advertirá antes del go-live.

## Producción

La instalación de producción se realiza con:

```bat
INSTALAR_PRODUCCION.bat
```

El instalador solo admite una base `carrousel_helpdesk` inexistente o vacía y no ejecuta `DROP DATABASE`.

Antes del go-live deben quedar resueltos:

- matriz visual manual en desktop, iPad/tablet y móvil;
- `app_url` final;
- SMTP real Gmail/Outlook;
- configuración local del servidor.

## Configuración local

Crear `config/local.php` a partir de `config/local.php.example`. El archivo local no se versiona.

Valores principales:

- `db_name`: `carrousel_helpdesk`
- `mail_mode`: `log` en PC TEST / `smtp` en producción
- `app_url`: URL canónica del entorno cuando se envía correo real

## URLs previstas

- PC TEST: `http://localhost/HelpdeskCarrousel/public/`
- Servidor por IP: `http://94.74.71.96/HelpdeskCarrousel/public/`
- Dominio: `https://portal.carrousel-apps.com/HelpdeskCarrousel/public/`
- Portal: `https://portal.carrousel-apps.com/portal/`

La aplicación deriva `APP_BASE_URL` del host actual y conserva la ruta pública `/HelpdeskCarrousel/public/`.

## Base de datos

Para una instalación nueva, la fuente única de verdad es `database/INSTALAR.sql`.

Validadores canónicos:

- `database/VERIFICAR_INSTALACION.sql`
- `database/VERIFICAR_PRODUCCION_LIMPIA.sql`
- `database/VERIFICAR_ESTABILIDAD_V2.sql`

Las migraciones incrementales conservadas en `database/` existen únicamente para compatibilidad con instalaciones previas. Una instalación nueva no se construye encadenando migraciones históricas.

## Historial

El detalle de la evolución funcional se conserva en `CHANGELOG.md`, `docs/superpowers/` y el historial de Git. El README documenta únicamente el estado operativo actual.
