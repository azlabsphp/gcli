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

namespace Drewlabs\ComponentGenerators\Builders;

use Drewlabs\CodeGenerator\Contracts\Blueprint;
use Drewlabs\CodeGenerator\Models\PHPClass;
use Drewlabs\CodeGenerator\Models\PHPClassMethod;
use Drewlabs\CodeGenerator\Models\PHPClassProperty;
use Drewlabs\CodeGenerator\Types\PHPTypesModifiers;
use Drewlabs\ComponentGenerators\Contracts\ComponentBuilder;
use Drewlabs\ComponentGenerators\Contracts\EloquentORMModelBuilder as ContractsEloquentORMModel;
use Drewlabs\ComponentGenerators\Contracts\ORMModelColumnDefintion;
use Drewlabs\ComponentGenerators\Contracts\ORMModelDefinition;
use Drewlabs\ComponentGenerators\Exceptions\BuildErrorException;
use Drewlabs\ComponentGenerators\PHP\PHPScriptFile;
use Drewlabs\ComponentGenerators\Traits\HasNameAttribute;
use Drewlabs\ComponentGenerators\Traits\HasNamespaceAttribute;
use Drewlabs\ComponentGenerators\Traits\HasPathAttribute;
use Drewlabs\Contracts\Data\Model\ActiveModel;
use Drewlabs\Contracts\Data\Model\GuardedModel;
use Drewlabs\Contracts\Data\Model\Parseable;
use Drewlabs\Contracts\Data\Model\Relatable;
use Drewlabs\Packages\Database\Traits\Model;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Pluralizer;

class EloquentORMModelBuilder implements ContractsEloquentORMModel, ComponentBuilder
{
    use HasNamespaceAttribute;
    use HasPathAttribute;
    use HasNameAttribute;

    /**
     * @var string
     */
    private const DEFAULT_PATH = 'app/Models';

    /**
     * The namespace of the model.
     *
     * @var string
     */
    private const DEFAULT_NAMESPACE = 'App\\Models';

    /**
     * @var Stringable
     */
    private $component_;

    /**
     * List of appendable model properties.
     *
     * @var array
     */
    private $appends_ = [];

    /**
     * List of model properties that will not be added to serialized output.
     *
     * @var array
     */
    private $hidden_ = [];

    /**
     * Indicates whether the model must have a timestamp or Not.
     *
     * @var bool
     */
    private $hasTimestamps_ = true;

    /**
     * The table name binded to the model.
     *
     * @var string
     */
    private $table_;

    /**
     * List of table columns.
     *
     * @var ORMModelColumnDefintion[]
     */
    private $columns_ = [];

    /**
     * They primary key of the model/table.
     *
     * @var string
     */
    private $primaryKey_ = 'id';

    /**
     * Is the model primary key incrementable.
     *
     * @var true
     */
    private $autoIncrements_ = true;

    /**
     * List of eloquent relation methods.
     *
     * @var array
     */
    private $relationMethods_ = [];

    public function __construct(
        ORMModelDefinition $defintion,
        ?string $path = null
    ) {
        [$table, $name] = [$defintion->table(), $defintion->name()];
        $table = (null === $table) ? (null !== $name ?
            drewlabs_core_strings_as_snake_case(Pluralizer::plural($name)) : null) : $table;
        // Set the table name
        if ($table) {
            $this->setTableName($table);
        }
        // Set the table name
        $name = (null === $name) ? drewlabs_core_strings_as_camel_case(Pluralizer::singular($table)) : $name;
        if ($name) {
            $this->setName($name);
        }
        // Set the primary key
        if ($defintion->primaryKey()) {
            $this->setKeyName($defintion->primaryKey() ?? 'id');
        }
        // Set the list of columns
        if ($defintion->columns()) {
            $this->setColumns($defintion->columns() ?? []);
        }

        // Set the AUTO-INCREMENTS property
        if (!$defintion->shouldAutoIncrements()) {
            $this->doesNotAutoIncrements();
        }

        // Set the model namespace
        if ($defintion->namespace()) {
            $this->setNamespace($defintion->namespace());
        }

        // Set the model path
        $this->setWritePath($path ?? self::DEFAULT_PATH);
    }

    public function __toString(): string
    {
        if (null === $this->component_) {
            throw new BuildErrorException(__CLASS__);
        }

        return $this->component_->__toString();
    }

    public function setRelationMethods(array $names)
    {
        $this->relationMethods_ = $names;

        return $this;
    }

    public function setAppends(array $columns)
    {
        $this->appends_ = $columns;

        return $this;
    }

    public function setHiddenColumns(array $columns)
    {
        $this->hidden_ = $columns;

        return $this;
    }

    public function hasTimestamps(bool $value)
    {
        $this->hasTimestamps_ = $value;

        return $this;
    }

    public function setTableName(string $table)
    {
        $this->table_ = $table;

        return $this;
    }

    public function setColumns(array $columns = [])
    {
        $this->columns_ = $columns;

        return $this;
    }

    public function setKeyName(string $name)
    {
        $this->primaryKey_ = $name;

        return $this;
    }

    public function doesNotAutoIncrements()
    {
        $this->autoIncrements_ = false;

        return $this;
    }

    public function build()
    {
        /**
         * @var BluePrint
         */
        $model = (new PHPClass($this->name_))
            ->setBaseClass(EloquentModel::class)
            ->asFinal()
            ->addTrait(Model::class)
            // Model associated table
            ->addProperty(
                new PHPClassProperty(
                    'table',
                    'string',
                    PHPTypesModifiers::PROTECTED,
                    $this->table_ ?? 'examples',
                    'Model referenced table'
                )
            )
            // Model table primary key
            ->addProperty(
                new PHPClassProperty(
                    'primaryKey',
                    'string',
                    PHPTypesModifiers::PROTECTED,
                    $this->primaryKey_ ?? 'id',
                    'Table primary key'
                )
            )
            // Model hidden attributes
            ->addProperty(
                new PHPClassProperty(
                    'hidden',
                    'array',
                    PHPTypesModifiers::PROTECTED,
                    $this->hidden_ ?? [],
                    'List of values that must be hidden when generating the json output'
                )
            )
            // Model appendable attributes
            ->addProperty(
                new PHPClassProperty(
                    'appends',
                    'array',
                    PHPTypesModifiers::PROTECTED,
                    $this->appends_ ?? [],
                    'List of attributes that will be appended to the json output of the model'
                )
            )
            // Models fillable Columns
            ->addProperty(
                new PHPClassProperty(
                    'fillable',
                    'array',
                    PHPTypesModifiers::PROTECTED,
                    $this->columns_ ? array_map(static function (ORMModelColumnDefintion $column) {
                        return $column->name();
                    }, $this->columns_) : [],
                    'List of fillable properties of the current model'
                )
            )
            ->addProperty(
                new PHPClassProperty(
                    'relation_methods',
                    'array',
                    PHPTypesModifiers::PUBLIC,
                    $this->relationMethods_ ?? [],
                    'List of fillable properties of the current model'
                )
            )->addMethod(
                (new PHPClassMethod(
                    'boot',
                    [],
                    'void',
                    PHPTypesModifiers::PROTECTED,
                    'Bootstrap the model and its traits.'
                ))->asStatic(true)->addLine('parent::boot()')
            )
            ->addToNamespace($this->namespace_ ?? self::DEFAULT_NAMESPACE);

        if (!$this->autoIncrements_) {
            $model = $model->addProperty(
                new PHPClassProperty(
                    'incrementing',
                    'bool',
                    PHPTypesModifiers::PUBLIC,
                    false,
                    'Indicates whether the primary key of the model is incrementable'
                )
            );
        }

        if (!$this->hasTimestamps_) {
            $model = $model->addProperty(
                new PHPClassProperty(
                    'timestamps',
                    'bool',
                    PHPTypesModifiers::PUBLIC,
                    false,
                    'Indicates whether table has updated_at and created_at columns '
                )
            );
        }

        // Add implementations
        $this->component_ = array_reduce([
            ActiveModel::class,
            Parseable::class,
            Relatable::class,
            GuardedModel::class,
        ], static function (Blueprint $carry, $curr) {
            $carry = $carry->addImplementation($curr);

            return $carry;
        }, $model);

        // Returns the builded component
        return new PHPScriptFile(
            $this->component_->getName(),
            $this->component_,
            $this->path_ ?? self::DEFAULT_PATH
        );
    }
}
