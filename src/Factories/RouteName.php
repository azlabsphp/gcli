<?php

namespace Drewlabs\GCli\Factories;

use Drewlabs\CodeGenerator\Helpers\Str;

class RouteName
{

    /**
     * Creates new class instance
     * 
     * @return static 
     */
    public static function new()
    {
        return new static;
    }

    /**
     * Creates route name from controller name
     * 
     * @param string $controller 
     * @return string 
     */
    public function createRouteName(string $controller)
    {
        $controller = empty($controller) || is_null(null === $controller) ? 'TestsController' : $controller;
        return Str::snakeCase(str_replace('Controller', '', $controller), '-');
    }
}