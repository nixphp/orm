<?php

namespace NixPHP\ORM;

use NixPHP\ORM\Core\EntityManager;
use NixPHP\ORM\Repository\AbstractRepository;
use function NixPHP\app;
use function NixPHP\Database\database;

function em():? EntityManager
{
    return app()->container()->get('em');
}

function repo(string $repository): AbstractRepository
{
    return new $repository(database());
}