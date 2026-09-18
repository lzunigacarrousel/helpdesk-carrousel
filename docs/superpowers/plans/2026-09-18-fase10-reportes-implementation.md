# Fase 10 — Plan de implementación TDD

Fecha: 2026-09-18
Rama: `main`

## Tareas

1. **Hub de reportes**
   - navegación compacta en `/gestion/informes`;
   - enlaces a tickets/SLA, Agenda, Proveedores y Equipo según capacidad;
   - regresión de rutas/permisos.

2. **Resumen de Conocimiento**
   - artículos activos;
   - publicados para soporte;
   - disponibles para solicitantes;
   - borradores/en revisión;
   - sugerencias/aperturas/referencias del período.

3. **Actividades / Agenda**
   - resumen de programadas, en curso, finalizadas, canceladas y atrasadas;
   - respetar scope;
   - no duplicar Agenda.

4. **Tickets / SLA**
   - consolidar copy, indicadores y detalle existentes;
   - asegurar consistencia pantalla/XLSX.

5. **Proveedores**
   - integrar acceso y resumen sin duplicar el informe especializado;
   - conservar calidad IT → proveedor.

6. **Equipo**
   - integrar carga/desempeño como reporte especializado;
   - mantener gestión separada de consulta.

7. **XLSX**
   - validar export general;
   - validar export proveedores;
   - validar export equipo;
   - filtros y alcance equivalentes.

8. **UI / responsive**
   - 1920, 1366, iPad H/V, móvil;
   - claro/oscuro;
   - contrato global de botones.

9. **Manual / tutorial**
   - documentar Centro de informes y lectura por perfil.

10. **Gate y cierre**
    - `VALIDAR_FASE10.bat`;
    - README/CHANGELOG;
    - log;
    - PC TEST;
    - sin producción.
