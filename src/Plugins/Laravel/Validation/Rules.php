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

namespace Drewlabs\GCli\Plugins\Laravel\Validation;

use Drewlabs\Core\Helpers\Str;
use Drewlabs\GCli\Contracts\ForeignKeyConstraintDefinition;
use Drewlabs\GCli\Contracts\UniqueKeyConstraintDefinition;

final class Rules
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
     * @param mixed $definition
     * 
     * @return array
     */
    public static function get($definition, ?\Closure $predicate = null)
    {
        if ($definition instanceof ForeignKeyConstraintDefinition) {
            return [static::exists($definition->getForeignTableName(), $definition->getForeignColumns())];
        }

        if ($definition instanceof UniqueKeyConstraintDefinition) {
            return [static::unique($definition)];
        }

        if (!\is_string($definition)) {
            throw new \Exception('$definition parameter type must be a PHP string or instance of '.ForeignKeyConstraintDefinition::class);
        }


        return static::basic($definition, $predicate);
    }

    /**
     * Returns the unique validation rule.
     *
     * @return string
     */
    public static function unique(UniqueKeyConstraintDefinition $metadata, ?string $primaryKey = null, bool $updates = false)
    {
        $columns = $metadata->getColumns();
        // @phpstan-ignore nullCoalesce.variable
        $key = \is_array($columns) ? ($columns[0] ?? 'id') : ($columns ?? 'id');

        return !$updates ? "expr:\$this->has('$primaryKey') ? '".sprintf(
            "unique:%s,%s,' . ",
            $metadata->getTable(),
            $key
        )."\$this->{$primaryKey} ".sprintf(
            ": 'unique:%s,%s",
            $metadata->getTable(),
            $key
        )."'" : "expr:'".sprintf(
            "unique:%s,%s,' . ",
            $metadata->getTable(),
            $key
        )."\$this->{$primaryKey} ";
    }

    /**
     * @param string|string[] $columns
     *
     * @return string
     */
    public static function exists(string $table, $columns)
    {
        // @phpstan-ignore nullCoalesce.variable
        $key = \is_array($columns) ? ($columns[0] ?? 'id') : ($columns ?? 'id');
        return sprintf('exists:%s,%s', $table, $key);
    }

    /**
     * @param (\Closure|null)|null $predicate
     *
     * @return array
     */
    private static function basic(string $type, ?\Closure $predicate = null)
    {
        if (!Str::contains($type, ':')) {
            return array_filter([static::TYPE_MAPS[$type] ?? null], $predicate ?? static function ($item) {
                return null !== $item;
            });
        }

        [$part0, $part1] = [Str::before(':', $type), Str::after(':', $type)];
        [$rule1, $rule2] = [static::TYPE_MAPS[$part0] ?? null, null];
        if (\in_array($rule1, ['string', 'numeric', 'integer'], true)) {
            $rule2 = "max:{$part1}";
        }
        if (\in_array($rule1, ['date_format'], true)) {
            // @phpstan-ignore nullCoalesce.variable
            $format = $part1 ?? 'Y-m-d H:i:s';
            $rule1 = "$rule1:{$format}";
        }

        return array_filter([$rule1, $rule2], $predicate ?? static function ($item) {
            return null !== $item;
        });
    }
}
