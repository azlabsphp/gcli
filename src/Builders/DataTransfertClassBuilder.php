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
use Drewlabs\CodeGenerator\Types\PHPTypesModifiers;
use Drewlabs\ComponentGenerators\Contracts\ComponentBuilder;
use Drewlabs\ComponentGenerators\Helpers\ComponentBuilderHelpers;
use function Drewlabs\ComponentGenerators\Proxy\PHPScript;

use Drewlabs\ComponentGenerators\Traits\HasNamespaceAttribute;
use Drewlabs\Core\Helpers\Str;
use Drewlabs\Filesystem\Exceptions\UnableToRetrieveMetadataException;
use Illuminate\Support\Pluralizer;
use InvalidArgumentException;
use RuntimeException;

class DataTransfertClassBuilder implements ComponentBuilder
{
    use HasNamespaceAttribute;

    /**
     * Service default class namespace.
     *
     * @var string
     */
    public const DEFAULT_NAMESPACE = 'App\\Dto';

    /**
     * @var string
     */
    private const DEFAULT_NAME = 'TestDto';

    /**
     * @var string
     */
    private const DEFAULT_PATH = 'Dto/';

    /**
     * List of attributes that can be json serializable.
     *
     * @var array|\Closure
     */
    private $properties = [];

    /**
     * List of cast attributes
     * 
     * @var array
     */
    private $casts = [];

    /**
     * List of the data transfert object hidden properties.
     *
     * @var array
     */
    private $excepts = [];

    /**
     * @var array|\Closure
     */
    private $propertyDocComments = [];

    /**
     * @var string
     */
    private $modelClassPath;

    /**
     * 
     * @var bool
     */
    private $camelize = false;

    /**
     * Creates a data transfert class instance
     * 
     * @param array $json_attributes 
     * @param (null|string)|null $name 
     * @param (null|string)|null $namespace 
     * @param (null|string)|null $path 
     * @return void 
     * @throws InvalidArgumentException 
     * @throws RuntimeException 
     * @throws UnableToRetrieveMetadataException 
     */
    public function __construct(
        array $json_attributes = [],
        ?string $name = null,
        ?string $namespace = null,
        ?string $path = null
    ) {
        $this->setName($name ? (!Str::endsWith($name, 'Dto') ?
            Str::camelize(Pluralizer::singular($name)) . 'Dto' :
            Str::camelize(Pluralizer::singular($name))) : self::DEFAULT_NAME);
        // Set the component write path
        $this->setWritePath($path ?? self::DEFAULT_PATH);

        // Set the component namespace
        $this->setNamespace($namespace ?? self::DEFAULT_NAMESPACE);
        // Set json_attributes
        $this->setAttributes(function ($camelize) use ($json_attributes) {
            return $this->resolveClassProperties(array_keys($json_attributes ?? []), $camelize);
        });
        $this->propertyDocComments = function ($camelize) use ($json_attributes) {
            return $this->buildPropertiesTypeComments($json_attributes ?? [], $camelize);
        };
    }

    /**
     * Attach a model class metadata to the data transfer class builder
     * 
     * @param mixed $model 
     * @return self 
     * @throws InvalidArgumentException 
     */
    public function bindModel($model)
    {
        if (null === $model) {
            return $this;
        }
        $classname = \is_object($model) ? \get_class($model) : $model;
        $isClassPath = Str::contains($classname, '\\');
        if ($isClassPath) {
            $this->modelClassPath = $classname;
        }
        $modelname = 'Test';
        $modelname = $isClassPath ? $this->getClassNameFromPath($classname) : $classname;
        $this->setName(Str::camelize(Pluralizer::singular($modelname)) . 'Dto');

        // creates an object to if the model is a PHP string
        if (\is_string($model) && class_exists($model)) {
            $model = new $model();
        }
        // Get or load the fillable properties
        if (!empty($fillables = (method_exists($model, 'getFillables') ? $model->getFillables() : []))) {
            $this->setAttributes(function ($camelize) use ($fillables) {
                return $this->resolveClassProperties($fillables, $camelize);
            });
        }
        return $this;
    }

    /**
     * Set the list of hidden properties.
     *
     * @return self
     */
    public function setHidden(array $properties = [])
    {
        $this->excepts = $properties ?? [];

        return $this;
    }

    /**
     * Set the attribute or attribute creator function that is invoked when buiding properties
     * 
     * @param array|\Closure $attributes 
     * @return $this 
     */
    public function setAttributes($attributes = [])
    {
        if (is_array($attributes) && empty($attributes)) {
            return $this;
        }
        $this->properties = $attributes ?? [];
        return $this;
    }

    /**
     * Set the cast attributes
     * 
     * @param array $casts 
     * @return self 
     */
    public function setCasts(array $casts = [])
    {
        if (!empty($casts)) {
            $this->casts = $casts;
        }

        return $this;
    }

    /**
     * Configure support of camel case transformation for the current instance
     * 
     * @param bool $value 
     * @return self 
     */
    public function setCamelizeProperties(bool $value)
    {
        $this->camelize = $value;
        return $this;
    }

    public function build()
    {
        /**
         * @var BluePrint
         */
        $component = (PHPClass($this->name()))
            ->addImplementation(\Drewlabs\PHPValue\Contracts\ValueInterface::class)
            ->addComment(
                array_merge(
                    is_array($this->propertyDocComments) ?
                        $this->propertyDocComments ?? [] : (null !== $this->propertyDocComments ? ($this->propertyDocComments)($this->camelize) : []),
                    [
                        ' ',
                        '@package ' . $this->namespace_ ?? self::DEFAULT_NAMESPACE,
                    ]
                )
            )
            ->addTrait(\Drewlabs\PHPValue\Traits\ModelAwareValue::class)
            ->addToNamespace($this->namespace_ ?? self::DEFAULT_NAMESPACE);

        if (interface_exists(\Illuminate\Contracts\Routing\UrlRoutable::class)) {
            $component = $component->addImplementation(\Illuminate\Contracts\Routing\UrlRoutable::class)
                ->addTrait(\Drewlabs\Packages\Database\Traits\URLRoutableModelAware::class);
        }

        if ($this->modelClassPath) {
            $component = $component->addMethod(
                (PHPClassMethod(
                    'resolveModel',
                    [],
                    "$this->modelClassPath",
                    PHPTypesModifiers::PUBLIC,
                    'Creates an instance of the attached model'
                ))->addContents(
                    'return self::createResolver(' . $this->getClassNameFromPath($this->modelClassPath) . '::class)()'
                )
            );
        }

        $component = $component->addProperty(
            PHPClassProperty(
                '__PROPERTIES__',
                'array',
                PHPTypesModifiers::PROTECTED,
                is_array($this->properties) ?
                    $this->properties ?? [] : (null !== $this->properties ? ($this->properties)($this->camelize) : [])
            )
        );

        $component = $component->addProperty(
            PHPClassProperty(
                '__HIDDEN__',
                'array',
                PHPTypesModifiers::PROTECTED,
                $this->excepts ?? []
            )
        );
        $component = $component->addProperty(
            PHPClassProperty(
                '__CASTS__',
                'array',
                PHPTypesModifiers::PROTECTED,
                $this->casts ?? []
            )
        );
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

    private function resolveClassProperties(array $values = [], bool $camelize = false)
    {
        $attributes = [];
        if ($camelize) {
            foreach (array_filter($values ?? []) as $key => $value) {
                $name = !is_numeric($key) ?
                    Str::camelize(ltrim(rtrim($key, '_'), '_'), false) : Str::camelize(ltrim(rtrim($value, '_'), '_'), false);
                $attributes[$name] = $value;
            }
        } else {
            foreach (array_filter($values ?? []) as $key => $value) {
                $attributes[] = !is_numeric($key) ? $key : $value;
            }
        }
        return $attributes;
    }

    private function getPHPType(string $type)
    {
        switch (strtolower($type)) {
            case 'bigint':
            case 'integer':
            case 'integer':
                return 'int';
            case 'string':
            case 'datetime':
                return 'string';
            case 'decimal':
                return 'float';
            default:
                return 'mixed';
        }
    }

    private function buildPropertiesTypeComments(array $properties = [], bool $camelize = false)
    {
        $comments = [];
        foreach ($properties as $key => $value) {
            $name = false !== strpos($value, ':') ? Str::before(':', $value) : $value;
            $comments[] = '@property ' . $this->getPHPType($name) . ' ' . ($camelize ? Str::camelize(trim($key), false) : $key);
        }
        return $comments;
    }

    private function getClassNameFromPath(string $name)
    {
        return array_reverse(explode('\\', $name))[0];
    }
}
