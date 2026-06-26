<?php

namespace Drewlabs\GCli\Plugins\Laravel\Observers;

use Drewlabs\GCli\Plugins\Laravel\Expressions\ComposedExpression;
use Drewlabs\GCli\Plugins\Laravel\Expressions\LiteralExpression;
use Drewlabs\GCli\Plugins\Laravel\Expressions\PropertyChangedExpression;
use Drewlabs\PHPSQLC\Compiler;
use Drewlabs\PHPSQLC\Eloquent\Builder;
use LogicException;

final class QueryExpression
{

    /** @var string */
    private $table;

    /** @var string */
    private $query;

    /** @var ?\Stringable */
    private $changedExpression;

    /** @var ?\Stringable */
    private $condition;

    /**
     * query expression class constructor
     * 
     * @param string $table 
     * @param string $query 
     * @param \Stringable $changedExpression 
     * @param \Stringable $condition 
     * @return void 
     */
    public function __construct(string $table, string $query, $changedExpression = null, $condition = null)
    {
        $this->table = $table;
        $this->query = $query;
        $this->changedExpression = $changedExpression;
        $this->condition = $condition;
    }

    /**
     * query expression factory constructor
     * 
     * @param string $haystack 
     * @return static 
     */
    public static function create(string $haystack)
    {
        /** @var string|null */
        $property = null;

        /** @var string|null */
        $condition = null;

        /** @var string|null */
        $changed = null;

        /** @var string|null */
        $query = null;

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

        if (str_contains($haystack, 'FROM')) {
            $pos = strpos($haystack, 'FROM');
            $table = trim(substr($haystack, $pos + strlen('FROM')));
            $haystack = substr($haystack, 0, $pos);
        }

        if (str_contains($haystack, 'SQL')) {
            $pos = strpos($haystack, 'SQL');
            $query = trim(substr($haystack, $pos + strlen('SQL')));
            $haystack = substr($haystack, 0, $pos);
        }

        if (empty($table)) {
            throw new LogicException('table name is expected in query expression');
        }

        if (empty($query)) {
            throw new LogicException('bad expressipon, cannot locate SQL query construct');
        }

        return new static($table, $query, $changed, (is_callable($condition) ? ($property ? call_user_func($condition, $property) : null) : $condition));
    }

    public function __toString(): string
    {
        $compiler = new Compiler(new Builder(['facade' => 'DB::']));
        $query = str_replace('DB::table(\'' . trim($this->table) . '\')', '$model->' . $this->table . '()', $compiler->compile(sprintf('FROM %s %s', $this->table, $this->query), false));

        return $this->condition ?
            sprintf("if (%s%s) {\n    %s \n}", $this->changedExpression ?? '', sprintf('%s%s', $this->changedExpression ? ' && ' : '', $this->condition), $query)
            : sprintf('%s', $query);
    }
}
