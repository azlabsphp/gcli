<?php

namespace Drewlabs\GCli\Plugins\Laravel\Observers;

use Drewlabs\CodeGenerator\Contracts\FunctionParameterInterface;
use Drewlabs\CodeGenerator\Models\PHPConstructorParameter;
use Drewlabs\GCli\Contracts\ComponentBuilder;
use Drewlabs\GCli\Plugins\Laravel\EventBuilder;

final class Event
{
    /** @var string */
    private $name;

    /** @var string */
    private $namespace = 'App';

    /** @var FunctionParameterInterface[] */
    private $params = [];

    /**
     * creates event class instance
     * 
     * @param string $name 
     * @param string|array $params 
     * @param null|string $namespace 
     * @return void 
     */
    public function __construct(string $name, $params = null, ?string $namespace = 'App')
    {
        $this->name = $name;
        $this->namespace = $namespace ?? 'App';
        if (!is_null($params)) {
            $params = is_string($params) ? explode(',', $params) : (array)$params;
            foreach ($params as $p) {
                if ($p instanceof FunctionParameterInterface) {
                    $this->params[] = $p;
                    continue;
                }
                $p = trim(strval($p));
                $pos = strpos($p, ':');
                $this->params[] = new PHPConstructorParameter(trim(substr($p, 0, $pos)), trim(substr($p, $pos + 1)));
            }
        }
    }

    /**
     * returns event namespace path
     * 
     * @return string 
     */
    public function getClasspath(): string
    {
        return sprintf("\\%s\\%s", rtrim($this->getNamespace(), '\\'), $this->name);
    }

    /**
     * returns event class name
     * 
     * @return string 
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * returns event class namespace
     * 
     * @return string 
     */
    public function getNamespace(): string
    {
        return sprintf("%s\\%s", rtrim($this->namespace, '\\'), 'Events');
    }

    /**
     * returns event constructor parameter list
     * 
     * @return FunctionParameterInterface[] 
     */
    public function getParams(): array
    {
        return $this->params ?? [];
    }

    /**
     * returns the event listener instance
     * 
     * @return Listener 
     */
    public function getListener(): Listener
    {
        return new Listener($this);
    }

    /**
     * returns event component builder instance
     * 
     * @param null|string $path 
     * @return ComponentBuilder 
     */
    public function getBuilder(?string $path = null): ComponentBuilder
    {
        return new EventBuilder($this, $path);
    }
}
