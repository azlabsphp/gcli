<?php

namespace Drewlabs\ComponentGenerators\Builders;

use Drewlabs\CodeGenerator\Types\PHPTypesModifiers;
use Drewlabs\ComponentGenerators\Contracts\ComponentBuilder;
use Drewlabs\ComponentGenerators\Traits\HasNamespaceAttribute;
use Drewlabs\Contracts\Data\Model\Parseable;
use Drewlabs\Support\Immutable\ValueObject;
use Illuminate\Support\Pluralizer;
use Drewlabs\CodeGenerator\Contracts\Blueprint;
use Drewlabs\ComponentGenerators\Helpers\ComponentBuilderHelpers;
use Drewlabs\Support\Immutable\ModelValueObject;

use function Drewlabs\CodeGenerator\Proxy\PHPClass;
use function Drewlabs\CodeGenerator\Proxy\PHPClassMethod;
use function Drewlabs\CodeGenerator\Proxy\PHPClassProperty;
use function Drewlabs\CodeGenerator\Proxy\PHPVariable;
use function Drewlabs\ComponentGenerators\Proxy\PHPScript;

/** @package Drewlabs\ComponentGenerators\Builders */
class DataTransfertClassBuilder implements ComponentBuilder
{
    use HasNamespaceAttribute;

    /**
     * @var string
     */
    private const DEFAULT_NAME = 'TestDto';

    /**
     * @var string
     */
    private const DEFAULT_PATH = 'DataTransfertObject/';

    /**
     * Service default class namespace.
     *
     * @var string
     */
    public const DEFAULT_NAMESPACE = 'App\\DataTransfertObject';

    /**
     * List of attributes that can be json serializable
     * 
     * @var array
     */
    private $json_attributes_ = [];

    /**
     * List of the data transfert object hidden properties
     * 
     * @var array
     */
    private $hidden_ = []; //

    /**
     * List of the data transfert object guarded properties
     * 
     * @var array
     */
    private $guarded_ = [];

    public function __construct(
        array $json_attributes = [],
        string $name = null,
        ?string $namespace = null,
        string $path = null
    ) {
        if ($name) {
            $this->setName($name);
        }
        // Set the component write path
        $this->setWritePath($path ?? self::DEFAULT_PATH);

        // Set the component namespace
        $this->setNamespace($namespace ?? self::DEFAULT_NAMESPACE);
        // Set json_attributes
        $this->setAttributes($json_attributes ?? []);
    }

    /**
     * 
     * @param Parseable|string $model
     * 
     * @return self 
     */
    public function bindModel($model)
    {
        if (null === $model) {
            return $this;
        }
        $classname = ($model instanceof Parseable) || is_object($model) ? get_class($model) : $model;
        $is_class_path = drewlabs_core_strings_contains($classname, '\\'); // && class_exists($model); To uncomment if there is need to validate class existence
        $model_name = 'Test';
        $model_name = $is_class_path ? array_reverse(explode('\\', $classname))[0] : (drewlabs_core_strings_contains($classname, '\\') ? array_reverse(explode('\\', $classname))[0] : $classname);
        $this->setName(drewlabs_core_strings_as_camel_case(Pluralizer::singular($model_name)) . 'Dto');

        // creates an object to if the model is a PHP string
        if (is_string($model) && class_exists($model)) {
            $model = new $model;
        }
        // Get or load the fillable properties
        return $this->setAttributes(self::buildAsAssociativeArray(method_exists($model, 'getFillables') ? $model->getFillables() : []));
    }

    /**
     * Set the list of hidden properties
     * 
     * @param array $properties 
     * @return self 
     */
    public function setHidden(array $properties = [])
    {
        $this->hidden_ = $properties ?? [];
        return $this;
    }

    /**
     * Set list of properties that are not assignable in the value object
     * 
     * @param array $properties 
     * @return self 
     */
    public function setGuarded(array $properties = [])
    {
        $this->guarded_ = $properties ?? [];
        return $this;
    }

    public function setAttributes(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->json_attributes_ = $attributes;
        }
        return $this;
    }

    public function build()
    {
        $property = sprintf("return %s", PHPVariable('jsonProperty', 'array', $this->json_attributes_)->asRValue()->__toString());
        /**
         * @var BluePrint
         */
        $component = (PHPClass($this->name_ ?? self::DEFAULT_NAME))
            ->setBaseClass(ModelValueObject::class)
            ->addMethod(
                PHPClassMethod(
                    'getJsonableAttributes',
                    [],
                    'array',
                    PHPTypesModifiers::PROTECTED,
                    'Returns the list of JSON serializable properties'
                )->addContents($property)
            )->addToNamespace($this->namespace_ ?? self::DEFAULT_NAMESPACE);

        $component = $component->addProperty(
            PHPClassProperty(
                ValueObject::hiddenProperty(),
                'array',
                PHPTypesModifiers::PRIVATE,
                $this->hidden_ ?? []
            )
        );

        $component = $component->addProperty(
            PHPClassProperty(
                ValueObject::guardedProperty(),
                'array',
                PHPTypesModifiers::PRIVATE,
                $this->guarded_ ?? []
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
        if (drewlabs_core_strings_contains($classname, "\\")) {
            return $classname;
        }
        return sprintf("%s%s%s", self::DEFAULT_NAMESPACE, "\\", $classname);
    }

    public static function buildAsAssociativeArray(array $values = [])
    {
        $attributes = [];
        // Convert values to camel case for associatve values
        foreach (array_filter($values ?? []) as $key => $value) {
            if (!is_numeric($key)) {
                $attributes[drewlabs_core_strings_as_camel_case(ltrim(rtrim($key, '_'), '_'), false)] = $value;
                continue;
            }
            $attributes[drewlabs_core_strings_as_camel_case(ltrim(rtrim($value, '_'), '_'), false)] = $value;
        }
        return $attributes;
    }
}
