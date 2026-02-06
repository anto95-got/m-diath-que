<?php

declare(strict_types=1);

use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;

$config = new Configuration();
$config->addMigrationsDirectory('DoctrineMigrations', __DIR__);
$config->setAllOrNothing(false);
$config->setTransactional(false);

return DependencyFactory::fromConfiguration($config);
