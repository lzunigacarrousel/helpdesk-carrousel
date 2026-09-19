-- Helpdesk Carrousel - Fase 12
-- Correo y notificaciones. SOLO LECTURA.
USE carrousel_helpdesk;

SELECT 'EVENTOS_NOTIFICACION' control,COUNT(*) valor
FROM notification_events;

SELECT 'ENTREGAS_EMAIL' control,COUNT(*) valor
FROM notification_deliveries
WHERE channel='EMAIL';

SELECT 'ENTREGAS_IN_APP' control,COUNT(*) valor
FROM notification_deliveries
WHERE channel='IN_APP';

SELECT 'DELIVERIES_HUERFANAS' control,COUNT(*) valor
FROM notification_deliveries d
LEFT JOIN notification_events e ON e.id=d.event_id
WHERE e.id IS NULL;

SELECT 'EMAIL_NOTA_INTERNA' control,COUNT(*) valor
FROM notification_deliveries d
JOIN notification_events e ON e.id=d.event_id
WHERE d.channel='EMAIL'
  AND e.event_key='INTERNAL_NOTE_ADDED';

SELECT 'EMAIL_OPERATIVO_GERENCIA' control,COUNT(*) valor
FROM notification_deliveries d
JOIN notification_events e ON e.id=d.event_id
JOIN users u ON u.id=d.recipient_user_id
JOIN roles r ON r.id=u.role_id
WHERE d.channel='EMAIL'
  AND r.code='MANAGEMENT'
  AND e.event_key NOT IN('OTP_REQUESTED','MAIL_TEST');

SELECT 'DUPLICADOS_ENTREGA' control,COUNT(*) valor
FROM (
    SELECT event_id,channel,COALESCE(recipient_user_id,0) recipient_user_id,
           LOWER(COALESCE(recipient_email,'')) recipient_email,COUNT(*) cantidad
    FROM notification_deliveries
    GROUP BY event_id,channel,COALESCE(recipient_user_id,0),LOWER(COALESCE(recipient_email,''))
    HAVING COUNT(*)>1
) x;

SELECT 'EMAIL_SIN_DESTINATARIO' control,COUNT(*) valor
FROM notification_deliveries
WHERE channel='EMAIL'
  AND (recipient_email IS NULL OR TRIM(recipient_email)='' OR recipient_email NOT LIKE '%@%');

SELECT 'IN_APP_SIN_USUARIO' control,COUNT(*) valor
FROM notification_deliveries
WHERE channel='IN_APP'
  AND (recipient_user_id IS NULL OR recipient_user_id<=0);

SELECT 'INTENTOS_NEGATIVOS' control,COUNT(*) valor
FROM notification_deliveries
WHERE attempts<0;

SELECT 'OTP_CON_REINTENTO_MANUAL' control,COUNT(*) valor
FROM notification_deliveries d
JOIN notification_events e ON e.id=d.event_id
WHERE d.channel='EMAIL'
  AND e.event_key='OTP_REQUESTED'
  AND d.attempts>1;

SELECT 'BASELINE_DELIVERY_ID' control,COALESCE(@phase12_baseline_delivery_id,0) valor;

SELECT 'ACCIONES_LOCALHOST_EMAIL_HISTORICAS' control,COUNT(*) valor
FROM notification_deliveries
WHERE channel='EMAIL'
  AND status='SENT'
  AND id<=COALESCE(@phase12_baseline_delivery_id,0)
  AND action_url IS NOT NULL
  AND (
    LOWER(action_url) LIKE 'http://localhost%'
    OR LOWER(action_url) LIKE 'https://localhost%'
    OR action_url LIKE '%192.168.%'
  );

SELECT 'ACCIONES_LOCALHOST_EMAIL_NUEVAS' control,COUNT(*) valor
FROM notification_deliveries
WHERE channel='EMAIL'
  AND status='SENT'
  AND id>COALESCE(@phase12_baseline_delivery_id,0)
  AND action_url IS NOT NULL
  AND (
    LOWER(action_url) LIKE 'http://localhost%'
    OR LOWER(action_url) LIKE 'https://localhost%'
    OR action_url LIKE '%192.168.%'
  );

SELECT CASE
  WHEN (SELECT COUNT(*) FROM notification_deliveries d
        LEFT JOIN notification_events e ON e.id=d.event_id
        WHERE e.id IS NULL)=0
   AND (SELECT COUNT(*) FROM notification_deliveries d
        JOIN notification_events e ON e.id=d.event_id
        WHERE d.channel='EMAIL' AND e.event_key='INTERNAL_NOTE_ADDED')=0
   AND (SELECT COUNT(*) FROM notification_deliveries d
        JOIN notification_events e ON e.id=d.event_id
        JOIN users u ON u.id=d.recipient_user_id
        JOIN roles r ON r.id=u.role_id
        WHERE d.channel='EMAIL' AND r.code='MANAGEMENT'
          AND e.event_key NOT IN('OTP_REQUESTED','MAIL_TEST'))=0
   AND (SELECT COUNT(*) FROM (
        SELECT event_id,channel,COALESCE(recipient_user_id,0),LOWER(COALESCE(recipient_email,'')),COUNT(*) cantidad
        FROM notification_deliveries
        GROUP BY event_id,channel,COALESCE(recipient_user_id,0),LOWER(COALESCE(recipient_email,''))
        HAVING COUNT(*)>1
      ) dup)=0
   AND (SELECT COUNT(*) FROM notification_deliveries
        WHERE channel='EMAIL'
          AND (recipient_email IS NULL OR TRIM(recipient_email)='' OR recipient_email NOT LIKE '%@%'))=0
   AND (SELECT COUNT(*) FROM notification_deliveries
        WHERE channel='IN_APP' AND (recipient_user_id IS NULL OR recipient_user_id<=0))=0
   AND (SELECT COUNT(*) FROM notification_deliveries WHERE attempts<0)=0
   AND (SELECT COUNT(*) FROM notification_deliveries d
        JOIN notification_events e ON e.id=d.event_id
        WHERE d.channel='EMAIL' AND e.event_key='OTP_REQUESTED' AND d.attempts>1)=0
   AND (SELECT COUNT(*) FROM notification_deliveries
        WHERE channel='EMAIL'
          AND status='SENT'
          AND id>COALESCE(@phase12_baseline_delivery_id,0)
          AND action_url IS NOT NULL
          AND (LOWER(action_url) LIKE 'http://localhost%'
               OR LOWER(action_url) LIKE 'https://localhost%'
               OR action_url LIKE '%192.168.%'))=0
  THEN 'PASS'
  ELSE 'FAIL'
END AS fase12_communication_gate;
