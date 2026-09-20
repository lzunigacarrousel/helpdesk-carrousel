<?php
declare(strict_types=1);

namespace App\Services;

final class RequesterTopicService
{
    private const HELP_BY_CODE=[
        'NETWORK_OUTAGE'=>[
            'help'=>'Indica si el problema afecta a todo el parque o solamente a un equipo y desde cuándo no tienes Internet.',
            'placeholder'=>'Ejemplo: Desde esta mañana no hay Internet en todo el parque.',
        ],
        'NETWORK_UNSTABLE'=>[
            'help'=>'Indica desde cuándo está lento o se desconecta y si afecta a todos los equipos o solamente a algunos.',
            'placeholder'=>'Ejemplo: El Internet se desconecta varias veces desde las 9:00.',
        ],
        'ACCESS_PASSWORD'=>[
            'help'=>'Indica a qué sistema necesitas ingresar y qué mensaje aparece. No escribas tu contraseña.',
            'placeholder'=>'Ejemplo: No puedo ingresar al Portal; aparece que mis credenciales no son correctas.',
        ],
        'ACCESS_PERMISSION'=>[
            'help'=>'Indica a qué sistema o función necesitas acceso y para qué actividad lo necesitas.',
            'placeholder'=>'Ejemplo: Necesito acceso al reporte de cierres para revisar la información del parque.',
        ],
        'POS_BILLING'=>[
            'help'=>'Indica qué caja presenta el problema, qué estabas intentando facturar y qué mensaje aparece.',
            'placeholder'=>'Ejemplo: La caja principal no me permite completar la factura y muestra un mensaje de error.',
        ],
        'POS_PRINTING'=>[
            'help'=>'Indica qué caja o equipo presenta el problema y si la venta finaliza pero el comprobante no se imprime.',
            'placeholder'=>'Ejemplo: La caja principal factura correctamente, pero no imprime el comprobante.',
        ],
        'SEMNOX_KIOSK'=>[
            'help'=>'Indica en qué kiosco ocurre, qué estabas intentando hacer y qué mensaje aparece.',
            'placeholder'=>'Ejemplo: En el kiosco 2 Semnox no permite completar la operación.',
        ],
        'SEMNOX_OPOS'=>[
            'help'=>'Indica el equipo y qué ocurre al imprimir. Si lo sabes, menciona si OPOS o AutoPrint está abierto.',
            'placeholder'=>'Ejemplo: En el kiosco 1 la venta termina, pero AutoPrint no genera el ticket.',
        ],
        'SEMNOX_PROMO'=>[
            'help'=>'Úsala únicamente si la promoción o código se configura o utiliza dentro de Semnox / Parafait. Si el problema es dar salida a un promocional, selecciona Payout.',
            'placeholder'=>'Ejemplo: La promoción no aparece al realizar la venta dentro de Semnox / Parafait.',
        ],
        'PAYOUT_PROMOS'=>[
            'help'=>'Úsala para códigos o promocionales que no aparecen al dar salida o al registrar información en Payout.',
            'placeholder'=>'Ejemplo: El código PRO1153 no aparece en Payout para dar salida.',
        ],
        'PAYOUT_PARKS'=>[
            'help'=>'Indica el parque y qué ocurre: no puedes ingresar, aparece bloqueado, falta información o un reporte no funciona.',
            'placeholder'=>'Ejemplo: No puedo ingresar a Payout Parques; aparece como logueado y no me deja continuar.',
        ],
        'PAYOUT_KIDDIES'=>[
            'help'=>'Indica el parque y qué estabas intentando registrar o consultar en Payout Kiddies.',
            'placeholder'=>'Ejemplo: En Payout Kiddies no puedo completar el registro del parque.',
        ],
        'APP_CASH'=>[
            'help'=>'Indica si necesitas agregar un NIT, registrar una factura, recuperar acceso o corregir un dato.',
            'placeholder'=>'Ejemplo: Necesito agregar un NIT para registrar una factura en Caja Chica.',
        ],
        'APP_DESTROYED_TICKETS'=>[
            'help'=>'Indica la fecha y cantidad. Cuéntanos si falta registrar el dato o si necesitas corregir uno existente.',
            'placeholder'=>'Ejemplo: No pude registrar los tickets destruidos del 09/09/2026.',
        ],
        'REPORT_POWERBI'=>[
            'help'=>'Indica qué dashboard estás consultando, qué filtro aplicaste y qué información esperabas ver.',
            'placeholder'=>'Ejemplo: El dashboard abre, pero no muestra datos al seleccionar el parque.',
        ],
        'MOBILE_IPAD'=>[
            'help'=>'Indica qué pantalla o sistema estás usando en el iPad y si el problema es de visualización, acceso o funcionamiento.',
            'placeholder'=>'Ejemplo: En iPad el dashboard se desordena, aunque en PC y celular se ve correctamente.',
        ],
    ];

    private const HELP_BY_PARENT=[
        'NETWORK'=>'Indica si afecta a todo el parque o solamente a un equipo y desde cuándo ocurre.',
        'ACCESS'=>'Indica a qué sistema necesitas ingresar o qué acceso necesitas. No compartas contraseñas.',
        'POS'=>'Indica qué caja o equipo presenta el problema y qué estabas intentando realizar.',
        'SEMNOX'=>'Indica el módulo, kiosco o caja y qué estabas intentando realizar.',
        'PAYOUT'=>'Indica el parque, módulo, cierre o máquina relacionada y qué resultado esperabas.',
        'HARDWARE'=>'Indica qué equipo presenta el problema y qué comportamiento observas.',
        'PRINTERS'=>'Indica qué impresora es, desde qué equipo imprimes y qué ocurre.',
        'CARROUSEL_APPS'=>'Indica qué aplicación Carrousel estás utilizando y qué sucede.',
        'REPORTS'=>'Indica qué reporte o dato estás consultando y qué esperabas ver.',
        'MAIL'=>'Indica la cuenta o equipo involucrado y si el problema es enviar, recibir o configurar.',
        'SAP'=>'Indica qué estabas realizando en SAP Business One y qué mensaje apareció.',
        'SOFTWARE'=>'Indica qué programa estás usando y qué sucede al intentar trabajar.',
        'MOBILE'=>'Indica el dispositivo, sistema y qué comportamiento observas.',
    ];

    /**
     * Corrige únicamente confusiones inequívocas entre categorías cercanas.
     * No intenta adivinar la categoría general a partir del texto.
     */
    public static function resolveCategoryCode(string $selectedCode,string $subject,string $description):string
    {
        $selectedCode=strtoupper(trim($selectedCode));
        if($selectedCode!=='SEMNOX_PROMO')return $selectedCode;

        $text=self::normalizeText($subject.' '.$description);
        if(self::containsAny($text,[
            'dar salida',
            'darle salida',
            'salida de promocional',
            'salida del promocional',
            'salida de codigo',
            'salida del codigo',
            'payout',
        ]))return 'PAYOUT_PROMOS';

        return $selectedCode;
    }

    private static function normalizeText(string $value):string
    {
        $value=strtr(trim($value),[
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N',
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
        ]);
        return strtolower((string)preg_replace('/\s+/u',' ',$value));
    }

    private static function containsAny(string $text,array $needles):bool
    {
        foreach($needles as $needle)if(str_contains($text,$needle))return true;
        return false;
    }

    public static function options(array $categories):array
    {
        $hasChildren=[];
        foreach($categories as $category){
            $parentId=(int)($category['parent_id']??0);
            if($parentId>0)$hasChildren[$parentId]=true;
        }

        $options=[];
        foreach($categories as $category){
            $id=(int)($category['id']??0);
            $code=trim((string)($category['code']??''));
            $name=trim((string)($category['name']??''));
            if($id<=0||$code===''||$name===''||isset($hasChildren[$id]))continue;

            $parentName=trim((string)($category['parent_name']??''));
            $parentCode=trim((string)($category['parent_code']??''));
            $context=self::HELP_BY_CODE[$code]??null;
            $help=(string)($context['help']??(self::HELP_BY_PARENT[$parentCode]??'Cuéntanos qué necesitas, qué estabas intentando hacer y qué ocurrió.'));
            $placeholder=(string)($context['placeholder']??'Describe qué ocurre, desde cuándo y qué estabas intentando hacer.');

            $options[]=[
                'key'=>$code,
                'label'=>$name,
                'group'=>$parentName!==''?$parentName:'General',
                'category_id'=>$id,
                'category_code'=>$code,
                'help'=>$help,
                'placeholder'=>$placeholder,
                '_parent_order'=>(int)($category['parent_sort_order']??$category['sort_order']??100),
                '_order'=>(int)($category['sort_order']??100),
            ];
        }

        usort($options,static function(array $a,array $b):int{
            return ($a['_parent_order']<=>$b['_parent_order'])
                ?:($a['_order']<=>$b['_order'])
                ?:strnatcasecmp((string)$a['label'],(string)$b['label']);
        });
        foreach($options as &$option){unset($option['_parent_order'],$option['_order']);}
        unset($option);
        return $options;
    }
}
