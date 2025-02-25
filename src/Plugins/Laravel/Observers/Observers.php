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

final class Observers
{
    /** @var self */
    private static $instance;

    /** @var array<string,array<\Stringable>> */
    private $observers = [];

    /** @var Event[] */
    private $events = [];

    /** @constructor */
    private function __construct()
    {
    }

    /**
     * returns observers singleton instance.
     *
     * @return Observers
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    /**
     * configure a list of observers from the provided array for a given namespace.
     *
     * @throws \BadMethodCallException
     *
     * @return void
     */
    public function configure(array $observers, string $namespace = 'App')
    {
        foreach ($observers as $name => $model) {
            if (!\is_array($model)) {
                continue;
            }
            foreach ($model as $observer => $expressions) {
                $expressions = \is_array($expressions) ? $expressions : [$expressions];
                foreach ($expressions as $expression) {
                    if (!\is_string($expression)) {
                        continue;
                    }

                    if ('set(' === mb_substr(trim($expression), 0, \strlen('set('))) {
                        $e = PropertyExpression::create($expression);
                        $this->addObserver($name.'.'.$observer, $e);
                        continue;
                    }

                    if ('dispatch(' === mb_substr(trim($expression), 0, \strlen('dispatch('))) {
                        $e = EventExpression::create($expression, $namespace);
                        $this->events[] = $e->getEvent();
                        $this->addObserver($name.'.'.$observer, $e);
                        continue;
                    }
                    // if required, add support for other expression parsers
                }
            }
        }
    }

    /**
     * returns the list of observers.
     *
     * @return array<string, array<\Stringable>>
     */
    public function getObservers()
    {
        return $this->observers;
    }

    /**
     * returns the list of namespace events.
     *
     * @return Event[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * get an observer matching the provided name parameter.
     *
     * @return \Stringable[]|null
     */
    public function get(string $name)
    {
        return $this->observers[$name] ?? null;
    }

    /**
     * appends an expression to a model observer.
     *
     * @param mixed $expression
     *
     * @return void
     */
    private function addObserver(string $event, $expression)
    {
        if (\array_key_exists($event, $this->observers)) {
            $this->observers[$event][] = $expression;
        } else {
            $this->observers[$event] = [$expression];
        }
    }
}
