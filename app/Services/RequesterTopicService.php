<?php
declare(strict_types=1);

namespace App\Services;

final class RequesterTopicService
{
    private const REQUESTER_TOPIC_PRESENTATION=[
        'CASH_NIT'=>[
            'label'=>'Caja Chica / NIT',
            'category_code'=>'SOFTWARE',
            'help'=>'Indica si necesitas agregar un NIT, recuperar el acceso o si aparece un error al ingresar una factura. Si es un NIT, escribe el número y nombre.',
            'placeholder'=>'Ejemplo: Necesito agregar el NIT 1234567 a nombre de Proveedor Ejemplo.',
        ],
        'PAYOUT'=>[
            'label'=>'Payout / Kiddies / Promocionales',
            'category_code'=>'SOFTWARE',
            'help'=>'Indica si no puedes ingresar, está lento, aparece bloqueado o logueado, falta un código o promocional, o algún reporte no funciona.',
            'placeholder'=>'Ejemplo: No puedo ingresar a Payout de parques. Aparece como logueado y no me deja continuar.',
        ],
        'DESTROYED_TICKETS'=>[
            'label'=>'Tickets Destruidos',
            'category_code'=>'SOFTWARE',
            'help'=>'Indica la fecha y cantidad. Cuéntanos si falta registrar el dato o si necesitas corregir uno que ya fue ingresado.',
            'placeholder'=>'Ejemplo: No pude registrar los tickets del 09/09/2026. La cantidad correcta es 5,420.',
        ],
        'POS_PRINTING'=>[
            'label'=>'Facturación / POS / Impresora',
            'category_code'=>'POS',
            'help'=>'Indica qué caja o equipo presenta el problema y qué ocurre: no factura, no imprime, imprime incompleto o aparece algún mensaje.',
            'placeholder'=>'Ejemplo: La caja principal factura, pero la impresora no imprime el comprobante.',
        ],
        'ACCESS'=>[
            'label'=>'Acceso / Contraseña',
            'category_code'=>'ACCESS',
            'help'=>'Indica a qué sistema necesitas ingresar y qué mensaje aparece. No escribas tu contraseña.',
            'placeholder'=>'Ejemplo: No puedo ingresar a Caja Chica; aparece credenciales incorrectas.',
        ],
        'HARDWARE'=>[
            'label'=>'Computadora / Equipo',
            'category_code'=>'HARDWARE',
            'help'=>'Indica qué equipo tiene el problema y qué sucede: no enciende, se reinicia, está lento, no reconoce un dispositivo u otra falla.',
            'placeholder'=>'Ejemplo: La computadora principal no enciende después de un apagón.',
        ],
        'NETWORK'=>[
            'label'=>'Internet / Conexión',
            'category_code'=>'NETWORK',
            'help'=>'Indica si el problema afecta a todo el parque o solamente a un equipo y desde cuándo ocurre.',
            'placeholder'=>'Ejemplo: Desde esta mañana no hay internet en el parque; por ahora estamos usando el teléfono corporativo.',
        ],
        'REPORTS'=>[
            'label'=>'Reportes / Dashboards / Formularios',
            'category_code'=>'REPORTS',
            'help'=>'Indica qué reporte, tablero o formulario estás utilizando, qué estabas intentando consultar y qué esperabas ver.',
            'placeholder'=>'Ejemplo: El reporte general de Payout abre, pero no muestra información al aplicar el filtro.',
        ],
        'SEMNOX'=>[
            'label'=>'Semnox / Parafait',
            'category_code'=>'SEMNOX',
            'help'=>'Indica dónde ocurre el problema, en qué equipo o módulo y qué estabas intentando realizar.',
            'placeholder'=>'Ejemplo: En el kiosco 1, Semnox no permite completar la operación después de seleccionar la opción.',
        ],
        'OTHER'=>[
            'label'=>'Otro',
            'category_code'=>'OTHER',
            'help'=>'Cuéntanos qué necesitas, qué estabas intentando hacer y qué ocurrió.',
            'placeholder'=>'Ejemplo: Necesito apoyo con una situación que no aparece en las opciones anteriores.',
        ],
    ];

    public static function options(array $categories):array
    {
        $idsByCode=[];
        foreach($categories as $category){
            $code=(string)($category['code']??'');
            $id=(int)($category['id']??0);
            if($code!==''&&$id>0)$idsByCode[$code]=$id;
        }

        $options=[];
        foreach(self::REQUESTER_TOPIC_PRESENTATION as $key=>$topic){
            $categoryCode=(string)$topic['category_code'];
            if(!isset($idsByCode[$categoryCode]))continue;
            $options[]=[
                'key'=>$key,
                'label'=>$topic['label'],
                'category_id'=>$idsByCode[$categoryCode],
                'category_code'=>$categoryCode,
                'help'=>$topic['help'],
                'placeholder'=>$topic['placeholder'],
            ];
        }
        return $options;
    }
}
