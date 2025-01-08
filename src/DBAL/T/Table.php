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

namespace Drewlabs\GCli\DBAL\T;

use Drewlabs\GCli\Contracts\ORMColumnDefinition;
use Drewlabs\GCli\Contracts\ORMModelDefinition as ModelDefinition;
use Drewlabs\GCli\Contracts\HasModuleMetadata;
use Drewlabs\GCli\Contracts\HasRelations;
use Drewlabs\GCli\Contracts\Relation;

/** @internal */
class Table implements
    ModelDefinition,
    HasModuleMetadata,
    HasRelations
{
    /** @var string */
    private $primaryKey;

    /** @var string */
    private $name;

    /** @var string */
    private $table;

    /** @var ORMColumnDefinition[] */
    private $columns;

    /** @var bool */
    private $increments;

    /** @var string */
    private $namespace;

    /** @var string */
    private $comment;

    /** @var string */
    private $module;

    /** @var Relation[] */
    private $relations;

    /**
     * Creates class instance.
     *
     * @param string                $primaryKey
     * @param ORMColumnDefinition[] $columns
     */
    public function __construct(
        $primaryKey,
        string $name,
        string $table,
        array $columns,
        bool $increments,
        ?string $namespace = null,
        ?string $module = null,
        ?string $comment = null,
        array $relations = []
    ) {
        $this->primaryKey = $primaryKey;
        $this->name = $name;
        $this->table = $table;
        $this->columns = $columns;
        $this->increments = $increments;
        $this->namespace = $namespace;
        $this->comment = $comment;
        $this->module = $module;
        $this->relations = $relations ?? [];
    }

    public function withModuleName(string $name)
    {
        $self = clone $this;
        $self->module = $name;
        return $self;
    }

    public function getModuleName(): ?string
    {
        return $this->module ?? $this->table();
    }

    public function withRelations(array $relations = [])
    {
        $self = clone $this;
        $self->relations = $relations;
        return $self;
    }

    public function getRelations(): array
    {
        return $this->relations;
    }

    public function getProperties(): array
    {
        return $this->columns ?? [];
    }

    public function primaryKey(): ?string
    {
        return $this->primaryKey ?? 'id';
    }

    public function name(): string
    {
        return $this->name;
    }

    public function comment(): ?string
    {
        return $this->comment;
    }

    public function table(): string
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
}
