<?php

namespace Drewlabs\GCli\Plugins\Laravel\Observers;

use BadMethodCallException;
use Drewlabs\CodeGenerator\Models\PHPConstructorParameter;
use Drewlabs\Core\Helpers\Str;

final class EventExpression
{
    /** @var Event */
    private $event;

    /** @var \Stringable */
    private $condition;

    /** @var array|null */
    private $formatters;

    /**
     * Create new event observer expression instance
     * 
     * @param string $event 
     * @param null|array $params 
     * @param string $namespace 
     * @param \Stringable $trigger 
     * @return void 
     */
    public function __construct(string $event, ?array $params = null, string $namespace = 'App', $trigger = null)
    {
        $this->condition = $trigger;
        $items = [];
        $expressions = [];
        foreach ($params as $p) {
            if ($pos = strpos($p, ':')) {
                $name = trim(substr($p, 0, $pos));
                $type = trim(substr($p, $pos + 1));
                $camelized = Str::camelize($name, false);
                switch (strtolower($type)) {
                    case 'float':
                    case 'decimal':
                        $items[] = new PHPConstructorParameter($camelized, 'float');
                        $expressions[] = [$name, "floatval(%s)"];
                        break;
                    case 'int':
                        $items[] = new PHPConstructorParameter($camelized, 'int');
                        $expressions[] = [$name, "intval(%s)"];
                        break;
                    case 'bool':
                        $items[] = new PHPConstructorParameter($camelized, 'bool');
                        $expressions[] = [$name, "boolval(%s)"];
                        break;
                    case 'str':
                    case 'string':
                        $items[] = new PHPConstructorParameter($camelized, 'string');
                        $expressions[] = [$name, "strval(%s)"];
                        break;
                    case 'date':
                        $items[] = new PHPConstructorParameter($camelized, '\DateTimeInterface');
                        $expressions[] = [$name, "\DateTimeImmutable::createFromTimestamp(strtotime(%s))"];
                        break;
                    default:
                        $items[] = new PHPConstructorParameter($camelized);
                        $expressions[] = [$name, "%s"];
                        break;
                }
                continue;
            }
            $items[] = new PHPConstructorParameter(Str::camelize($p, false));
            $expressions[] = [$p, "%s"];
        }

        // Use the model as parameter if event does not take any argument
        // as on line 133, it will be passed as parameter to event call
        if (empty($items)) {
            $items = [new PHPConstructorParameter('model')];
        }

        $this->event = new Event($event, $items, $namespace);
        $this->formatters = $expressions;
    }

    public static function create(string $haystack, string $namespace = 'App')
    {
        if (!empty($params = Expression::new($haystack)->read('dispatch', $offset))) {
            $next = trim(mb_substr($haystack, $offset + 1));
            if (empty($next)) {
                return new EventExpression(Str::camelize($params[0]), array_slice($params, 1), $namespace);
            }

            if (!empty($p = Expression::new($next)->read('->onChange'))) {
                $expression = new PropertyChangedLogicalExpression(...$p);
                return new EventExpression(Str::camelize($params[0]), array_slice($params, 1), $namespace, $expression);
            }

            if (!empty($p = Expression::new($next)->read('->changed'))) {
                $expression = new PropertyChangedLogicalExpression(...$p);
                return new EventExpression(Str::camelize($params[0]), array_slice($params, 1), $namespace, $expression);
            }

            if (!empty($p = Expression::new($next)->read('->if'))) {
                $expression = new PropertyLogicalExpression(...$p);
                return new EventExpression(Str::camelize($params[0]), array_slice($params, 1), $namespace, $expression);
            }
        }

        throw new BadMethodCallException('dispatch expression not correctly formed, supported syntax is dispatch(event_name, id:string, name:string)->if(property, value)');
    }

    /**
     * returns the event for the current expression
     * 
     * @return Event 
     */
    public function getEvent(): Event
    {
        return $this->event;
    }

    public function __toString(): string
    {
        if ($this->condition) {
            return sprintf("if (%s) {\n    %s \n}", strval($this->condition), $this->createExpression($this->event, $this->formatters));
        }
        return sprintf("%s", $this->createExpression($this->event, $this->formatters));
    }

    private function createExpression(Event $e, array $formatters)
    {
        $expression = !empty($formatters) ? array_map(function ($p) {
            list($name, $format) = $p;
            return sprintf($format, sprintf("\$model->getRawPropertyValue('%s')", $name));
        }, $formatters) : ["\$model"];
        return sprintf("\\Illuminate\\Support\\Facades\\Event::dispatch(new %s(%s));", $e->getClasspath(), implode(', ', $expression));
    }
}
