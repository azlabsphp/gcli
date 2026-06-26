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

namespace Drewlabs\GCli\Plugins\Laravel;

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
use Drewlabs\GCli\Contracts\HasRelations;
use Drewlabs\GCli\Contracts\ORMColumnDefinition;
use Drewlabs\GCli\Contracts\ORMModelDefinition;
use Drewlabs\GCli\Contracts\Pivotable;
use Drewlabs\GCli\Contracts\ProvidesPropertyAccessors;
use Drewlabs\GCli\DBAL\ProvidesTrimTableSchema;
use Drewlabs\GCli\DBAL\R\Through;

use Drewlabs\GCli\DBAL\R\Types;
use Drewlabs\GCli\Factories\ComponentPath;
use Drewlabs\GCli\Plugins\Laravel\Observers\Observers;
use Drewlabs\GCli\Plugins\Laravel\Traits\HasNamespaceAttribute;
use Drewlabs\GCli\Plugins\Laravel\Traits\ViewModelBuilder;

use function Drewlabs\GCli\Proxy\PHPScript;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Pluralizer;

final class ORMModelBuilder implements AbstractORMModelBuilder, AbstractBuilder, HasRelations, ProvidesPropertyAccessors, Pivotable
{
    use HasNamespaceAttribute;
    use ProvidesTrimTableSchema;
    use ViewModelBuilder;

    /** @var string name of the model. */
    public const DEFAULT_NAME = 'Test';

    /** @var string namespace of the model. */
    public const DEFAULT_NAMESPACE = 'App\\Models';

    /** @var string */
    public const DEFAULT_PATH = 'Models';

    /** @var string[] */
    public const CLASS_PATHS = [
        'Drewlabs\\PHPValue\\Contracts\\Adaptable',
        'Drewlabs\\Query\\Contracts\\Queryable as AbstractQueryable',
        'Illuminate\\Database\\Eloquent\\Model',
        'Drewlabs\\Laravel\\Query\\Traits\\Queryable',
    ];

    /** @var string[] */
    public const CLASS_TRAITS = [
        'Queryable',
    ];

    /** @var string[] */
    public const CLASS_IMPLEMENTATIONS = [
        'AbstractQueryable',
        'Adaptable',
    ];

    /**
     * A growable list of reserved keywords for which setter
     * and getters should not be generated when generating methods
     * for columns.
     *
     * @var string[]
     */
    public const RESERVED_KEYWORDS = [
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
        'time_stamp',
    ];

    /** @var string[] List of supported observers */
    private const OBSERVERS = ['creating', 'created', 'saving', 'saved', 'updating', 'updated', 'deleting', 'deleted'];

    /** @var array list of appendable model properties. */
    private $appends = [];

    /** @var array list of model properties that will not be added to serialized output. */
    private $hidden = [];

    /** @var bool indicates whether the model must have a timestamp or not. */
    private $hasTimestamps = true;

    /** @var string table name binded to the model. */
    private $table = 'examples';

    /** Database table schema value */
    /** @var string */
    private $schema;

    /** @var ORMColumnDefinition[] List of table columns. */
    private $columns = [];

    /** @var string|null They primary key of the model/table. */
    private $primaryKey;

    /** @var bool is the model primary key incrementable. */
    private $autoIncrements = true;

    /** @var array List of eloquent relation methods. */
    private $relationMethods = [];

    /** @var bool Specify that the model act like a view model. */
    private $isViewModel = false;

    /** @var ORMModelDefinition */
    private $definition;

    /** @var (\Drewlabs\GCli\DBAL\R\Basic|\Drewlabs\GCli\DBAL\R\Through)[] */
    private $relations = [];

    /** @var bool Makes the table a pivot table. */
    private $aspivot;

    /** @var bool */
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
        ORMModelDefinition $definition,
        ?string $schema = null,
        ?string $path = null
    ) {
        $this->setDefinition($definition);
        [$table, $name] = [$definition->table(), $definition->name()];
        $this->setComponentBaseDefinitions($schema, $table, $name);
        $this->setKeyName($definition->primaryKey() ?? 'id');
        $this->setColumns($definition->columns());


        // Set the AUTO-INCREMENTS property
        if (!$definition->shouldAutoIncrements()) {
            $this->doesNotAutoIncrements();
        }

        // Set the model namespace
        if ($definition->namespace()) {
            $this->setNamespace($definition->namespace());
        }

        // Set the model path
        $this->setWritePath($path ?? static::DEFAULT_PATH);
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

    public function withRelations(array $relations = [])
    {
        $this->relations = $relations;

        return $this;
    }

    public function getRelations(): array
    {
        return $this->relations;
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
        $comments = [];
        $component = PHPClass($this->name());
        if ($this->isViewModel) {
            /** @var Blueprint */
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
                /** @var Blueprint */
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
                /** @var Blueprint */
                $component = $component
                    ->addImplementation(\Drewlabs\Contracts\Validator\BaseValidatable::class);
            }
        }

        if ($this->hasInputsTraits) {
            foreach (ViewModelClassBuilder::DEFAULT_CLASS_PATHS as $classPath) {
                $component = $component->addClassPath($classPath);
            }
            foreach (ViewModelClassBuilder::DEFAULT_CLASS_TRAITS as $trait) {
                $component = $component->addTrait($trait);
            }
        }

        // If the generator is configured to provide model relation, the relation is generated
        $component = $this->addRelations($component, $comments);

        $component->setBaseClass(EloquentModel::class)
            ->asFinal()
            // Model associated table
            ->addProperty(
                PHPClassProperty(
                    'table',
                    'string',
                    PHPTypesModifiers::PROTECTED,
                    $this->table,
                    'Model referenced table'
                )
            )
            // Model hidden attributes
            ->addProperty(
                PHPClassProperty(
                    'hidden',
                    'array',
                    PHPTypesModifiers::PROTECTED,
                    $this->hidden,
                    'List of values that must be hidden when generating the json output'
                )
            )
            // Model appendable attributes
            ->addProperty(
                PHPClassProperty(
                    'appends',
                    'array',
                    PHPTypesModifiers::PROTECTED,
                    $this->appends,
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
                    $this->relationMethods,
                    'List of model relation methods'
                )
            )
            ->addToNamespace($this->package ?? static::DEFAULT_NAMESPACE);

        // #region Add properties setters and getters
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
        $boot = PHPClassMethod('boot', [], 'void', PHPTypesModifiers::PROTECTED, 'Bootstrap the model and its traits.')->asStatic(true)
            ->addLine('parent::boot()')
            ->addLine('');

        foreach (static::OBSERVERS as $value) {
            if ($observers = Observers::getInstance()->get(sprintf('%s.%s', $this->table, $value))) {
                $boot->addLine(sprintf('parent::%s(function(self $model) {', $value));
                foreach ($observers as $expression) {
                    $items = array_map(static function ($item) {
                        return sprintf('    %s', $item);
                    }, explode(\PHP_EOL, (string) $expression));
                    foreach ($items as $item) {
                        $boot->addLine(rtrim($item, " \n\r\t\v\0;"));
                    }
                }
                $boot->addLine('});');
                $boot->addLine("");
                continue;
            }
            if ($observers = Observers::getInstance()->get(sprintf('%s.%s', static::trimschema($this->table, $this->schema), $value))) {
                $boot->addLine(sprintf('parent::%s(function(self $model) {', $value));
                foreach ($observers as $expression) {
                    $items = array_map(static function ($item) {
                        return sprintf('    %s', $item);
                    }, explode(\PHP_EOL, (string) $expression));
                    foreach ($items as $item) {
                        $boot->addLine(rtrim($item, " \n\r\t\v\0;"));
                    }
                }
                $boot->addLine('});');
                $boot->addLine("");
                continue;
            }
        }

        /** @var Blueprint */
        $component = $component->addMethod($boot);
        // #endregion add boot method

        if (null !== $this->primaryKey) {
            /** @var Blueprint */
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
        /** @var Blueprint */
        $component = array_reduce(static::CLASS_IMPLEMENTATIONS, static function (Blueprint $carry, $curr) {
            $carry = $carry->addImplementation($curr);

            return $carry;
        }, $component);

        // Add Traits
        /** @var Blueprint */
        $component = array_reduce(static::CLASS_TRAITS, static function (Blueprint $carry, $curr) {
            $carry = $carry->addTrait($curr);

            return $carry;
        }, $component);

        // Add class path
        /** @var \Drewlabs\CodeGenerator\Models\PHPClass */
        $component = array_reduce(static::CLASS_PATHS, static function (Blueprint $carry, $curr) {
            $carry = $carry->addClassPath($curr);

            return $carry;
        }, $component);

        $component = $component->addComment($comments);

        // Returns the builded component
        return PHPScript(
            $component->getName(),
            $component,
            ComponentPath::new()->create($this->package ?? static::DEFAULT_NAMESPACE, $this->path ?? static::DEFAULT_PATH)
        )->setNamespace($component->getNamespace());
    }

    public static function defaultClassPath(?string $classname = null)
    {
        $classname = $classname ?? 'Test';
        if (Str::contains($classname, '\\')) {
            return $classname;
        }

        return sprintf('%s%s%s', static::DEFAULT_NAMESPACE, '\\', $classname);
    }

    /**
     * @return void
     */
    public function setDefinition(ORMModelDefinition $value)
    {
        $this->definition = $value;
    }

    /**
     * @return ORMModelDefinition
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * Create model property setter.
     *
     * @return CallableInterface
     */
    private function createPropertySetter(string $name, ?string $type = null)
    {
        return PHPClassMethod(sprintf('set%s', Str::camelize($name)), [PHPFunctionParameter('value', $type)], 'static', 'public', sprintf('mutate `%s` property to the parameter value', $name))
            ->addLine(sprintf("\$this->setRawPropertyValue('%s', \$value)", $name))
            ->addLine('return $this');
    }

    /**
     * Creates model property getter.
     *
     * @return CallableInterface
     */
    private function createPropertyGetter(string $name, ?string $type = null)
    {
        return PHPClassMethod(sprintf('get%s', Str::camelize($name)), [], $type ?? 'mixed', 'public', sprintf('returns `%s` property value', $name))
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
        $table = null === $table ? (null !== $name ? Str::snakeCase(Pluralizer::plural($name)) : null) : $table;
        if ($table) {
            $this->setTableName($table);
        }

        if (($result = $table ?? $name ?? null) && $schema) {
            $result = static::trimschema($result, $schema);
        }

        if ($name = Str::camelize(Pluralizer::singular($result))) {
            $this->setName($name);
        }

        $this->schema = $schema;
    }

    /**
     * Add model releation methods.
     *
     * @return Blueprint
     */
    private function addRelations(Blueprint $component, array &$comments)
    {
        if (empty($this->relations)) {
            return $component;
        }

        /** @var array<string, int> */
        $methods = [];
        foreach ($this->relations as $relation) {
            $method = $relation->getName();
            if (!\array_key_exists($method, $methods)) {
                $methods[$method] = 0;
            } else {
                $methods[$method] = intval($methods[$method] + 1);
            }
            $type = $relation->getType();
            if (Types::ONE_TO_MANY === $type || Types::ONE_TO_ONE === $type) {
                $method = $this->createOneOrManyMethodTemplate($relation, $type, $methods);
                $component = $component->addMethod($method);
                $comments[] = Types::ONE_TO_MANY === $type ? sprintf('@property \Illuminate\Support\Collection<\\%s> $%s', ltrim($relation->getModel(), '\\'), $method->getName()) : sprintf('@property \\%s $%s', ltrim($relation->getModel(), '\\'), $method->getName());
                $this->relationMethods[] = $relation->getName();
                continue;
            }
            if (Types::MANY_TO_ONE === $type) {
                $method = $this->createBelongsToTemplate($relation, $methods);
                $component = $component->addMethod($method);
                $comments[] = sprintf('@property \\%s $%s', ltrim($relation->getModel(), '\\'), $method->getName());
                $this->relationMethods[] = $relation->getName();
                continue;
            }
            if (Types::MANY_TO_MANY === $type && $relation instanceof Through) {
                $method = $this->createManyToManyRelationTemplate($relation, $methods);
                /** @var Blueprint */
                $component = $component->addMethod($method);
                $comments[] = sprintf('@property \Illuminate\Support\Collection<\\%s> $%s', ltrim($relation->getLeftTable(), '\\'), $method->getName());
                $this->relationMethods[] = $relation->getName();
                continue;
            }
            if (
                \in_array($type, [Types::ONE_TO_MANY_THROUGH, Types::ONE_TO_ONE_THROUGH], true)
                && $relation instanceof Through
            ) {
                $method = $this->createThroughRelationTemplate($relation, $methods);
                /** @var Blueprint */
                $component = $component->addMethod($method);
                $comments[] = Types::ONE_TO_MANY_THROUGH === $relation->getType() ? sprintf('@property \Illuminate\Support\Collection<\\%s> $%s', ltrim($relation->getLeftTable(), '\\'), $method->getName()) : sprintf('@property \\%s $%s', ltrim($relation->getLeftTable(), '\\'), $method->getName());
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
    private function createOneOrManyMethodTemplate(\Drewlabs\GCli\DBAL\R\Basic $relation, string $type, array $methods)
    {
        $model = $relation->getModel();
        $local = $relation->getLocal();
        $reference = $relation->getReference();
        $returns = Types::ONE_TO_MANY === $type ? \Illuminate\Database\Eloquent\Relations\HasMany::class : \Illuminate\Database\Eloquent\Relations\HasOne::class;
        $method = Types::ONE_TO_MANY === $type ? 'hasMany' : 'hasOne';
        $returnType = "\\$returns<\\$model>";

        return PHPClassMethod(
            $this->resolvename($relation->getName(), $methods),
            [],
            $returnType,
            'public',
            Types::ONE_TO_MANY === $type ? 'returns an eloquent `has many` relation' : 'returns an eloquent `has one` relation'
        )->addLine("return \$this->$method(\\$model::class, '$local', '$reference')");
    }

    /**
     * Creates a belongs to method template.
     *
     * @return CallableInterface
     */
    private function createBelongsToTemplate(\Drewlabs\GCli\DBAL\R\Basic $relation, array $methods)
    {
        $model = $relation->getModel();
        $local = $relation->getLocal();
        $reference = $relation->getReference();
        $returns = \Illuminate\Database\Eloquent\Relations\BelongsTo::class;
        $returnType = "\\$returns<\\$model>";

        return PHPClassMethod($this->resolvename($relation->getName(), $methods), [], $returnType, 'public', 'returns an eloquent `belongs to` relation')->addLine("return \$this->belongsTo(\\$model::class, '$local', '$reference')");
    }

    /**
     * Createa a one to one through or a one to many though relation method template for the model.
     *
     * @return CallableInterface
     */
    private function createThroughRelationTemplate(Through $relation, array $methods)
    {
        $returns = Types::ONE_TO_MANY_THROUGH === $relation->getType() ? \Illuminate\Database\Eloquent\Relations\HasManyThrough::class : \Illuminate\Database\Eloquent\Relations\HasOneThrough::class;
        $left = $relation->getLeftTable();
        $right = $relation->getRightTable();
        $leftforeignkey = $relation->getLeftForeignKey();
        $rightforeignkey = $relation->getRightForeignKey();
        $leftlocalkey = $relation->getLeftLocalKey();
        $rightlocalkey = $relation->getRightLocalKey();
        $returnType = "\\$returns<\\$left>";

        $line = Types::ONE_TO_MANY_THROUGH === $relation->getType() ? "return \$this->hasManyThrough(\\$left::class, \\$right::class, " : "return \$this->hasOneThrough(\\$left::class, \\$right::class, ";
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

        return PHPClassMethod($this->resolvename($relation->getName(), $methods), [], $returnType, 'public', 'returns an eloquent `through` relation')->addLine($line);
    }

    /**
     * Creates a many to many relation method template.
     *
     * @return CallableInterface
     */
    private function createManyToManyRelationTemplate(Through $relation, array $methods)
    {
        $returns = \Illuminate\Database\Eloquent\Relations\BelongsToMany::class;
        $left = $relation->getLeftTable();
        $right = $relation->getRightTable();
        $leftforeignkey = $relation->getLeftForeignKey();
        $rightforeignkey = $relation->getRightForeignKey();
        $leftlocalkey = $relation->getLeftLocalKey();
        $rightlocalkey = $relation->getRightLocalKey();
        $through = $relation->getThroughTable();
        $returnType = "\\$returns<\\$left>";
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

        return PHPClassMethod($this->resolvename($relation->getName(), $methods), [], $returnType, 'public')->addLine($line);
    }

    /**
     * resolve relation name.
     *
     * @return string
     */
    private function resolvename(string $name, array $methods)
    {
        return isset($methods[$name]) && $methods[$name] > 0 ? sprintf('%s_%d', $name, $methods[$name]) : $name;
    }
}
