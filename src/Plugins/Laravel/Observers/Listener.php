<?php

namespace Drewlabs\GCli\Plugins\Laravel\Observers;

use Drewlabs\GCli\Contracts\ComponentBuilder;
use Drewlabs\GCli\Plugins\Laravel\ListenerBuilder;

final class Listener
{

    /** @var Event */
    private $event;

    /** @var string */
    private $namespace;

    /**
     * listener class constructor
     * 
     * @param Event $e 
     * @return void 
     */
    public function __construct(Event $e, string $namespace = 'App')
    {
        $this->event = $e;
        $this->namespace  = $namespace;
    }

    /**
     * returns the source event instance
     * 
     * @return Event 
     */
    public function getEvent(): Event
    {
        return $this->event;
    }

    /**
     * return class path to listener source code
     * 
     * @return string 
     */
    public function getClasspath(): string
    {
        return sprintf("%s\\%s", rtrim($this->getNamespace(), '\\'), $this->getName());
    }

    /**
     * return class name of the listener source code
     * 
     * @return string 
     */
    public function getName(): string
    {
        return sprintf("%sListener", $this->event->getName());
    }

    /**
     * returns listener namespace or domain
     * 
     * @return string 
     */
    public function getNamespace(): string
    {
        return sprintf("\\%s\\%s", rtrim($this->namespace, '\\'), 'Listeners');
    }

    /**
     * creates listener source code builder
     * 
     * @param null|string $path 
     * @return ComponentBuilder 
     */
    public function getBuilder(?string $path = null): ComponentBuilder
    {
        return new ListenerBuilder($this, $path);
    }
}
