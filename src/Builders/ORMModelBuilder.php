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

namespace Drewlabs\GCli\Builders;

use Drewlabs\CodeGenerator\Contracts\Blueprint;
use Drewlabs\CodeGenerator\Contracts\CallableInterface;

use function Drewlabs\CodeGenerator\Proxy\PHPClass;
use function Drewlabs\CodeGenerator\Proxy\PHPClassMethod;
use function Drewlabs\CodeGenerator\Proxy\PHPClassProperty;
use function Drewlabs\CodeGenerator\Proxy\PHPFunctionParameter;
use function Drewlabs\CodeGenerator\Proxy\PHPVariable;

use Drewlabs\CodeGenerator\Types\PHPTypesModifiers;
use Drewlabs\Core\Helpers\Str;
use Drewlabs\GCli\Contracts\ComponentBuilder as AbstractBuilder;
use Drewlabs\GCli\Contracts\EloquentORMModelBuilder as AbstractORMModelBuilder;
use Drewlabs\GCli\Contracts\ORMColumnDefinition;
use Drewlabs\GCli\Contracts\ORMModelDefinition;
use Drewlabs\GCli\Contracts\ProvidesPropertyAccessors;
use Drewlabs\GCli\Contracts\ProvidesRelations;
use Drewlabs\GCli\Helpers\ComponentBuilder;

use function Drewlabs\GCli\Proxy\PHPScript;

use Drewlabs\GCli\RelationTypes;
use Drewlabs\GCli\Traits\HasNamespaceAttribute;
use Drewlabs\GCli\Traits\ProvidesTrimTableSchema;
use Drewlabs\GCli\Traits\ViewModelBuilder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Pluralizer;

class ORMModelBuilder implements AbstractORMModelBuilder, AbstractBuilder, ProvidesRelations, ProvidesPropertyAccessors
{
    use HasNamespaceAttribute;
    use ProvidesTrimTableSchema;
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
    public const DEFAULT_PATH = 'Models';

    /**
     * @var string[]
     */
    public const CLASS_PATHS = [
        'Drewlabs\\PHPValue\\Contracts\\Adaptable',
        'Drewlabs\\Query\\Contracts\\Queryable as AbstractQueryable',
        'Illuminate\\Database\\Eloquent\\Model',
        'Drewlabs\\Laravel\\Query\\Traits\\Queryable',
    ];

    /**
     * @var string[]
     */
    public const CLASS_TRAITS = [
        'Queryable',
    ];

    public const CLASS_IMPLEMENTATIONS = [
        'AbstractQueryable',
        'Adaptable',
    ];

    /**
     * List of appendable model properties.
     *
     * @var array
     */
    private $appends = [];

    /**
     * List of model properties that will not be added to serialized output.
     *
     * @var array
     */
    private $hidden = [];

    /**
     * Indicates whether the model must have a timestamp or Not.
     *
     * @var bool
     */
    private $hasTimestamps = true;

    /**
     * The table name binded to the model.
     *
     * @var string
     */
    private $table;

    /**
     * List of table columns.
     *
     * @var ORMColumnDefinition[]
     */
    private $columns = [];

    /**
     * They primary key of the model/table.
     *
     * @var string
     */
    private $primaryKey = 'id';

    /**
     * Is the model primary key incrementable.
     *
     * @var true
     */
    private $autoIncrements = true;

    /**
     * List of eloquent relation methods.
     *
     * @var array
     */
    private $relationMethods = [];

    /**
     * Specify that the model act like a view model.
     *
     * @var false
     */
    private $isViewModel = false;

    /**
     * @var ORMModelDefinition
     */
    private $defintion;

    /**
     * List of model relations to provide.
     *
     * @var (\Drewlabs\GCli\BasicRelation|\Drewlabs\GCli\ThroughRelationTables)[]
     */
    private $relations;

    /**
     * Makes the table a pivot table.
     *
     * @var bool
     */
    private $aspivot;

    /**
     * A growable list of reserved keywords for which setter
     * and getters should not be generated when generating methods
     * for columns.
     * 
     * @var string[]
     */
    const RESERVED_KEYWORDS = [
        'table',
        'key',
        'primary_key',
        'fillable',
        'fillables',
        'hidden',
        'casts',
        'guards',
        'attributes',
        'created_at',
        'updated_at',
        'timestamp',
        'timestamp',
        'time_stamps',
        'time_stamp'
    ];

    /**
     * @var false
     */
    private $provideAccessors = true;

    /**
     * Creates a model builder class instance.
     *
     * @param (string|null)|null $schema
     * @param (string|null)|null $path
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \Exception
     *
     * @return void
     */
    public function __construct(
        ORMModelDefinition $defintion,
        string $schema = null,
        string $path = null
    ) {
        $this->setDefinition($defintion);
        [$table, $name] = [$defintion->table(), $defintion->name() ?? self::DEFAULT_NAME];
        $this->setComponentBaseDefinitions($schema, $table, $name);
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
        $this->relationMethods = $names;

        return $this;
    }

    public function setAppends(array $columns)
    {
        $this->appends = $columns;

        return $this;
    }

    public function setHiddenColumns(array $columns)
    {
        $this->hidden = $columns;

        return $this;
    }

    public function hasTimestamps(bool $value)
    {
        $this->hasTimestamps = $value;

        return $this;
    }

    public function setTableName(string $table)
    {
        $this->table = $table;

        return $this;
    }

    public function setColumns(array $columns = [])
    {
        $this->columns = $columns;

        return $this;
    }

    public function setKeyName(string $name)
    {
        $this->primaryKey = $name;

        return $this;
    }

    public function doesNotAutoIncrements()
    {
        $this->autoIncrements = false;

        return $this;
    }

    /**
     * Creates the model as a view model.
     *
     * @return self
     */
    public function asViewModel()
    {
        $this->isViewModel = true;

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

    public function withoutAccessors()
    {
        $self = clone $this;
        $self->provideAccessors = false;
        return $self;
    }

    public function build()
    {
        $component = PHPClass($this->name());
        if ($this->isViewModel) {
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
                PHPClassMethod(
                    'messages',
                    [],
                    'array<string,string|string[]>',
                    PHPTypesModifiers::PUBLIC,
                    'Returns a fluent validation rules'
                )->addContents(
                    'return ' . PHPVariable('rules', null, $this->rules ?? [])->asRValue()->__toString()
                )
            );
            if (!$this->isSingleActionValidator) {
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
                        )->addContents('return ' . PHPVariable('rules', null, $this->rules ?? [])->asRValue()->__toString())
                    );
            } else {
                /**
                 * @var Blueprint
                 */
                $component = $component
                    ->addImplementation(\Drewlabs\Contracts\Validator\BaseValidatable::class);
            }
        }

        // Add inputs traits
        if ($this->hasInputsTraits) {
            /**
             * @var BluePrint
             */
            $component = $component->addTrait(\Drewlabs\Core\Validator\Traits\ViewModel::class);
        }

        // If the generator is configured to provide model relation, the relation is generated
        $component = $this->addRelations($component);

        $component->setBaseClass(EloquentModel::class)
            ->asFinal()
            // Model associated table
            ->addProperty(
                PHPClassProperty(
                    'table',
                    'string',
                    PHPTypesModifiers::PROTECTED,
                    $this->table ?? 'examples',
                    'Model referenced table'
                )
            )
            // Model hidden attributes
            ->addProperty(
                PHPClassProperty(
                    'hidden',
                    'array',
                    PHPTypesModifiers::PROTECTED,
                    $this->hidden ?? [],
                    'List of values that must be hidden when generating the json output'
                )
            )
            // Model appendable attributes
            ->addProperty(
                PHPClassProperty(
                    'appends',
                    'array',
                    PHPTypesModifiers::PROTECTED,
                    $this->appends ?? [],
                    'List of attributes that will be appended to the json output of the model'
                )
            )
            // Models fillable Columns
            ->addProperty(
                PHPClassProperty(
                    'fillable',
                    'array',
                    PHPTypesModifiers::PROTECTED,
                    $this->columns ? array_map(static function (ORMColumnDefinition $column) {
                        return $column->name();
                    }, $this->columns) : [],
                    'List of fillable properties of the current model'
                )
            )
            ->addProperty(
                PHPClassProperty(
                    'relation_methods',
                    'array',
                    PHPTypesModifiers::PUBLIC,
                    $this->relationMethods ?? [],
                    'List of model relation methods'
                )
            )
            ->addToNamespace($this->namespace_ ?? self::DEFAULT_NAMESPACE);

        // #region Add properties setters and getters
        // TODO : Add these method to a class region instance in future release
        if ($this->provideAccessors) {
            foreach ($this->columns as $column) {
                if (\in_array($name = $column->name(), array_merge(static::RESERVED_KEYWORDS, [$this->primaryKey ?? 'id']), true)) { // RESERVED_KEYWORDS
                    continue;
                }
                $component = $component->addMethod($this->createPropertySetter($name))
                    ->addMethod($this->createPropertyGetter($name));
            }
        }
        // #region Add properties setters and getters

        // #region add boot method
        /**
         * @var Blueprint
         */
        $component = $component->addMethod(
            PHPClassMethod(
                'boot',
                [],
                'void',
                PHPTypesModifiers::PROTECTED,
                'Bootstrap the model and its traits.'
            )->asStatic(true)->addLine('parent::boot()')
        );
        // #endregion add boot method

        if (null !== $this->primaryKey) {
            /**
             * @var Blueprint
             */
            $component = $component->addProperty(
                PHPClassProperty(
                    'primaryKey',
                    'string',
                    PHPTypesModifiers::PROTECTED,
                    $this->primaryKey ?? 'id',
                    'Table primary key'
                )
            );
        }

        if ((null !== $this->autoIncrements) && !$this->autoIncrements) {
            $component = $component->addTrait(trait_exists(\Illuminate\Database\Eloquent\Concerns\HasUuids::class) ? \Illuminate\Database\Eloquent\Concerns\HasUuids::class : \Drewlabs\Laravel\Query\Traits\HasUuids::class);
        }

        // Checks if the model is a pivot table model
        // and add the required laravel pivot trait to the model
        if ($this->aspivot) {
            $component = $component->addTrait(\Illuminate\Database\Eloquent\Relations\Concerns\AsPivot::class);
        }

        if (!$this->hasTimestamps) {
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
        $component = array_reduce(static::CLASS_IMPLEMENTATIONS, static function (Blueprint $carry, $curr) {
            $carry = $carry->addImplementation($curr);

            return $carry;
        }, $component);

        // Add Traits
        /**
         * @var BluePrint
         */
        $component = array_reduce(static::CLASS_TRAITS, static function (Blueprint $carry, $curr) {
            $carry = $carry->addTrait($curr);

            return $carry;
        }, $component);

        // Add class path
        /**
         * @var BluePrint
         */
        $component = array_reduce(static::CLASS_PATHS, static function (Blueprint $carry, $curr) {
            $carry = $carry->addClassPath($curr);

            return $carry;
        }, $component);

        // Returns the builded component
        return PHPScript(
            $component->getName(),
            $component,
            ComponentBuilder::rebuildComponentPath(
                $this->namespace_ ?? self::DEFAULT_NAMESPACE,
                $this->path_ ?? self::DEFAULT_PATH
            )
        )->setNamespace($component->getNamespace());
    }

    public static function defaultClassPath(string $classname = null)
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
     * Create model property setter.
     *
     * @return CallableInterface
     */
    private function createPropertySetter(string $name, string $type = null)
    {
        return PHPClassMethod(sprintf('set%s', Str::camelize($name)), [PHPFunctionParameter('value', $type)], 'static', 'public', sprintf('Set `%s` property to the parameter value', $name))
            ->addLine(sprintf("\$this->setRawPropertyValue('%s', \$value)", $name))
            ->addLine('return $this');
    }

    /**
     * Creates model property getter.
     *
     * @return CallableInterface
     */
    private function createPropertyGetter(string $name, string $type = null)
    {
        return PHPClassMethod(sprintf('get%s', Str::camelize($name)), [], $type ?? 'mixed', 'public', sprintf('Get `%s` property value', $name))
            ->addLine(sprintf("return \$this->getRawPropertyValue('%s')", $name));
    }

    /**
     * Set the base properties of the current component.
     *
     * @param mixed $schema
     * @param mixed $table
     * @param mixed $name
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    private function setComponentBaseDefinitions($schema, $table, $name)
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
     * Add model releation methods.
     *
     * @return Blueprint
     */
    private function addRelations(BluePrint $component)
    {
        if (empty($this->relations)) {
            return $component;
        }
        $this->relationMethods = $this->relationMethods ?? [];
        $methods = [];
        foreach ($this->relations as $relation) {
            if (!\array_key_exists($method = $relation->getName(), $methods)) {
                $methods[$method] = 0;
            } else {
                $methods[$method] = ($method[$method] ?? 0 + 1);
            }
            $type = $relation->getType();
            if (RelationTypes::ONE_TO_MANY === $type || RelationTypes::ONE_TO_ONE === $type) {
                $component = $component->addMethod($this->createOneOrManyMethodTemplate($relation, $type, $methods));
                $this->relationMethods[] = $relation->getName();
                continue;
            }
            if (RelationTypes::MANY_TO_ONE === $type) {
                $component = $component->addMethod($this->createBelongsToTemplate($relation, $methods));
                $this->relationMethods[] = $relation->getName();
                continue;
            }
            if (RelationTypes::MANY_TO_MANY === $type && $relation instanceof \Drewlabs\GCli\ThroughRelation) {
                /**
                 * @var BluePrint
                 */
                $component = $component->addMethod($this->createManyToManyRelationTemplate($relation, $methods));
                $this->relationMethods[] = $relation->getName();
                continue;
            }
            if (
                \in_array($type, [RelationTypes::ONE_TO_MANY_THROUGH, RelationTypes::ONE_TO_ONE_THROUGH], true)
                && $relation instanceof \Drewlabs\GCli\ThroughRelation
            ) {
                /**
                 * @var BluePrint
                 */
                $component = $component->addMethod($this->createThroughRelationTemplate($relation, $methods));
                $this->relationMethods[] = $relation->getName();
                continue;
            }
        }

        return $component;
    }

    /**
     * Creates a HasMany or HasOne method template.
     *
     * @return CallableInterface
     */
    private function createOneOrManyMethodTemplate(\Drewlabs\GCli\BasicRelation $relation, string $type, array $methods)
    {
        $model = $relation->getModel();
        $local = $relation->getLocal();
        $reference = $relation->getReference();
        $returns = RelationTypes::ONE_TO_MANY === $type ? \Illuminate\Database\Eloquent\Relations\HasMany::class : \Illuminate\Database\Eloquent\Relations\HasOne::class;
        $method = RelationTypes::ONE_TO_MANY === $type ? 'hasMany' : 'hasOne';

        return PHPClassMethod(
            $this->resolvename($relation->getName(), $methods),
            [],
            $returns,
            'public',
            RelationTypes::ONE_TO_MANY === $type ? 'returns an eloquent `has many` relation' : 'returns an eloquent `has one` relation'
        )->addLine("return \$this->$method(\\$model::class, '$local', '$reference')");
    }

    /**
     * Creates a belongs to method template.
     *
     * @return CallableInterface
     */
    private function createBelongsToTemplate(\Drewlabs\GCli\BasicRelation $relation, array $methods)
    {
        $model = $relation->getModel();
        $local = $relation->getLocal();
        $reference = $relation->getReference();
        $returns = \Illuminate\Database\Eloquent\Relations\BelongsTo::class;

        return PHPClassMethod(
            $this->resolvename($relation->getName(), $methods),
            [],
            $returns,
            'public',
            'returns an eloquent `belongs to` relation'
        )->addLine("return \$this->belongsTo(\\$model::class, '$local', '$reference')");
    }

    /**
     * Createa a one to one through or a one to many though relation method template for the model.
     *
     * @return CallableInterface
     */
    private function createThroughRelationTemplate(\Drewlabs\GCli\ThroughRelation $relation, array $methods)
    {
        $returns = RelationTypes::ONE_TO_MANY_THROUGH === $relation->getType() ? \Illuminate\Database\Eloquent\Relations\HasManyThrough::class : \Illuminate\Database\Eloquent\Relations\HasOneThrough::class;
        $left = $relation->getLeftTable();
        $right = $relation->getRightTable();
        $leftforeignkey = $relation->getLeftForeignKey();
        $rightforeignkey = $relation->getRightForeignKey();
        $leftlocalkey = $relation->getLeftLocalKey();
        $rightlocalkey = $relation->getRightLocalKey();
        $line = RelationTypes::ONE_TO_MANY_THROUGH === $relation->getType() ? "return \$this->hasManyThrough(\\$left::class, \\$right::class, " : "return \$this->hasOneThrough(\\$left::class, \\$right::class, ";
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

        return PHPClassMethod(
            $this->resolvename($relation->getName(), $methods),
            [],
            $returns,
            'public',
            'returns an eloquent `through` relation'
        )->addLine($line);
    }

    /**
     * Creates a many to many relation method template.
     *
     * @return CallableInterface
     */
    private function createManyToManyRelationTemplate(\Drewlabs\GCli\ThroughRelation $relation, array $methods)
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

        return PHPClassMethod(
            $this->resolvename($relation->getName(), $methods),
            [],
            $returns,
            'public'
        )->addLine($line);
    }

    /**
     * Resolve relation name.
     *
     * @return string
     */
    private function resolvename(string $name, array $methods)
    {
        return isset($methods[$name]) && $methods[$name] > 0 ? sprintf('%s_%d', $name, $methods[$name]) : $name;
    }
}
