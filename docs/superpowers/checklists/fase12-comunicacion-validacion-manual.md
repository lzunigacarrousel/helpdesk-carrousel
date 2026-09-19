# Fase 12 — Validación manual de correo real

Usar **solo PC TEST**. Esta lista se ejecuta después de que `VALIDAR_FASE12_COMUNICACION.bat` quede GREEN.

## Preparación

- Confirmar `MAIL_MODE=smtp`.
- Confirmar que `/admin/correo` muestre **SMTP activo**.
- Confirmar URL canónica pública/estable.
- Usar cuentas de prueba controladas.
- No usar producción para esta validación.

## Pruebas mínimas

1. **Correo de prueba**
   - `/admin/correo` → Enviar correo de prueba.
   - Estado esperado: SENT.
   - Confirmar logo visible.
   - Confirmar botón con URL válida, no localhost ni IP privada.

2. **OTP**
   - Solicitar código de acceso.
   - Confirmar recepción.
   - Confirmar logo.
   - Confirmar que no aparece botón Reintentar para OTP en `/admin/correo`.

3. **Ticket nuevo**
   - Solicitante recibe “Solicitud recibida”.
   - Equipo de soporte recibe “Nuevo ticket”.
   - Gerencia no recibe correo operativo.

4. **Tomar / asignar / reasignar**
   - Solicitante recibe actualización relevante.
   - Responsable nuevo recibe lo necesario.
   - Evitar correos duplicados al actor.

5. **Respuesta pública**
   - Solicitante recibe nueva actualización.
   - Responsable recibe cuando corresponde.

6. **Nota interna**
   - Debe aparecer dentro del Helpdesk.
   - **No debe existir entrega EMAIL**.

7. **En espera / continuar**
   - Solicitante recibe cambio relevante y motivo de espera.
   - Continuar atención limpia el motivo previo.

8. **Proveedor**
   - Agregar proveedor: proveedor recibe caso compartido.
   - Proveedor responde: soporte recibe actualización.
   - Retirar acceso: proveedor recibe participación finalizada.

9. **Resolución**
   - Solicitante recibe enlace para revisar la solución.
   - Aviso interno no genera correo masivo.

10. **Reapertura**
    - Responsable/equipo recibe que el solicitante necesita más ayuda.

11. **Confirmación/NPS**
    - Se registra in-app para soporte/admin.
    - No genera spam por correo.

## Compatibilidad visual

### Gmail
- Logo visible.
- Franja multicolor visible.
- Botón principal visible y clicable.
- Sin URL cruda.
- Texto legible.

### Outlook
- Logo visible.
- Layout de tabla sin deformación.
- Botón principal usable.
- Franja/colores aceptables.
- Sin URL cruda.

## Evidencia a registrar

- Fecha/hora.
- Evento probado.
- Destinatario.
- Estado en `/admin/correo`.
- Gmail: PASS/FAIL.
- Outlook: PASS/FAIL/no disponible.
- Observaciones.

No desplegar producción únicamente por completar esta lista.
