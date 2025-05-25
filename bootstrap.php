<?php

use NixPHP\ORM\Core\EntityManager;
use function NixPHP\app;
use function NixPHP\Database\database;

app()->container()->set('em', function() {
    return new EntityManager(database());
});