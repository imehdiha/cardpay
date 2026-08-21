<?php
declare(strict_types=1);
$root=dirname(__DIR__);require is_file($root.'/vendor/autoload.php')?$root.'/vendor/autoload.php':$root.'/app/autoload.php';$container=require$root.'/app/bootstrap.php';(new CardPay\Core\Migrator($container->get(CardPay\Core\Database::class),$root.'/database/migrations'))->migrate();echo"Migrations completed.\n";
