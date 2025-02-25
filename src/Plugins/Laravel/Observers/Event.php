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
     * creates event class instance.
     *
     * @param string|array $params
     *
     * @return void
     */
    public function __construct(string $name, $params = null, ?string $namespace = 'App')
    {
        $this->name = $name;
        $this->namespace = $namespace ?? 'App';
        if (null !== $params) {
            $params = \is_string($params) ? explode(',', $params) : (array) $params;
            foreach ($params as $p) {
                if ($p instanceof FunctionParameterInterface) {
                    $this->params[] = $p;
                    continue;
                }
                $p = trim((string) $p);
                $pos = strpos($p, ':');
                $this->params[] = new PHPConstructorParameter(trim(substr($p, 0, $pos)), trim(substr($p, $pos + 1)));
            }
        }
    }

    /**
     * returns event namespace path.
     */
    public function getClasspath(): string
    {
        return sprintf('\\%s\\%s', rtrim($this->getNamespace(), '\\'), $this->name);
    }

    /**
     * returns event class name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * returns event class namespace.
     */
    public function getNamespace(): string
    {
        return sprintf('%s\\%s', rtrim($this->namespace, '\\'), 'Events');
    }

    /**
     * returns event constructor parameter list.
     *
     * @return FunctionParameterInterface[]
     */
    public function getParams(): array
    {
        return $this->params ?? [];
    }

    /**
     * returns the event listener instance.
     */
    public function getListener(): Listener
    {
        return new Listener($this);
    }

    /**
     * returns event component builder instance.
     */
    public function getBuilder(?string $path = null): ComponentBuilder
    {
        return new EventBuilder($this, $path);
    }
}
