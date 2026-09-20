<?php
declare(strict_types=1);

namespace App\Core;

final class SearchText
{
    private const FOLD=[
        'á'=>'a','à'=>'a','ä'=>'a','â'=>'a','ã'=>'a','å'=>'a',
        'é'=>'e','è'=>'e','ë'=>'e','ê'=>'e',
        'í'=>'i','ì'=>'i','ï'=>'i','î'=>'i',
        'ó'=>'o','ò'=>'o','ö'=>'o','ô'=>'o','õ'=>'o',
        'ú'=>'u','ù'=>'u','ü'=>'u','û'=>'u',
        'ñ'=>'n','ç'=>'c',
        'Á'=>'a','À'=>'a','Ä'=>'a','Â'=>'a','Ã'=>'a','Å'=>'a',
        'É'=>'e','È'=>'e','Ë'=>'e','Ê'=>'e',
        'Í'=>'i','Ì'=>'i','Ï'=>'i','Î'=>'i',
        'Ó'=>'o','Ò'=>'o','Ö'=>'o','Ô'=>'o','Õ'=>'o',
        'Ú'=>'u','Ù'=>'u','Ü'=>'u','Û'=>'u',
        'Ñ'=>'n','Ç'=>'c',
    ];

    public static function normalize(string $value): string
    {
        $value=strtr($value,self::FOLD);
        $value=mb_strtolower($value,'UTF-8');
        $value=(string)preg_replace('/[^\p{L}\p{N}]+/u',' ',$value);
        return (string)preg_replace('/\s+/u',' ',trim($value));
    }

    public static function tokens(string $query): array
    {
        $normalized=self::normalize($query);
        if($normalized==='')return[];
        return array_values(array_unique(array_filter(explode(' ',$normalized),static fn(string $token):bool=>$token!=='')));
    }

    public static function matches(string $haystack,string $query): bool
    {
        $tokens=self::tokens($query);
        if($tokens===[])return true;
        $normalized=self::normalize($haystack);
        foreach($tokens as $token){
            if(!str_contains($normalized,$token))return false;
        }
        return true;
    }
}
