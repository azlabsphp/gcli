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

use function Drewlabs\CodeGenerator\Proxy\PHPClass;
use function Drewlabs\CodeGenerator\Proxy\PHPClassMethod;
use function Drewlabs\CodeGenerator\Proxy\PHPClassProperty;
use function Drewlabs\CodeGenerator\Proxy\PHPVariable;
use Drewlabs\CodeGenerator\Types\PHPTypesModifiers;

use Drewlabs\ComponentGenerators\Contracts\ComponentBuilder;
use Drewlabs\ComponentGenerators\Contracts\EloquentORMModelBuilder as ContractsEloquentORMModel;
use Drewlabs\ComponentGenerators\Contracts\ORMColumnDefinition;
use Drewlabs\ComponentGenerators\Contracts\ORMModelDefinition;
use Drewlabs\ComponentGenerators\Helpers\ComponentBuilderHelpers;
use function Drewlabs\ComponentGenerators\Proxy\PHPScript;
use Drewlabs\ComponentGenerators\Traits\HasNamespaceAttribute;
use Drewlabs\ComponentGenerators\Traits\ViewModelBuilder;
use Drewlabs\Core\Helpers\Str;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Pluralizer;

class EloquentORMModelBuilder implements ContractsEloquentORMModel, ComponentBuilder
{
    use HasNamespaceAttribute;
    use ViewModelBuilder;

    /**
     * The name of the model.
     *
     * @var string
     */
    public const DEFAULT_NAME = 'Test';

    /**
     * The namespace of the model.
     *
     * @var string
     */
    public const DEFAULT_NAMESPACE = 'App\\Models';

    /**
     * @var string
     */
    private const DEFAULT_PATH = 'Models';

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
     * @var ORMColumnDefinition[]
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

    /**
     * Specify that the model act like a view model.
     *
     * @var false
     */
    private $isViewModel_ = false;

    /**
     * @var ORMModelDefinition
     */
    private $defintion;

    public function __construct(
        ORMModelDefinition $defintion,
        ?string $schema = null,
        ?string $path = null
    ) {
        $this->setDefinition($defintion);
        [$table, $name] = [$defintion->table(), $defintion->name()];
        $this->setComponentBaseDefintions($schema, $table, $name);
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

    /**
     * Creates the model as a view model.
     *
     * @return self
     */
    public function asViewModel()
    {
        $this->isViewModel_ = true;

        return $this->addInputsTraits();
    }

    public function build()
    {
        $component = (PHPClass($this->name_));
        if ($this->isViewModel_) {
            /**
             * @var BluePrint
             */
            $component = $component->addMethod(PHPClassMethod(
                'rules',
                [],
                'array<string,string|string[]>',
                PHPTypesModifiers::PUBLIC,
                'Returns a fluent validation rules'
            ))->addMethod(
                (PHPClassMethod(
                    'messages',
                    [],
                    'array<string,string|string[]>',
                    PHPTypesModifiers::PUBLIC,
                    'Returns a fluent validation rules'
                ))->addContents(
                    'return '.PHPVariable('rules', null, $this->rules_ ?? [])->asRValue()->__toString()
                )
            );
            if (!$this->isSingleActionValidator_) {
                /**
                 * @var BluePrint|PHPClass
                 */
                $component = $component
                    ->addImplementation(\Drewlabs\Contracts\Validator\Validatable::class)
                    ->addMethod(
                        PHPClassMethod(
                            'updateRules',
                            [],
                            'array<string,string|string[]>',
                            PHPTypesModifiers::PUBLIC,
                            'Returns a fluent validation rules applied during update actions'
                        )->addContents('return '.PHPVariable('rules', null, $this->rules_ ?? [])->asRValue()->__toString())
                    );
            } else {
                /**
                 * @var Blueprint
                 */
                $component = $component
                    ->addImplementation(\Drewlabs\Contracts\Validator\CoreValidatable::class);
            }
        }

        // Add inputs traits
        if ($this->hasInputsTraits_) {
            /**
             * @var BluePrint
             */
            $component = $component->addTrait(\Drewlabs\Core\Validator\Traits\ViewModel::class);
        }
        $component->setBaseClass(EloquentModel::class)
            ->asFinal()
            ->addTrait(\Drewlabs\Packages\Database\Traits\Model::class)
            // Model associated table
            ->addProperty(
                PHPClassProperty(
                    'table',
                    'string',
                    PHPTypesModifiers::PROTECTED,
                    $this->table_ ?? 'examples',
                    'Model referenced table'
                )
            )
            // Model hidden attributes
            ->addProperty(
                PHPClassProperty(
                    'hidden',
                    'array',
                    PHPTypesModifiers::PROTECTED,
                    $this->hidden_ ?? [],
                    'List of values that must be hidden when generating the json output'
                )
            )
            // Model appendable attributes
            ->addProperty(
                PHPClassProperty(
                    'appends',
                    'array',
                    PHPTypesModifiers::PROTECTED,
                    $this->appends_ ?? [],
                    'List of attributes that will be appended to the json output of the model'
                )
            )
            // Models fillable Columns
            ->addProperty(
                PHPClassProperty(
                    'fillable',
                    'array',
                    PHPTypesModifiers::PROTECTED,
                    $this->columns_ ? array_map(static function (ORMColumnDefinition $column) {
                        return $column->name();
                    }, $this->columns_) : [],
                    'List of fillable properties of the current model'
                )
            )
            ->addProperty(
                PHPClassProperty(
                    'relation_methods',
                    'array',
                    PHPTypesModifiers::PUBLIC,
                    $this->relationMethods_ ?? [],
                    'List of fillable properties of the current model'
                )
            )->addMethod(
                (PHPClassMethod(
                    'boot',
                    [],
                    'void',
                    PHPTypesModifiers::PROTECTED,
                    'Bootstrap the model and its traits.'
                ))->asStatic(true)->addLine('parent::boot()')
            )
            ->addToNamespace($this->namespace_ ?? self::DEFAULT_NAMESPACE);

        if (null !== $this->primaryKey_) {
            $component = $component->addProperty(
                PHPClassProperty(
                    'primaryKey',
                    'string',
                    PHPTypesModifiers::PROTECTED,
                    $this->primaryKey_ ?? 'id',
                    'Table primary key'
                )
            );
        }

        if ((null !== $this->autoIncrements_) && !$this->autoIncrements_) {
            $component = $component->addProperty(
                PHPClassProperty(
                    'incrementing',
                    'bool',
                    PHPTypesModifiers::PUBLIC,
                    false,
                    'Indicates whether the primary key of the model is incrementable'
                )
            );
        }

        if (!$this->hasTimestamps_) {
            $component = $component->addProperty(
                PHPClassProperty(
                    'timestamps',
                    'bool',
                    PHPTypesModifiers::PUBLIC,
                    false,
                    'Indicates whether table has updated_at and created_at columns '
                )
            );
        }

        // Add implementations
        /**
         * @var BluePrint
         */
        $component = array_reduce([
            \Drewlabs\Packages\Database\Contracts\ORMModel::class,
        ], static function (Blueprint $carry, $curr) {
            $carry = $carry->addImplementation($curr);

            return $carry;
        }, $component);

        // Returns the builded component
        return PHPScript(
            $component->getName(),
            $component,
            ComponentBuilderHelpers::rebuildComponentPath(
                $this->namespace_ ?? self::DEFAULT_NAMESPACE,
                $this->path_ ?? self::DEFAULT_PATH
            ),
        )->setNamespace($component->getNamespace());
    }

    public static function defaultClassPath(?string $classname = null)
    {
        $classname = $classname ?? 'Test';
        if (Str::contains($classname, '\\')) {
            return $classname;
        }

        return sprintf('%s%s%s', self::DEFAULT_NAMESPACE, '\\', $classname);
    }

    /**
     * @return void
     */
    public function setDefinition(ORMModelDefinition $value)
    {
        $this->defintion = $value;
    }

    /**
     * @return ORMModelDefinition
     */
    public function getDefinition()
    {
        return $this->defintion;
    }

    private function setComponentBaseDefintions($schema, $table, $name)
    {
        $table = (null === $table) ? (null !== $name ? Str::snakeCase(Pluralizer::plural($name)) : null) : $table;
        // Set the table name
        if ($table) {
            $this->setTableName($table);
        }
        // Set model name
        $name_ = $table ?? $name ?? null;
        // TODO : REMOVE SCHEMA PREFIX IF ANY
        if ($name_ && $schema) {
            $name_ = Str::startsWith($name_, $schema.'_') ?
                Str::replace($schema.'_', '', $name_) : (Str::startsWith($name_, $schema) ?
                    Str::replace($schema, '', $name_) :
                    $name_);
        }
        $name = $name_ ? Str::camelize(Pluralizer::singular($name_)) : self::DEFAULT_NAME;
        if ($name) {
            $this->setName($name);
        }
    }
}
