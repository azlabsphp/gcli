<?php

declare(strict_types=1);

/*
 * This file is part of the drewlabs namespace.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
     * listener class constructor.
     *
     * @return void
     */
    public function __construct(Event $e, string $namespace = 'App')
    {
        $this->event = $e;
        $this->namespace = $namespace;
    }

    /**
     * returns the source event instance.
     */
    public function getEvent(): Event
    {
        return $this->event;
    }

    /**
     * return class path to listener source code.
     */
    public function getClasspath(): string
    {
        return sprintf('%s\\%s', rtrim($this->getNamespace(), '\\'), $this->getName());
    }

    /**
     * return class name of the listener source code.
     */
    public function getName(): string
    {
        return sprintf('%sListener', $this->event->getName());
    }

    /**
     * returns listener namespace or domain.
     */
    public function getNamespace(): string
    {
        return sprintf('\\%s\\%s', rtrim($this->namespace, '\\'), 'Listeners');
    }

    /**
     * creates listener source code builder.
     */
    public function getBuilder(?string $path = null): ComponentBuilder
    {
        return new ListenerBuilder($this, $path);
    }
}
