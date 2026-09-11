<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$fail=[];
$ok=static function(bool $condition,string $message)use(&$fail):void{if(!$condition)$fail[]=$message;};

$service=$root.'/app/Services/RequesterTopicService.php';
$footer=$root.'/app/Views/shared/app_end.php';
$css=$root.'/public/assets/css/layout-density-v25.css';
$ticket=$root.'/app/Controllers/TicketController.php';
$install=$root.'/database/INSTALAR.sql';
foreach([$service,$footer,$css,$ticket,$install] as $file)$ok(is_file($file),'Falta '.str_replace($root.'/','',$file));

if(is_file($service)){
    require_once $service;
    $rows=[
        ['id'=>1,'code'=>'PAYOUT','name'=>'Payout','parent_id'=>null,'parent_name'=>null,'parent_code'=>null,'sort_order'=>80,'parent_sort_order'=>null],
        ['id'=>2,'code'=>'PAYOUT_PARKS','name'=>'Payout Parques','parent_id'=>1,'parent_name'=>'Payout','parent_code'=>'PAYOUT','sort_order'=>10,'parent_sort_order'=>80],
        ['id'=>3,'code'=>'PAYOUT_CLOSURES','name'=>'Cierres','parent_id'=>1,'parent_name'=>'Payout','parent_code'=>'PAYOUT','sort_order'=>30,'parent_sort_order'=>80],
        ['id'=>4,'code'=>'OTHER','name'=>'Otros','parent_id'=>null,'parent_name'=>null,'parent_code'=>null,'sort_order'=>999,'parent_sort_order'=>null],
    ];
    $options=\App\Services\RequesterTopicService::options($rows);
    $codes=array_column($options,'key');
    $ok(count($options)===3,'Selector publico debe ofrecer hojas y categorias sin hijos');
    $ok(!in_array('PAYOUT',$codes,true),'No debe ofrecer padre PAYOUT cuando existen subcategorias');
    $ok(in_array('PAYOUT_PARKS',$codes,true),'Debe ofrecer Payout Parques real');
    foreach($options as $option){if($option['key']==='PAYOUT_PARKS'){$ok($option['label']==='Payout Parques','Etiqueta debe provenir de BD');$ok($option['group']==='Payout','Debe conservar grupo padre');$ok((int)$option['category_id']===2,'Debe conservar category_id real');}}
}
if(is_file($footer))$ok(!str_contains((string)file_get_contents($footer),'<span class="app-footer-chip">Helpdesk Carrousel</span>'),'Footer no debe mostrar chip Helpdesk Carrousel');
if(is_file($css)){$c=(string)file_get_contents($css);$ok(str_contains($c,'.mgmt-grid{align-items:stretch!important}'),'Dashboard gestion debe estirar tarjetas hermanas');$ok(str_contains($c,'.dashboard-work-grid{align-items:stretch!important}'),'Dashboard soporte debe alinear tarjetas');}
if(is_file($ticket)){$t=(string)file_get_contents($ticket);$ok(!str_contains($t,'REQUESTER_CATEGORY_PRESENTATION'),'No debe existir catalogo visual duplicado en TicketController');$ok(str_contains($t,'p.code parent_code'),'TicketController debe cargar jerarquia de BD');}
if(is_file($install)){$i=(string)file_get_contents($install);$ok(str_contains($i,'sort_order INT NOT NULL DEFAULT 100'),'Instalacion limpia debe incluir sort_order');$ok(str_contains($i,"'PAYOUT_PARKS'"),'Instalacion limpia debe incluir catalogo jerarquico');}

if($fail){fwrite(STDERR,"[FAIL] Regresion categorias/UI\n - ".implode("\n - ",$fail)."\n");exit(1);}echo "[OK] Regresion categorias/UI\n";
