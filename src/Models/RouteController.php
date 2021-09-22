<?php

namespace Drewlabs\ComponentGenerators\Models;

use Drewlabs\Support\Immutable\ValueObject;

class RouteController extends ValueObject
{

    protected function getJsonableAttributes()
    {
        return ['namespace', 'name'];
    }

    public function getName()
    {
        return $this->name;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function __serialize(): array
    {
        return [
          'name' => $this->getName(),
          'namespace' => $this->getNamespace()  
        ];
    }
}
