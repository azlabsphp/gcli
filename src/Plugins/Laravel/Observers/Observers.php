<?php

namespace Drewlabs\GCli\Plugins\Laravel\Observers;

final class Observers
{
    /** @var self */
    private static $instance;

    /**  @var array<string,array<\Stringable>> */
    private $observers = [];

    /** @constructor */
    private function __construct() {}

    /**
     * returns observers singleton instance
     * 
     * @return Observers 
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new self;
        }
        return static::$instance;
    }

    public function configure(array $observers)
    {
        foreach ($observers as $name => $model) {
            if (!is_array($model)) {
                continue;
            }
            printf("model: %s\n", $name);
            foreach ($model as $observer => $expressions) {
                printf("observer: %s\n", $observer);
                $expressions = is_array($expressions) ? $expressions : [$expressions];
                foreach ($expressions as $expression) {
                    if (!is_string($expression)) {
                        continue;
                    }

                    if (mb_substr(trim($expression), 0, strlen('set(')) === 'set(') {
                        $e = PropertyExpression::create($expression);
                        $this->addObserver($name . "." . $observer, $e);
                        continue;
                    }

                    if (mb_substr(trim($expression), 0, strlen('dispatch(')) === 'dispatch(') {
                        printf("Expression: %s\n", $expression);
                        $e = EventExpression::create($expression);
                        $this->addObserver($name . "." . $observer, $e);
                        continue;
                    }

                    // if required, add support for other expression parsers
                }
            }
        }
    }

    /**
     * appends an expression to a model observer
     * 
     * @param string $event 
     * @param mixed $expression 
     * @return void 
     */
    public function addObserver(string $event, $expression)
    {
        if (array_key_exists($event, $this->observers)) {
            $this->observers[$event][] = $expression;
        } else {
            $this->observers[$event] = [$expression];
        }
    }

    /**
     * returns the list of observers
     * 
     * @return array<string, array<\Stringable>> 
     */
    public function getObservers()
    {
        printf("Reading observers....\n");
        return $this->observers;
    }

    /**
     * get an observer matching the provided name parameter
     * 
     * @param string $name 
     * @return Stringable|null 
     */
    public function get(string $name)
    {
        return $this->observers[$name] ?? null;
    }
}
