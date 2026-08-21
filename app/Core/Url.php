<?php
declare(strict_types=1);

namespace CardPay\Core;

final class Url
{
    public static function basePath(): string
    {
        $script = str_replace('\\','/',(string)($_SERVER['SCRIPT_NAME']??''));
        $dir=rtrim(dirname($script),'/');
        if(str_ends_with($dir,'/public'))$dir=substr($dir,0,-7);
        return $dir==='.'||$dir==='/'?'':$dir;
    }
    public static function to(string $path=''):string{return self::basePath().'/'.ltrim($path,'/');}
}
