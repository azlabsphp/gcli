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

use Drewlabs\ComponentGenerators\Contracts\ORMColumnDefinition;
use Drewlabs\ComponentGenerators\Contracts\ORMModelDefinition as ContractsORMModelDefinition;
use Drewlabs\Support\Immutable\ValueObject;

class ORMModelDefinition extends ValueObject implements ContractsORMModelDefinition
{
    public function setColumns_Attribute(?array $value)
    {
        foreach ($value as $value) {
            if (!($value instanceof ORMColumnDefinition)) {
                throw new \InvalidArgumentException('$columns parameter must be a list of '.ORMColumnDefinition::class.' items');
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

    protected function getJsonableAttributes()
    {
        return [
            'primaryKey_' => 'primaryKey',
            'name_' => 'name',
            'table_' => 'table',
            'columns_' => 'columns',
            'increments_' => 'increments',
            'namespace_' => 'namespace',
            'comment_' => 'comment',
        ];
    }
}
