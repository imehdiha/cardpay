<?php
declare(strict_types=1);

namespace CardPay\Services;

use CardPay\Core\Config;
use CardPay\Core\DomainException;

final class UploadService
{
    public function __construct(private readonly string$root,private readonly Config$config){}
    public function branding(array$file,string$kind):string
    {
        if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new DomainException('upload_failed','فایل برندینگ آپلود نشد.',422);$max=min(2,(int)$this->config->get('upload.max_mb',5))*1024*1024;if((int)($file['size']??0)>$max)throw new DomainException('upload_too_large','حجم فایل برندینگ حداکثر ۲ مگابایت است.',422);$mime=(new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);$allowed=$kind==='favicon'?['image/png'=>'png','image/x-icon'=>'ico','image/vnd.microsoft.icon'=>'ico']:['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'];if(!isset($allowed[$mime]))throw new DomainException('invalid_upload','نوع فایل برندینگ مجاز نیست.',422);$name=$kind.'-'.bin2hex(random_bytes(16)).'.'.$allowed[$mime];if(!move_uploaded_file($file['tmp_name'],$this->root.'/public/uploads/'.$name))throw new DomainException('upload_failed','ذخیره فایل برندینگ انجام نشد.',500);return$name;
    }
}
