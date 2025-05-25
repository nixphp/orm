<?php

namespace NixPHP\ORM;

use NixPHP\ORM\Core\EntityManager;
use function NixPHP\app;

function em():? EntityManager
{
    return app()->container()->get('em');
}