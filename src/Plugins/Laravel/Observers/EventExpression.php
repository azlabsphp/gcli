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

use Drewlabs\CodeGenerator\Models\PHPConstructorParameter;
use Drewlabs\Core\Helpers\Str;
use Drewlabs\GCli\Plugins\Laravel\Expressions\ComposedExpression;
use Drewlabs\GCli\Plugins\Laravel\Expressions\LiteralExpression;
use Drewlabs\GCli\Plugins\Laravel\Expressions\Property;
use Drewlabs\GCli\Plugins\Laravel\Expressions\PropertyChangedExpression;
use Drewlabs\GCli\Plugins\Laravel\Expressions\PropertyName;
use Drewlabs\CodeGenerator\Contracts\FunctionParameterInterface;

final class EventExpression implements Observer
{
    use HasEventSource;

    /** @var string */
    private $event;

    /** @var FunctionParameterInterface[] */
    private $params;

    /** @var string */
    private $namespace;

    /** @var \Stringable|null */
    private $condition = null;

    /** @var array|null */
    private $variables;

    /** @var \Stringable|null */
    private $changedExpression;

    /**
     * Create new event observer expression instance.
     *
     * @param \Stringable|null $condition
     *
     * @return void
     */
    public function __construct(string $event, ?array $params = null, string $namespace = 'App', $changedExpression = null, $condition = null)
    {
        $items = [];
        $variables = [];
        foreach ($params as $p) {
            // We look for the last occurrence of : as the type is after last occurrence of :
            if ($pos = strrpos($p, ':')) {
                $name = trim(substr($p, 0, $pos));
                $type = trim(substr($p, $pos + 1));
                $camelized = new PropertyName(Str::camelize($name, false));
                switch (strtolower($type)) {
                    case 'float':
                    case 'decimal':
                        $items[] = new PHPConstructorParameter((string) $camelized, 'float');
                        $variables[] = Property::create($p);
                        break;
                    case 'int':
                        $items[] = new PHPConstructorParameter((string) $camelized, 'int');
                        $variables[] = Property::create($p);
                        break;
                    case 'bool':
                        $items[] = new PHPConstructorParameter((string) $camelized, 'bool');
                        $variables[] = Property::create($p);
                        break;
                    case 'str':
                    case 'string':
                        $items[] = new PHPConstructorParameter((string) $camelized, 'string');
                        $variables[] = Property::create($p);
                        break;
                    case 'date':
                        $items[] = new PHPConstructorParameter((string) $camelized, '\DateTimeInterface');
                        $variables[] = Property::create($p);
                        break;
                    default:
                        $items[] = new PHPConstructorParameter((string) $camelized);
                        $variables[] = Property::create($p);
                        break;
                }
                continue;
            }
            $items[] = new PHPConstructorParameter((string) (new PropertyName(Str::camelize($p, false))));
            $variables[] = Property::create($p);
        }

        $this->variables = $variables;
        $this->condition = $condition;
        $this->changedExpression = $changedExpression;
        $this->namespace = $namespace;
        $this->event = $event;
        $this->params = $items;
    }

    public function __toString(): string
    {
        $event = $this->getEvent();

        if ($this->condition) {
            $condition = sprintf('%s%s', $this->changedExpression ? ' && ' : '', $this->condition);
            return sprintf("if (%s%s) {\n    %s \n}", $this->changedExpression ?? '', $condition , $this->createExpression($event, $this->variables));
        }
        return sprintf('%s', $this->createExpression($event, $this->variables));
    }

    public static function create(string $haystack, string $namespace = 'App')
    {
        /** @var string|null */
        $property = null;

        /** @var string|\Stringable|\Closure|null */
        $condition = null;

        /** @var string|null */
        $changed = null;

        /** @var string|null */
        $dispatch = null;

        $pos = strlen($haystack) - 1;

        if (str_contains($haystack, 'CHANGED')) {
            $pos = strpos($haystack, 'CHANGED');
            $changed = new PropertyChangedExpression($property = trim(substr($haystack, $pos + strlen('CHANGED'))));
            $haystack = substr($haystack, 0, $pos);
        } else if (str_contains($haystack, 'ON CHANGE')) {
            $pos = strpos($haystack, 'ON CHANGE');
            $changed = new PropertyChangedExpression($property = trim(substr($haystack, $pos + strlen('ON CHANGE'))));
            $haystack = substr($haystack, 0, $pos);
        }
        
        if (str_contains($haystack, 'IF NOT NULL')) {
            $pos = strpos($haystack, 'IF NOT NULL');
            $string = trim(substr($haystack, $pos + strlen('IF NOT NULL')));
            $property = !empty(trim($string)) ? $string : $property;
            $condition = $property ? new LiteralExpression($property, 'null', '!==') : null;
            $haystack = substr($haystack, 0, $pos);
        } else if (str_contains($haystack, 'IF NULL')) {
            $pos = strpos($haystack, 'IF NULL');
            $string = trim(substr($haystack, $pos + strlen('IF NULL')));
            $property = !empty(trim($string)) ? $string : $property;
            $condition = $property ? new LiteralExpression($property, 'null', '!==') : null;
            $haystack = substr($haystack, 0, $pos);
        } else if (str_contains($haystack, 'IF')) {
            $pos = strpos($haystack, 'IF');
            $condition = ComposedExpression::compile(trim(substr($haystack, $pos + strlen('IF'))));
            $haystack = substr($haystack, 0, $pos);
        }

        if (str_contains($haystack, 'DISPATCH')) {
            $pos = strpos($haystack, 'DISPATCH');
            $dispatch = trim(substr($haystack, $pos + strlen('DISPATCH')));
            $haystack = substr($haystack, 0, $pos);
        }

        // @phpstan-ignore empty.expr
        if (empty($params = explode('WITH', $dispatch ?? ''))) {
            throw new \BadMethodCallException('dispatch expression not correctly formed, supported syntax is DISPATCH event_name WITH [id]:string, [name]:string IF property == value CHANGED [name]');
        }

        $dispatchParams = array_map(function ($item) {
            return trim($item);
        }, preg_split('/\s+/', $params[1] ?? '', -1, \PREG_SPLIT_NO_EMPTY));

        return new static(Str::camelize(trim($params[0])), $dispatchParams, $namespace, $changed, is_callable($condition) ? ($property ? call_user_func($condition, $property) : null) : $condition);
    }

    /**
     * returns the event for the current expression.
     */
    public function getEvent(): Event
    {
        $params = empty($this->params) ? [new PHPConstructorParameter('model', $this->eventSource ? $this->eventSource->getClassPath() : null)] : $this->params;
        print_r([get_class($this->eventSource ?? new \stdClass)], );
        return new Event($this->event, $params, $this->namespace);
    }

    private function createExpression(Event $e, array $variables)
    {
        $expression = !empty($variables) ? array_map(static function ($p) {
            return sprintf('%s', $p);
        }, $variables) : ['$model'];

        return sprintf('\\Illuminate\\Support\\Facades\\Event::dispatch(new %s(%s));', $e->getClasspath(), implode(', ', $expression));
    }
}
