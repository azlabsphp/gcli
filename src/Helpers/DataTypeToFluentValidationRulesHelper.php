<?php

namespace Drewlabs\ComponentGenerators\Helpers;

use Closure;
use Drewlabs\ComponentGenerators\Contracts\ForeignKeyConstraintDefinition;
use Drewlabs\ComponentGenerators\Contracts\UniqueKeyConstraintDefinition;

class DataTypeToFluentValidationRulesHelper
{
    private const TYPE_MAPS = [
        'bigint' => 'integer',
        'int' => 'integer',
        'integer' => 'integer',
        'decimal' => 'numeric',
        'float' => 'numeric',
        'string' => 'string',
        'text' => 'string',
        'boolean' => 'boolean',
        'date' => 'date',
        'datetime' => 'date_format'
    ];

    public const REQUIRED_IF = 'required_if';

    public const REQUIRED_WITHOUT = 'required_without';

    public const REQUIRED = 'required';

    public const NULLABLE = 'nullable';

    public const SOMETIMES = 'sometimes';

    /**
     * Resolve a type definition to a fluent rule
     * 
     * @param string|ForeignKeyConstraintDefinition|UniqueKeyConstraintDefinition $type 
     * @param Closure|null $filterFn 
     * @return array 
     */
    public static function getRule($definition, \Closure $filterFn = null)
    {
        // Handle Foreign key definition rules
        if ($definition instanceof ForeignKeyConstraintDefinition) {
            return [self::getExistsRule($definition->getForeignTableName(), $definition->getForeignColumns())];
        }
        // Handle unique columns definitions
        if ($definition instanceof UniqueKeyConstraintDefinition) {
            return [self::getExistsRule($definition->getTable(), $definition->getColumns())];
        }
        // Handle unique rules
        if (!is_string($definition)) {
            throw new \Exception('$definition parameter type must be a PHP string or instance of ' . ForeignKeyConstraintDefinition::class);
        }
        // Here we handle string types
        return self::getSimpleRule($definition, $filterFn);
        
    }

    private static function getSimpleRule(string $type, \Closure $filterFn = null)
    {
        if (!drewlabs_core_strings_contains($type, ':')) {
            return array_filter([self::TYPE_MAPS[$type] ?? null], $filterFn ?? function ($item) {
                return null !== $item;
            });
        }
        list($part0, $part1) = [drewlabs_core_strings_before(':', $type), drewlabs_core_strings_after(':', $type)];
        list($rule1, $rule2) = [self::TYPE_MAPS[$part0] ?? null, null];
        if (in_array($rule1, ['string', 'numeric', 'integer'])) {
            $rule2 = "max:{$part1}";
        }
        if (in_array($rule1, ['date_format'])) {
            $format = $part1 ?? 'Y-m-d H:i:s';
            $rule1 = "$rule1:{$format}";
        }
        return array_filter([$rule1, $rule2], $filterFn ?? function ($item) {
            return null !== $item;
        });
    }

    /**
     * 
     * @param string $table 
     * @param string|string[] $columns 
     * @return string 
     */
    public static function getExistsRule(string $table, $columns)
    {
        return sprintf(
            "exists:%s,%s",
            $table,
            is_array($columns) ? ($columns[0] ?? 'id') : ($columns ?? 'id')
        );
    }
}
