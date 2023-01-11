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
use Drewlabs\CodeGenerator\Contracts\CallableInterface;

use function Drewlabs\CodeGenerator\Proxy\PHPClass;
use function Drewlabs\CodeGenerator\Proxy\PHPClassMethod;
use function Drewlabs\CodeGenerator\Proxy\PHPClassProperty;
use function Drewlabs\CodeGenerator\Proxy\PHPVariable;
use Drewlabs\CodeGenerator\Types\PHPTypesModifiers;
use Drewlabs\ComponentGenerators\BasicRelation;
use Drewlabs\ComponentGenerators\Contracts\ComponentBuilder;
use Drewlabs\ComponentGenerators\Contracts\EloquentORMModelBuilder as ContractsEloquentORMModel;
use Drewlabs\ComponentGenerators\Contracts\ORMColumnDefinition;
use Drewlabs\ComponentGenerators\Contracts\ORMModelDefinition;
use Drewlabs\ComponentGenerators\Contracts\ProvidesRelations;
use Drewlabs\ComponentGenerators\Helpers\ComponentBuilderHelpers;
use Drewlabs\ComponentGenerators\ThoughRelation;
use Drewlabs\ComponentGenerators\RelationTypes;

use function Drewlabs\ComponentGenerators\Proxy\PHPScript;
use Drewlabs\ComponentGenerators\Traits\HasNamespaceAttribute;
use Drewlabs\ComponentGenerators\Traits\ProvidesTrimTableSchema;
use Drewlabs\ComponentGenerators\Traits\ViewModelBuilder;
use Drewlabs\Core\Helpers\Str;
use Drewlabs\Filesystem\Exceptions\UnableToRetrieveMetadataException;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Pluralizer;
use InvalidArgumentException;
use RuntimeException;

class EloquentORMModelBuilder implements ContractsEloquentORMModel, ComponentBuilder, ProvidesRelations
{
    use HasNamespaceAttribute;
    use ViewModelBuilder;
    use ProvidesTrimTableSchema;

    /**
     * The name of the model.
     *
     * @var string
     */
    const DEFAULT_NAME = 'Test';

    /**
     * The namespace of the model.
     *
     * @var string
     */
    const DEFAULT_NAMESPACE = 'App\\Models';

    /**
     * @var string
     */
    const DEFAULT_PATH = 'Models';

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

    /**
     * List of model relations to provide
     * 
     * @var (\Drewlabs\ComponentGenerators\BasicRelation|\Drewlabs\ComponentGenerators\ThroughRelationTables)[]
     */
    private $relations;


    /**
     * Makes the table a pivot table
     * 
     * @var bool
     */
    private $aspivot;

    /**
     * Creates a model builder class instance
     * 
     * @param ORMModelDefinition $defintion 
     * @param (null|string)|null $schema 
     * @param (null|string)|null $path 
     * @return void 
     * @throws InvalidArgumentException 
     * @throws RuntimeException 
     * @throws UnableToRetrieveMetadataException 
     */
    public function __construct(
        ORMModelDefinition $defintion,
        ?string $schema = null,
        ?string $path = null
    ) {
        $this->setDefinition($defintion);
        [$table, $name] = [$defintion->table(), $defintion->name() ?? self::DEFAULT_NAME];
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

    public function provideRelations(array $relations = [])
    {
        $this->relations = $relations;
        return $this;
    }

    public function asPivot()
    {
        $this->aspivot = true;
        return $this;
    }

    public function build()
    {
        $component = (PHPClass($this->name()));
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
                    'return ' . PHPVariable('rules', null, $this->rules_ ?? [])->asRValue()->__toString()
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
                        )->addContents('return ' . PHPVariable('rules', null, $this->rules_ ?? [])->asRValue()->__toString())
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

        // If the generator is configured to provide model relation, the relation is generated
        $component = $this->addRelations($component);

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
                    'List of model relation methods'
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
            /**
             * @var Blueprint
             */
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
            $component = $component->addTrait(
                trait_exists(\Illuminate\Database\Eloquent\Concerns\HasUuids::class) ?
                    \Illuminate\Database\Eloquent\Concerns\HasUuids::class :
                    \Drewlabs\Packages\Database\Traits\HasUuids::class
            );
        }

        // Checks if the model is a pivot table model
        // and add the required laravel pivot trait to the model
        if ($this->aspivot) {
            $component = $component->addTrait(\Illuminate\Database\Eloquent\Relations\Concerns\AsPivot::class);
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

    /**
     * Set the base properties of the current component
     * 
     * @param mixed $schema 
     * @param mixed $table 
     * @param mixed $name 
     * @return void 
     * @throws InvalidArgumentException 
     */
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
            $name_ = self::trimschema($name_, $schema);
        }
        if ($name = Str::camelize(Pluralizer::singular($name_))) {
            $this->setName($name);
        }
    }

    /**
     * Add model releation methods
     * 
     * @param Blueprint $component
     * 
     * @return Blueprint 
     */
    private function addRelations(BluePrint $component)
    {
        if (empty($this->relations)) {
            return $component;
        }
        $this->relationMethods_ = $this->relationMethods_ ?? [];
        $haspivot = false;
        foreach ($this->relations as $relation) {
            $type = $relation->getType();
            if ($type === RelationTypes::ONE_TO_MANY || $type === RelationTypes::ONE_TO_ONE) {
                $component = $component->addMethod($this->createOneOrManyMethodTemplate($relation, $type));
                $this->relationMethods_[] = $relation->getName();
                continue;
            }
            if ($type === RelationTypes::MANY_TO_ONE) {
                $component = $component->addMethod($this->createBelongsToTemplate($relation));
                $this->relationMethods_[] = $relation->getName();
                continue;
            }
            if ($type === RelationTypes::MANY_TO_MANY && $relation instanceof \Drewlabs\ComponentGenerators\ThoughRelation) {
                /**
                 * @var BluePrint
                 */
                $component = $component->addMethod($this->createManyToManyRelationTemplate($relation));
                $this->relationMethods_[] = $relation->getName();
                continue;
            }
            if (
                in_array($type, [RelationTypes::ONE_TO_MANY_THROUGH, RelationTypes::ONE_TO_ONE_THROUGH]) &&
                $relation instanceof \Drewlabs\ComponentGenerators\ThoughRelation
            ) {
                /**
                 * @var BluePrint
                 */
                $component = $component->addMethod($this->createThroughRelationTemplate($relation));
                $this->relationMethods_[] = $relation->getName();
                continue;
            }
        }
        return $component;
    }


    /**
     * Creates a HasMany or HasOne method template
     * 
     * @param BasicRelation $relation 
     * @param string $type 
     * @return CallableInterface 
     */
    private function createOneOrManyMethodTemplate(\Drewlabs\ComponentGenerators\BasicRelation $relation, string $type)
    {
        $model = $relation->getModel();
        $local = $relation->getLocal();
        $reference = $relation->getReference();
        $returns = $type === RelationTypes::ONE_TO_MANY ?
            \Illuminate\Database\Eloquent\Relations\HasMany::class :
            \Illuminate\Database\Eloquent\Relations\HasOne::class;
        $method = $type === RelationTypes::ONE_TO_MANY ? 'hasMany' : 'hasOne';
        return  PHPClassMethod($relation->getName(), [], $returns, 'public')->addLine("return \$this->$method(\\$model::class, '$local', '$reference')");
    }

    /**
     * Creates a belongs to method template
     * 
     * @param BasicRelation $relation 
     * @return CallableInterface 
     */
    private function createBelongsToTemplate(\Drewlabs\ComponentGenerators\BasicRelation $relation)
    {
        $model = $relation->getModel();
        $local = $relation->getLocal();
        $reference = $relation->getReference();
        $returns = \Illuminate\Database\Eloquent\Relations\BelongsTo::class;
        return PHPClassMethod($relation->getName(), [], $returns, 'public')->addLine("return \$this->belongsTo(\\$model::class, '$local', '$reference')");
    }


    /**
     * Createa a one to one through or a one to many though relation method template for the model
     * 
     * @param ThoughRelation $relation 
     * @return CallableInterface 
     */
    private function createThroughRelationTemplate(\Drewlabs\ComponentGenerators\ThoughRelation $relation)
    {
        $returns = $relation->getType() === RelationTypes::ONE_TO_MANY_THROUGH ?
            \Illuminate\Database\Eloquent\Relations\HasManyThrough::class :
            \Illuminate\Database\Eloquent\Relations\HasOneThrough::class;
        $left = $relation->getLeftTable();
        $right = $relation->getRightTable();
        $leftforeignkey = $relation->getLeftForeignKey();
        $rightforeignkey = $relation->getRightForeignKey();
        $leftlocalkey = $relation->getLeftLocalKey();
        $rightlocalkey = $relation->getRightLocalKey();
        $line = $relation->getType() === RelationTypes::ONE_TO_MANY_THROUGH ?
            "return \$this->hasManyThrough(\\$left::class, \\$right::class, " :
            "return \$this->hasOneThrough(\\$left::class, \\$right::class, ";
        if ($leftforeignkey) {
            $line .= "'$leftforeignkey', ";
        }
        if ($rightforeignkey) {
            $line .= "'$rightforeignkey', ";
        }
        if ($leftlocalkey) {
            $line .= "'$leftlocalkey', ";
        }
        if ($rightlocalkey) {
            $line .= "'$rightlocalkey'";
        }
        $line .= ')';
        return PHPClassMethod($relation->getName(), [], $returns, 'public')->addLine($line);
    }

    /**
     * Creates a many to many relation method template
     * 
     * @param ThoughRelation $relation
     * 
     * @return CallableInterface 
     */
    private function createManyToManyRelationTemplate(\Drewlabs\ComponentGenerators\ThoughRelation $relation)
    {
        $returns = \Illuminate\Database\Eloquent\Relations\BelongsToMany::class;
        $left = $relation->getLeftTable();
        $right = $relation->getRightTable();
        $leftforeignkey = $relation->getLeftForeignKey();
        $rightforeignkey = $relation->getRightForeignKey();
        $leftlocalkey = $relation->getLeftLocalKey();
        $rightlocalkey = $relation->getRightLocalKey();
        $through = $relation->getThroughTable();
        $line = "return \$this->belongsToMany(\\$left::class, ";
        if ($right) {
            $line .= "'$right', ";
        }
        if ($leftforeignkey) {
            $line .= "'$leftforeignkey', ";
        }
        if ($rightforeignkey) {
            $line .= "'$rightforeignkey', ";
        }
        if ($leftlocalkey) {
            $line .= "'$leftlocalkey', ";
        }
        if ($rightlocalkey) {
            $line .= "'$rightlocalkey'";
        }
        $line .= ')';

        if ($through) {
            $line .= "->using(\\$through::class)";
        }
        return PHPClassMethod($relation->getName(), [], $returns, 'public')->addLine($line);
    }
}
