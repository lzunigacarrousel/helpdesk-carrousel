<?php
declare(strict_types=1);namespace App\Core;final class View{public static function render(string $view,array $data=[]):void{extract($data,EXTR_SKIP);$file=APP_ROOT.'/app/Views/'.$view.'.php';if(!is_file($file))throw new \RuntimeException('Vista no encontrada.');require $file;}}
