<?php
declare(strict_types=1);

namespace App\Services;

final class TicketClassificationService
{
    private const REQUEST_TYPES = [
        'INCIDENT' => [
            'label' => 'Incidente',
            'user_label' => 'Tengo un problema',
            'help' => 'Algo dejó de funcionar, funciona mal o está afectando el trabajo.',
        ],
        'SERVICE_REQUEST' => [
            'label' => 'Solicitud de servicio',
            'user_label' => 'Necesito algo',
            'help' => 'Necesitas un acceso, instalación, configuración, usuario, reporte u otro requerimiento.',
        ],
    ];

    private const IMPACTS = [
        'INDIVIDUAL' => 'Solo a una persona',
        'AREA' => 'A un equipo o área',
        'PARK' => 'A un parque completo',
        'MULTI_PARK' => 'A varios parques o a la operación general',
    ];

    private const URGENCIES = [
        'LOW' => 'Puede esperar',
        'MEDIUM' => 'Lo necesito hoy',
        'HIGH' => 'Está frenando parte del trabajo',
        'CRITICAL' => 'La operación está detenida',
    ];

    private const PRIORITIES = [
        'LOW' => 'Baja',
        'MEDIUM' => 'Media',
        'HIGH' => 'Alta',
        'CRITICAL' => 'Crítica',
    ];

    private const PRIORITY_SOURCES = [
        'CALCULATED' => 'Calculada por impacto y urgencia',
        'MANUAL' => 'Ajustada por soporte',
        'LEGACY' => 'Prioridad histórica',
    ];

    /**
     * Categorías que representan un requerimiento aunque su padre no sea REQUEST.
     * Debe mantenerse alineado con el catálogo canónico y la inferencia histórica ITSM 2.1.
     */
    private const SERVICE_REQUEST_CATEGORY_CODES = [
        'ACCESS_CREATE','ACCESS_PERMISSION','HARDWARE_INSTALL','POS_CONFIG','SEMNOX_PROMO','SEMNOX_CASHIER',
        'SAP_PERMISSIONS','SOFTWARE_INSTALL','SOFTWARE_UPDATE','SOFTWARE_CONFIG','SOFTWARE_LICENSE',
        'REPORT_QUERY','MAIL_ACCOUNT','MAIL_SIGNATURE','MOBILE_CONFIG',
    ];

    private const MATRIX = [
        'LOW' => [
            'INDIVIDUAL' => 'LOW',
            'AREA' => 'LOW',
            'PARK' => 'MEDIUM',
            'MULTI_PARK' => 'MEDIUM',
        ],
        'MEDIUM' => [
            'INDIVIDUAL' => 'LOW',
            'AREA' => 'MEDIUM',
            'PARK' => 'MEDIUM',
            'MULTI_PARK' => 'HIGH',
        ],
        'HIGH' => [
            'INDIVIDUAL' => 'MEDIUM',
            'AREA' => 'MEDIUM',
            'PARK' => 'HIGH',
            'MULTI_PARK' => 'CRITICAL',
        ],
        'CRITICAL' => [
            'INDIVIDUAL' => 'MEDIUM',
            'AREA' => 'HIGH',
            'PARK' => 'CRITICAL',
            'MULTI_PARK' => 'CRITICAL',
        ],
    ];

    public static function requestTypeOptions(): array { return self::REQUEST_TYPES; }
    public static function requestTypeLabels(): array
    {
        $labels=[];foreach(self::REQUEST_TYPES as $code=>$meta)$labels[$code]=$meta['label'];return $labels;
    }
    public static function impactOptions(): array { return self::IMPACTS; }
    public static function urgencyOptions(): array { return self::URGENCIES; }
    public static function priorityOptions(): array { return self::PRIORITIES; }

    public static function requestTypeLabel(?string $code,bool $forRequester=false):string
    {
        $code=strtoupper(trim((string)$code));if(!isset(self::REQUEST_TYPES[$code]))return 'Sin clasificar';
        return $forRequester?self::REQUEST_TYPES[$code]['user_label']:self::REQUEST_TYPES[$code]['label'];
    }
    public static function impactLabel(?string $code):string{$code=strtoupper(trim((string)$code));return self::IMPACTS[$code]??'Sin clasificar';}
    public static function urgencyLabel(?string $code):string{$code=strtoupper(trim((string)$code));return self::URGENCIES[$code]??'Sin clasificar';}
    public static function priorityLabel(?string $code):string{$code=strtoupper(trim((string)$code));return self::PRIORITIES[$code]??'Sin prioridad';}
    public static function prioritySourceLabel(?string $code):string{$code=strtoupper(trim((string)$code));return self::PRIORITY_SOURCES[$code]??'Sin origen';}
    public static function validRequestType(string $code):bool{return isset(self::REQUEST_TYPES[strtoupper(trim($code))]);}
    public static function validImpact(string $code):bool{return isset(self::IMPACTS[strtoupper(trim($code))]);}
    public static function validUrgency(string $code):bool{return isset(self::URGENCIES[strtoupper(trim($code))]);}
    public static function validPriority(string $code):bool{return isset(self::PRIORITIES[strtoupper(trim($code))]);}

    public static function calculatePriority(string $impact,string $urgency):string
    {
        $impact=strtoupper(trim($impact));$urgency=strtoupper(trim($urgency));
        if(!self::validImpact($impact)||!self::validUrgency($urgency))throw new \InvalidArgumentException('Impacto o urgencia no válidos.');
        return self::MATRIX[$urgency][$impact];
    }

    /**
     * Clasificación inicial para el alta simple. El solicitante no debe conocer ITSM.
     * Soporte puede revisar y corregir posteriormente mediante /tickets/classification.
     */
    public static function inferInitialClassification(array $category,string $subject,string $description):array
    {
        $categoryCode=strtoupper(trim((string)($category['code']??'')));
        $parentCode=strtoupper(trim((string)($category['parent_code']??'')));
        $requestType=self::inferRequestType($categoryCode,$parentCode);
        $impact=self::inferImpact($subject.' '.$description);
        $urgency=self::inferUrgency($subject.' '.$description);
        return[
            'request_type'=>$requestType,
            'impact'=>$impact,
            'urgency'=>$urgency,
            'priority'=>self::calculatePriority($impact,$urgency),
        ];
    }

    public static function inferRequestType(?string $categoryCode,?string $parentCode):string
    {
        $categoryCode=strtoupper(trim((string)$categoryCode));$parentCode=strtoupper(trim((string)$parentCode));
        if($parentCode==='REQUEST'||$categoryCode==='REQUEST'||in_array($categoryCode,self::SERVICE_REQUEST_CATEGORY_CODES,true))return 'SERVICE_REQUEST';
        return 'INCIDENT';
    }

    /** Conservador: solo eleva impacto cuando el propio texto expresa alcance colectivo. */
    public static function inferImpact(string $text):string
    {
        $text=self::normalizeText($text);
        if(self::containsAny($text,['varios parques','todos los parques','toda la operacion','operacion general','a nivel general','nivel nacional']))return 'MULTI_PARK';
        if(self::containsAny($text,['todo el parque','parque completo','toda la sede','todas las cajas','todos los equipos del parque']))return 'PARK';
        if(self::containsAny($text,['toda el area','toda mi area','mi area completa','todo el equipo','varios usuarios','varias personas','varios equipos']))return 'AREA';
        return 'INDIVIDUAL';
    }

    /** Conservador: MEDIUM es el valor normal; solo escala cuando el texto lo declara claramente. */
    public static function inferUrgency(string $text):string
    {
        $text=self::normalizeText($text);
        if(self::containsAny($text,['operacion detenida','no podemos operar','no se puede operar','parque detenido','sin poder operar','ventas detenidas']))return 'CRITICAL';
        if(self::containsAny($text,['no puedo trabajar','no podemos trabajar','no puedo facturar','no podemos facturar','esta bloqueado','estamos bloqueados','urgente','afecta ventas']))return 'HIGH';
        if(self::containsAny($text,['puede esperar','cuando sea posible','no urge','sin prisa']))return 'LOW';
        return 'MEDIUM';
    }

    private static function normalizeText(string $value):string
    {
        $value=strtr(trim($value),['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N','á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
        return strtolower($value);
    }
    private static function containsAny(string $text,array $needles):bool
    {
        foreach($needles as $needle)if(str_contains($text,$needle))return true;return false;
    }
}
