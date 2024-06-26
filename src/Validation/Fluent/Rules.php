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

namespace Drewlabs\GCli\Validation\Fluent;

use Drewlabs\Core\Helpers\Str;
use Drewlabs\GCli\Contracts\ForeignKeyConstraintDefinition;
use Drewlabs\GCli\Contracts\UniqueKeyConstraintDefinition;

class Rules
{
    /** @var string */
    public const REQUIRED_IF = 'required_if';

    /** @var string */
    public const REQUIRED_WITHOUT = 'required_without';

    /** @var string */
    public const REQUIRED = 'required';

    /** @var string */
    public const NULLABLE = 'nullable';

    /** @var string */
    public const SOMETIMES = 'sometimes';

    /** @var array */
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
        'datetime' => 'date_format',
    ];

    /**
     * Resolve a type definition to a fluent rule.
     *
     * @return array
     */
    public static function get($definition, \Closure $predicate = null)
    {
        // Handle Foreign key definition rules
        if ($definition instanceof ForeignKeyConstraintDefinition) {
            return [self::exists($definition->getForeignTableName(), $definition->getForeignColumns())];
        }
        // Handle unique columns definitions
        if ($definition instanceof UniqueKeyConstraintDefinition) {
            return [self::unique($definition)];
        }
        // Handle unique rules
        if (!\is_string($definition)) {
            throw new \Exception('$definition parameter type must be a PHP string or instance of '.ForeignKeyConstraintDefinition::class);
        }

        // Here we handle string types
        return self::basic($definition, $predicate);
    }

    /**
     * Returns the unique validation rule.
     *
     * @return string
     */
    public static function unique(UniqueKeyConstraintDefinition $metadata, string $primaryKey = null, bool $updates = false)
    {
        $columns = $metadata->getColumns();

        return !$updates ? "expr:\$this->has('$primaryKey') ? '".sprintf(
            "unique:%s,%s,' . ",
            $metadata->getTable(),
            \is_array($columns) ? ($columns[0] ?? 'id') : ($columns ?? 'id')
        )."\$this->{$primaryKey} ".sprintf(
            ": 'unique:%s,%s",
            $metadata->getTable(),
            \is_array($columns) ? ($columns[0] ?? 'id') : ($columns ?? 'id')
        )."'" : "expr:'".sprintf(
            "unique:%s,%s,' . ",
            $metadata->getTable(),
            \is_array($columns) ? ($columns[0] ?? 'id') : ($columns ?? 'id')
        )."\$this->{$primaryKey} ";
    }

    /**
     * @param string|string[] $columns
     *
     * @return string
     */
    public static function exists(string $table, $columns)
    {
        return sprintf('exists:%s,%s', $table, \is_array($columns) ? ($columns[0] ?? 'id') : ($columns ?? 'id'));
    }

    /**
     * @param (\Closure|null)|null $predicate
     *
     * @return array
     */
    private static function basic(string $type, \Closure $predicate = null)
    {
        if (!Str::contains($type, ':')) {
            return array_filter([self::TYPE_MAPS[$type] ?? null], $predicate ?? static function ($item) {
                return null !== $item;
            });
        }
        [$part0, $part1] = [Str::before(':', $type), Str::after(':', $type)];
        [$rule1, $rule2] = [self::TYPE_MAPS[$part0] ?? null, null];
        if (\in_array($rule1, ['string', 'numeric', 'integer'], true)) {
            $rule2 = "max:{$part1}";
        }
        if (\in_array($rule1, ['date_format'], true)) {
            $format = $part1 ?? 'Y-m-d H:i:s';
            $rule1 = "$rule1:{$format}";
        }

        return array_filter([$rule1, $rule2], $predicate ?? static function ($item) {
            return null !== $item;
        });
    }
}
