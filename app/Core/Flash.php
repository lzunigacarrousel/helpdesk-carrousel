<?php
declare(strict_types=1);namespace App\Core;final class Flash{public static function set(string $m,string $t='info'):void{$_SESSION['flash']=['message'=>$m,'type'=>$t];}public static function pull():?array{$f=$_SESSION['flash']??null;unset($_SESSION['flash']);return $f;}}
