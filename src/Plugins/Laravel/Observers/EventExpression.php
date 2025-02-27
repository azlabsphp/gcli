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
use Drewlabs\GCli\Plugins\Laravel\Expressions\Property;

final class EventExpression
{
    /** @var Event */
    private $event;

    /** @var \Stringable */
    private $condition;

    /** @var array|null */
    private $variables;

    /** @var \Stringable */
    private $changedExpression;

    /**
     * Create new event observer expression instance.
     *
     * @param \Stringable $condition
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
                        $items[] = new PHPConstructorParameter((string)$camelized, 'float');
                        $variables[] = Property::create($p);
                        break;
                    case 'int':
                        $items[] = new PHPConstructorParameter((string)$camelized, 'int');
                        $variables[] = Property::create($p);
                        break;
                    case 'bool':
                        $items[] = new PHPConstructorParameter((string)$camelized, 'bool');
                        $variables[] = Property::create($p);
                        break;
                    case 'str':
                    case 'string':
                        $items[] = new PHPConstructorParameter((string)$camelized, 'string');
                        $variables[] = Property::create($p);
                        break;
                    case 'date':
                        $items[] = new PHPConstructorParameter((string)$camelized, '\DateTimeInterface');
                        $variables[] = Property::create($p);
                        break;
                    default:
                        $items[] = new PHPConstructorParameter((string)$camelized);
                        $variables[] = Property::create($p);
                        break;
                }
                continue;
            }
            $items[] = new PHPConstructorParameter((string)(new PropertyName((Str::camelize($p, false)))));
            $variables[] = Property::create($p);
        }

        // Use the model as parameter if event does not take any argument
        // as on line 133, it will be passed as parameter to event call
        if (empty($items)) {
            $items = [new PHPConstructorParameter('model')];
        }

        $this->variables = $variables;
        $this->condition = $condition;
        $this->changedExpression = $changedExpression;
        $this->event = new Event($event, $items, $namespace);
    }

    public function __toString(): string
    {
        if ($this->condition) {
            return sprintf("if (%s%s) {\n    %s \n}", $this->changedExpression ?? '', $this->condition ? sprintf('%s%s', $this->changedExpression ? ' && ' : '', $this->condition) : '', $this->createExpression($this->event, $this->variables));
        }

        return sprintf('%s', $this->createExpression($this->event, $this->variables));
    }

    public static function create(string $haystack, string $namespace = 'App')
    {
        if (!empty($params = Expression::new($haystack)->read('dispatch', $offset))) {
            $next = trim(substr($haystack, $offset + 1));
            if (empty($next)) {
                return new self(Str::camelize($params[0]), \array_slice($params, 1), $namespace);
            }

            $changedExpression = null;
            if (str_contains($next, '->onChange') && !empty($p = Expression::new($next)->read('->onChange', $offset))) {
                $next = ltrim(substr($next, $offset + 1));
                $changedExpression = new PropertyChangedExpression(...$p);
            }

            if (str_contains($next, '->changed') && !empty($p = Expression::new($next)->read('->changed', $offset))) {
                $next = ltrim(substr($next, $offset + 1));
                $changedExpression = new PropertyChangedExpression(...$p);
            }

            $next = ltrim($next);
            $condition = null;
            if (str_starts_with($next, '->if')) {
                $condition = ComposedExpression::compile(substr($next, \strlen('->if')));
            }

            return new self(Str::camelize($params[0]), \array_slice($params, 1), $namespace, $changedExpression, $condition);
        }

        throw new \BadMethodCallException('dispatch expression not correctly formed, supported syntax is dispatch(event_name, [id]:string, [name]:string)->if(property == value)');
    }

    /**
     * returns the event for the current expression.
     */
    public function getEvent(): Event
    {
        return $this->event;
    }

    private function createExpression(Event $e, array $variables)
    {
        $expression = !empty($variables) ? array_map(static function ($p) {
            return sprintf('%s', $p);
        }, $variables) : ['$model'];

        return sprintf('\\Illuminate\\Support\\Facades\\Event::dispatch(new %s(%s));', $e->getClasspath(), implode(', ', $expression));
    }
}
