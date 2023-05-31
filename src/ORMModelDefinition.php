<?php

declare(strict_types=1);

/*
 * This file is part of the Drewlabs package.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\GCli;

use Drewlabs\GCli\Builders\DtoAttributesFactory;
use Drewlabs\GCli\Builders\ViewModelRulesFactory;
use Drewlabs\GCli\Contracts\ORMColumnDefinition;
use Drewlabs\GCli\Contracts\ORMModelDefinition as ContractsORMModelDefinition;
use Drewlabs\GCli\Helpers\DataTypeToFluentValidationRulesHelper;

class ORMModelDefinition implements ContractsORMModelDefinition, DtoAttributesFactory, ViewModelRulesFactory
{
    private $primaryKey;
    private $name;
    private $table;
    private $columns;
    private $increments;
    private $namespace;
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
        return iterator_to_array((static function (ContractsORMModelDefinition $model) {
            /*
             * @var ORMColumnDefinition
             */
            foreach ($model->columns() as $column) {
                yield $column->name() => $column->type();
            }
        })($this));
    }

    public function createRules(bool $update = false)
    {
        return iterator_to_array((function (ContractsORMModelDefinition $model) use ($update) {
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
    private function getColumRules(ORMColumnDefinition $column, ?string $primaryKey = null, $updates = false)
    {
        $rules[] = !$column->required() ? DataTypeToFluentValidationRulesHelper::NULLABLE : ($column->required() && $column->hasDefault() ?
                DataTypeToFluentValidationRulesHelper::NULLABLE : ($updates ? DataTypeToFluentValidationRulesHelper::SOMETIMES :
                    $this->createColumnRule($column, $primaryKey)));

        if ($column->name() === $primaryKey) {
            $rules[] = DataTypeToFluentValidationRulesHelper::getExistsRule($this->table(), $primaryKey);
        }
        $columnRules = DataTypeToFluentValidationRulesHelper::getRule($column->type());
        $rules = [...$rules, ...$columnRules];
        if ($constraints = $column->foreignConstraint()) {
            $rules = [...$rules, ...DataTypeToFluentValidationRulesHelper::getRule($constraints)];
        }
        if (($constraints = $column->unique()) && ($column->name() !== $primaryKey)) {
            $rules = [...$rules, DataTypeToFluentValidationRulesHelper::getUniqueRule($constraints, $primaryKey, $updates)];
        }

        return array_merge($rules);
    }

    private function createColumnRule(ORMColumnDefinition $column, ?string $key = null)
    {
        if ($column->name() === $key) {
            return DataTypeToFluentValidationRulesHelper::SOMETIMES;
        }

        return null !== $key ?
            sprintf(
                '%s:%s',
                DataTypeToFluentValidationRulesHelper::REQUIRED_WITHOUT,
                $key
            ) : DataTypeToFluentValidationRulesHelper::REQUIRED;
    }
}
