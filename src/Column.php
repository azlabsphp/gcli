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

use Drewlabs\GCli\Contracts\ForeignKeyConstraintDefinition;
use Drewlabs\GCli\Contracts\HasExistConstraint;
use Drewlabs\GCli\Contracts\HasSizeProperty;
use Drewlabs\GCli\Contracts\HasUniqueConstraint;
use Drewlabs\GCli\Contracts\ORMColumnDefinition as ColumnDefinition;
use Drewlabs\GCli\Contracts\UniqueKeyConstraintDefinition;

/** @internal */
class Column implements ColumnDefinition, HasSizeProperty, HasExistConstraint, HasUniqueConstraint
{
    /** @var string|null */
    private $table;

    /** @var string */
    private $name;

    /** @var string */
    private $type;

    /** @var string|int|float|null */
    private $default;

    /** @var ForeignKeyConstraintDefinition|null */
    private $foreignKeyConstraint;

    /** @var UniqueKeyConstraintDefinition|null */
    private $uniqueKeyConstraint;

    /** @var bool */
    private $required;

    /**  @var bool */
    private $unsigned;

    /** @var string */
    private $rawType;

    /** @var int */
    private $size;

    /**
     * Creates class instance.
     *
     * @param bool                           $required
     * @param string|int|float               $default
     * @param bool                           $unsigned
     * @param ForeignKeyConstraintDefinition $foreignKeyConstraint
     * @param UniqueKeyConstraintDefinition  $uniqueKeyConstraint
     */
    public function __construct(
        string $name,
        string $type,
        string $table = null,
        $required = false,
        $default = null,
        $unsigned = false,
        $foreignKeyConstraint = null,
        $uniqueKeyConstraint = null
    ) {
        $this->table = $table;
        $this->name = $name;
        $this->type = $type ?? 'string';
        $this->setRawType($this->type);
        $this->default = $default;
        $this->foreignKeyConstraint = $foreignKeyConstraint;
        $this->uniqueKeyConstraint = $uniqueKeyConstraint;
        $this->required = $required;
        $this->unsigned = $unsigned;
    }

    public function hasSize(): bool
    {
        return !is_null($this->size);
    }

    public function getSize(): int
    {
        return intval($this->size);
    }

    public function hasExistContraint(): bool
    {
        return !is_null($this->foreignConstraint());
    }

    public function hasUniqueConstraint(): bool
    {
        return !is_null($this->unique());
    }

    public function name(): string
    {
        return $this->name ?? 'column';
    }

    public function type(): string
    {
        return $this->type;
    }

    public function getRawType(): string
    {
        return $this->rawType;
    }

    public function setForeignKey(ForeignKeyConstraintDefinition $value)
    {
        $self = clone $this;
        $self->foreignKeyConstraint = $value;

        return $self;
    }

    public function withSize(int $value)
    {
        $self = clone $this;
        $self->size = $value;
        return $self;
    }

    public function setUnique(UniqueKeyConstraintDefinition $value)
    {
        $self = clone $this;
        $self->uniqueKeyConstraint = $value;
        return $self;
    }

    public function unique()
    {
        return $this->uniqueKeyConstraint;
    }

    public function required(): bool
    {
        return (bool) $this->required ?? false;
    }

    public function unsigned()
    {
        return (bool) $this->unsigned ?? false;
    }

    public function foreignConstraint()
    {
        return $this->foreignKeyConstraint;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function hasDefault()
    {
        return null !== $this->default;
    }

    public function jsonSerialize()
    {
        $result = [
            'table' => $this->table,
            'name' => $this->name,
            'type' => $this->type,
            'default' => $this->default,
            'foreignKeyConstraint' => $this->foreignKeyConstraint,
            'uniqueKeyConstraint' => $this->uniqueKeyConstraint,
            'required' => $this->required,
            'unsigned' => $this->unsigned,
        ];

        return array_filter($result, static function ($value) {
            return null !== $value;
        });
    }

    private function setRawType(string $type)
    {
        $this->rawType = false !== ($pos = mb_strpos($type = $this->type(), ':')) ? mb_substr($type, 0, $pos) : $type;
    }
}
