<?php
declare(strict_types=1);

require dirname(__DIR__).'/app/Core/SearchText.php';

use App\Core\SearchText;

$ok=true;
function summaryCheck(bool $condition,string $message):void{
    global $ok;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    $ok=$ok&&$condition;
}

summaryCheck(!SearchText::addsDistinctDetail('Problema con impresora','Problema con impresora'),'Oculta descripción idéntica');
summaryCheck(!SearchText::addsDistinctDetail(
    'Solicito apoyo para ingresar en el sistema Ticket Station la información correspondiente a los días 01/09/2026 y 02/09/2026',
    'Solicito apoyo para ingresar en el sistema Ticket Station la información correspondiente a los días 01/09/2026 y 02/09/2026, debido a que durante la revisión se detectó una diferencia'
),'Oculta descripción cuando el asunto es el inicio de la descripción');
summaryCheck(!SearchText::addsDistinctDetail(
    'Al intentar ingresar a Fracttal me aparece que mi usuario está bloqueado y no me deja acceder',
    'Al intentar ingresar a Fracttal me aparece que mi usuario está bloqueado y no me deja acceder. Necesito ayuda para desbloquearlo.'
),'Oculta ampliación que repite íntegramente el asunto');
summaryCheck(SearchText::addsDistinctDetail(
    'Impresora no disponible',
    'El equipo muestra el código de error 5200 después de cada reinicio.'
),'Conserva descripción cuando aporta información distinta');
summaryCheck(SearchText::addsDistinctDetail('','Detalle útil'),'Conserva detalle si no hay asunto');

$root=dirname(__DIR__);
$views=[
    'app/Views/management/dashboard.php',
    'app/Views/dashboard/index.php',
    'app/Views/search/index.php',
    'app/Views/tickets/queue.php',
];
foreach($views as $path){
    $content=(string)file_get_contents($root.'/'.$path);
    summaryCheck(str_contains($content,'SearchText::addsDistinctDetail'),$path.' usa regla común anti-duplicado');
}

exit($ok?0:1);
