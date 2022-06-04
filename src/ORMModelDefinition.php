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

namespace Drewlabs\ComponentGenerators;

use Drewlabs\ComponentGenerators\Builders\DtoAttributesFactory;
use Drewlabs\ComponentGenerators\Builders\ViewModelRulesFactory;
use Drewlabs\ComponentGenerators\Contracts\ORMColumnDefinition;
use Drewlabs\ComponentGenerators\Contracts\ORMModelDefinition as ContractsORMModelDefinition;
use Drewlabs\ComponentGenerators\Helpers\DataTypeToFluentValidationRulesHelper;
use Drewlabs\PHPValue\Value;

/**
 * 
 * @package Drewlabs\ComponentGenerators
 */
class ORMModelDefinition extends Value implements ContractsORMModelDefinition, DtoAttributesFactory, ViewModelRulesFactory
{
    protected $__PROPERTIES__ = [
        'primaryKey_' => 'primaryKey',
        'name_' => 'name',
        'table_' => 'table',
        'columns_' => 'columns',
        'increments_' => 'increments',
        'namespace_' => 'namespace',
        'comment_' => 'comment',
    ];

    public function setColumns_Attribute(?array $value)
    {
        foreach ($value as $value) {
            if (!($value instanceof ORMColumnDefinition)) {
                throw new \InvalidArgumentException('$columns parameter must be a list of ' . ORMColumnDefinition::class . ' items');
            }
        }

        return $value;
    }

    public function primaryKey(): ?string
    {
        return $this->primaryKey_ ?? 'id';
    }

    public function name(): ?string
    {
        return $this->name_;
    }

    public function comment(): ?string
    {
        return $this->comment_;
    }

    public function table(): ?string
    {
        return $this->table_;
    }

    public function columns(): array
    {
        return $this->columns_ ?? [];
    }

    public function shouldAutoIncrements(): bool
    {
        return $this->increments_ ?? true;
    }

    public function namespace(): ?string
    {
        return $this->namespace_ ?? '\\App\\Models';
    }

    public function createDtoAttributes()
    {
        return iterator_to_array((function (ContractsORMModelDefinition $model) {
            /**
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

    private function getColumRules(ORMColumnDefinition $column, ?string $primaryKey = null, $useUpdateRules = false)
    {
        $evaluateIfPrimaryKeyFunc = static function ($key) use ($column) {
            if ($column->name() === $key) {
                return DataTypeToFluentValidationRulesHelper::SOMETIMES;
            }
            return null !== $key ?
                sprintf(
                    '%s:%s',
                    DataTypeToFluentValidationRulesHelper::REQUIRED_WITHOUT,
                    $key
                ) : DataTypeToFluentValidationRulesHelper::REQUIRED;
        };
        $rules[] = $column->required() ?
            ($useUpdateRules ? DataTypeToFluentValidationRulesHelper::SOMETIMES :
                $evaluateIfPrimaryKeyFunc($primaryKey)) :
            DataTypeToFluentValidationRulesHelper::NULLABLE;
        $rules = [...$rules, ...(DataTypeToFluentValidationRulesHelper::getRule($column->type()))];
        if ($constraints = $column->foreignConstraint()) {
            $rules = [...$rules, ...(DataTypeToFluentValidationRulesHelper::getRule($constraints))];
        }
        if (($constraints = $column->unique()) && !($useUpdateRules)) {
            $rules = [...$rules, ...(DataTypeToFluentValidationRulesHelper::getRule($constraints))];
        }

        return array_merge($rules);
    }
}
