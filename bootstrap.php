<?php

declare(strict_types=1);

use NixPHP\Core\Container;
use NixPHP\ORM\Core\EntityManager;
use NixPHP\Database\Core\Database;
use function NixPHP\app;

app()->container()->set(
    EntityManager::class,
    fn(Container $container) =>
        new EntityManager(
            $container->get(Database::class)->getConnection()
        )
);