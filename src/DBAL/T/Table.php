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
use Drewlabs\GCli\Contracts\ProvidesModuleMetadata;

/** @internal */
class Table implements ModelDefinition, ProvidesModuleMetadata
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
        string $namespace = null,
        string $module = null,
        string $comment = null
    ) {
        $this->primaryKey = $primaryKey;
        $this->name = $name;
        $this->table = $table;
        $this->columns = $columns;
        $this->increments = $increments;
        $this->namespace = $namespace;
        $this->comment = $comment;
        $this->module = $module;
    }

    public function getModuleName(): string
    {
        return $this->module ?? $this->table();
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
