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

namespace Drewlabs\GCli;

use Drewlabs\GCli\Contracts\DtoAttributesFactory;
use Drewlabs\GCli\Contracts\ORMColumnDefinition;
use Drewlabs\GCli\Contracts\ORMModelDefinition as ModelDefinition;
use Drewlabs\GCli\Contracts\ViewModelRulesFactory;
use Drewlabs\GCli\Helpers\FluentRules;

/** @internal */
class ORMModel implements ModelDefinition, DtoAttributesFactory, ViewModelRulesFactory
{
    /**  @var string */
    private $primaryKey;

    /**  @var string */
    private $name;

    /**  @var string */
    private $table;

    /**  @var ORMColumnDefinition[] */
    private $columns;

    /**  @var bool */
    private $increments;

    /**  @var string */
    private $namespace;

    /**  @var string */
    private $comment;

    /**
     * Creates class instance.
     *
     * @param string                $primaryKey
     * @param string                $name
     * @param string                $table
     * @param ORMColumnDefinition[] $columns
     * @param bool                  $increments
     * @param string                $namespace
     * @param string                $comment
     */
    public function __construct(
        $primaryKey,
        $name,
        $table,
        $columns,
        $increments,
        $namespace,
        $comment
    ) {
        $this->primaryKey = $primaryKey;
        $this->name = $name;
        $this->table = $table;
        $this->columns = $columns;
        $this->increments = $increments;
        $this->namespace = $namespace;
        $this->comment = $comment;
    }

    public function getProperties(): array
    {
        return $this->columns ?? [];
    }

    public function primaryKey(): ?string
    {
        return $this->primaryKey ?? 'id';
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function comment(): ?string
    {
        return $this->comment;
    }

    public function table(): ?string
    {
        return $this->table;
    }

    public function columns(): array
    {
        return $this->columns ?? [];
    }

    public function shouldAutoIncrements(): bool
    {
        return $this->increments ?? true;
    }

    public function namespace(): ?string
    {
        return $this->namespace ?? '\\App\\Models';
    }

    public function createDtoAttributes()
    {
        return iterator_to_array((static function (ModelDefinition $model) {
            foreach ($model->columns() as $column) {
                yield $column->name() => $column->type();
            }
        })($this));
    }

    public function createRules(bool $update = false)
    {
        return iterator_to_array((function (ModelDefinition $model) use ($update) {
            foreach ($model->columns() as $value) {
                yield $value->name() => $this->getColumRules($value, $model->primaryKey(), $update);
            }
        })($this));
    }

    /**
     * @param (string|null)|null $primaryKey
     * @param bool               $updates
     *
     * @throws \Exception
     *
     * @return array
     */
    private function getColumRules(ORMColumnDefinition $column, string $primaryKey = null, $updates = false)
    {
        $rules[] = !$column->required() ? FluentRules::NULLABLE : ($column->required() && $column->hasDefault() ?
            FluentRules::NULLABLE : ($updates ? FluentRules::SOMETIMES :
                $this->createColumnRule($column, $primaryKey)));

        if ($column->name() === $primaryKey && $updates) {
            $rules[] = FluentRules::getExistsRule($this->table(), $primaryKey);
        }
        $columnRules = FluentRules::getRule($column->type());
        $rules = [...$rules, ...$columnRules];
        if ($constraints = $column->foreignConstraint()) {
            $rules = [...$rules, ...FluentRules::getRule($constraints)];
        }
        if (($constraints = $column->unique()) && ($column->name() !== $primaryKey)) {
            $rules = [...$rules, FluentRules::getUniqueRule($constraints, $primaryKey, $updates)];
        }

        return array_merge($rules);
    }

    private function createColumnRule(ORMColumnDefinition $column, string $key = null)
    {
        if ($column->name() === $key) {
            return FluentRules::SOMETIMES;
        }

        return null !== $key ?
            sprintf(
                '%s:%s',
                FluentRules::REQUIRED_WITHOUT,
                $key
            ) : FluentRules::REQUIRED;
    }
}
