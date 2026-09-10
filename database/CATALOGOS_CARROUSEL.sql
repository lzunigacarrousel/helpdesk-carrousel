-- Helpdesk Carrousel V2
-- Catalogos organizacionales base para una instalacion limpia.
-- No importa usuarios ni tickets historicos.

USE helpdesk_carrousel;
SET NAMES utf8mb4;

-- Regiones canonicas.
INSERT INTO regions(code,name,is_active) VALUES
('CC_REGION_6','Sur Occidente',1),
('CC_REGION_7','Nor Oriente',1),
('CC_REGION_8','Central',1),
('CC_REGION_9','Central Alianzas',1)
ON DUPLICATE KEY UPDATE name=VALUES(name),is_active=1;

-- Parques canonicos. Se conservan los codigos CC_PARK_* para compatibilidad
-- con asignaciones y referencias historicas, pero esta carga no importa usuarios.
INSERT INTO parks(region_id,code,name,cost_center,is_active)
SELECT r.id,'CC_PARK_504','Alturas Huehuetenango','070',1 FROM regions r WHERE r.code='CC_REGION_6'
UNION ALL SELECT r.id,'CC_PARK_451','Coatepeque','052',1 FROM regions r WHERE r.code='CC_REGION_6'
UNION ALL SELECT r.id,'CC_PARK_390','Huehuetenango','032',1 FROM regions r WHERE r.code='CC_REGION_6'
UNION ALL SELECT r.id,'CC_PARK_430','Interplaza Xela','049',1 FROM regions r WHERE r.code='CC_REGION_6'
UNION ALL SELECT r.id,'CC_PARK_331','Mazatenango','018',1 FROM regions r WHERE r.code='CC_REGION_6'
UNION ALL SELECT r.id,'CC_PARK_345','Retalhuleu','020',1 FROM regions r WHERE r.code='CC_REGION_6'
UNION ALL SELECT r.id,'CC_PARK_465','Totonicapan','056',1 FROM regions r WHERE r.code='CC_REGION_6'
UNION ALL SELECT r.id,'CC_PARK_508','Celajes Quiche','071',1 FROM regions r WHERE r.code='CC_REGION_6'

UNION ALL SELECT r.id,'CC_PARK_463','Carcha','055',1 FROM regions r WHERE r.code='CC_REGION_7'
UNION ALL SELECT r.id,'CC_PARK_378','Pradera Chiquimula','028',1 FROM regions r WHERE r.code='CC_REGION_7'
UNION ALL SELECT r.id,'CC_PARK_323','Coban','017',1 FROM regions r WHERE r.code='CC_REGION_7'
UNION ALL SELECT r.id,'CC_PARK_409','Jalapa','042',1 FROM regions r WHERE r.code='CC_REGION_7'
UNION ALL SELECT r.id,'CC_PARK_402','Jutiapa','041',1 FROM regions r WHERE r.code='CC_REGION_7'
UNION ALL SELECT r.id,'CC_PARK_512','Metroplaza Mundo Maya Peten','072',1 FROM regions r WHERE r.code='CC_REGION_7'
UNION ALL SELECT r.id,'CC_PARK_371','Puerto Barrios','026',1 FROM regions r WHERE r.code='CC_REGION_7'
UNION ALL SELECT r.id,'CC_PARK_444','Zacapa','051',1 FROM regions r WHERE r.code='CC_REGION_7'

UNION ALL SELECT r.id,'CC_PARK_480','Cayala','059',1 FROM regions r WHERE r.code='CC_REGION_8'
UNION ALL SELECT r.id,'CC_PARK_316','El Frutal','016',1 FROM regions r WHERE r.code='CC_REGION_8'
UNION ALL SELECT r.id,'CC_PARK_273','Florida','007',1 FROM regions r WHERE r.code='CC_REGION_8'
UNION ALL SELECT r.id,'CC_PARK_304','Metro Centro','013',1 FROM regions r WHERE r.code='CC_REGION_8'
UNION ALL SELECT r.id,'CC_PARK_266','Santa Clara','006',1 FROM regions r WHERE r.code='CC_REGION_8'
UNION ALL SELECT r.id,'CC_PARK_437','Vistares','050',1 FROM regions r WHERE r.code='CC_REGION_8'

UNION ALL SELECT r.id,'CC_PARK_469','Andaria','057',1 FROM regions r WHERE r.code='CC_REGION_9'
UNION ALL SELECT r.id,'CC_PARK_281','Pradera Chimaltenango','009',1 FROM regions r WHERE r.code='CC_REGION_9'
UNION ALL SELECT r.id,'CC_PARK_455','Interplaza Escuintla','053',1 FROM regions r WHERE r.code='CC_REGION_9'
UNION ALL SELECT r.id,'CC_PARK_364','Pradera Escuintla','025',1 FROM regions r WHERE r.code='CC_REGION_9'
UNION ALL SELECT r.id,'CC_PARK_459','Santa Lucia','054',1 FROM regions r WHERE r.code='CC_REGION_9'
UNION ALL SELECT r.id,'CC_PARK_476','Telares','058',1 FROM regions r WHERE r.code='CC_REGION_9'
UNION ALL SELECT r.id,'CC_PARK_516','Plaza Villa Lobos','073',1 FROM regions r WHERE r.code='CC_REGION_9'
ON DUPLICATE KEY UPDATE
    region_id=VALUES(region_id),
    name=VALUES(name),
    cost_center=VALUES(cost_center),
    is_active=1;

SELECT 'OK - CATALOGOS CARROUSEL' AS resultado,
       (SELECT COUNT(*) FROM regions WHERE is_active=1) AS regiones_activas,
       (SELECT COUNT(*) FROM parks WHERE is_active=1) AS parques_activos,
       (SELECT COUNT(*) FROM parks WHERE is_active=1 AND region_id IS NULL) AS parques_sin_region;
