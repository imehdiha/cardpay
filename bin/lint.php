<?php
declare(strict_types=1);

$root=dirname(__DIR__);$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));$failed=[];
foreach($iterator as$file){if($file->getExtension()!=='php'||str_contains($file->getPathname(),DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR))continue;$output=[];$code=0;exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file->getPathname()),$output,$code);if($code!==0)$failed[]=$file->getPathname();}
if($failed){fwrite(STDERR,"PHP lint failed:\n".implode("\n",$failed)."\n");exit(1);}echo"PHP syntax check passed.\n";
