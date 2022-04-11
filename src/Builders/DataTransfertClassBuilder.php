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
use Drewlabs\PHPValue\Traits\ModelAwareValue;
use Illuminate\Support\Pluralizer;

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
     * @var array
     */
    private $json_attributes_ = [];

    /**
     * List of the data transfert object hidden properties.
     *
     * @var array
     */
    private $hidden_ = [];

    /**
     * @var array
     */
    private $propertyDocComments = [];

    /**
     * 
     * @var string
     */
    private $modelClassPath;

    public function __construct(
        array $json_attributes = [],
        ?string $name = null,
        ?string $namespace = null,
        ?string $path = null
    ) {
        if ($name) {
            $this->setName(drewlabs_core_strings_as_camel_case(Pluralizer::singular($name)) . 'Dto');
        }
        // Set the component write path
        $this->setWritePath($path ?? self::DEFAULT_PATH);

        // Set the component namespace
        $this->setNamespace($namespace ?? self::DEFAULT_NAMESPACE);
        // Set json_attributes
        $this->setAttributes($this->buildAsAssociativeArray(array_keys($json_attributes ?? [])));
        $this->propertyDocComments = $this->buildPropertiesTypeComments($json_attributes ?? []);
    }

    /**
     * @param object|string $model
     *
     * @return self
     */
    public function bindModel($model)
    {
        if (null === $model) {
            return $this;
        }
        $classname = \is_object($model) ? \get_class($model) : $model;
        $is_class_path = drewlabs_core_strings_contains($classname, '\\'); // && class_exists($model); To uncomment if there is need to validate class existence
        if ($is_class_path) {
            $this->modelClassPath = $classname;
        }
        $model_name = 'Test';
        $model_name = $is_class_path ?
            array_reverse(explode('\\', $classname))[0] : (drewlabs_core_strings_contains($classname, '\\') ?
                array_reverse(explode('\\', $classname))[0] :
                $classname);
        $this->setName(drewlabs_core_strings_as_camel_case(Pluralizer::singular($model_name)) . 'Dto');

        // creates an object to if the model is a PHP string
        if (\is_string($model) && class_exists($model)) {
            $model = new $model();
        }
        // Get or load the fillable properties
        return $this->setAttributes(
            $this->buildAsAssociativeArray(
                method_exists($model, 'getFillables') ? $model->getFillables() : []
            )
        );
    }

    /**
     * Set the list of hidden properties.
     *
     * @return self
     */
    public function setHidden(array $properties = [])
    {
        $this->hidden_ = $properties ?? [];

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
        /**
         * @var BluePrint
         */
        $component = (PHPClass($this->name_ ?? self::DEFAULT_NAME))
            ->addImplementation(\Drewlabs\PHPValue\Contracts\ValueInterface::class)
            ->addComment(
                array_merge(
                    $this->propertyDocComments ?? [],
                    [
                        ' ',
                        '@package ' . $this->namespace_ ?? self::DEFAULT_NAMESPACE,
                    ]
                )
            )
            ->addTrait(ModelAwareValue::class)
            ->addTrait(\Drewlabs\Packages\Database\Traits\ContainerAware::class)
            ->addToNamespace($this->namespace_ ?? self::DEFAULT_NAMESPACE);

        if ($this->modelClassPath) {
            $component = $component->addMethod(
                (PHPClassMethod(
                    'resolveModel',
                    [],
                    "$this->modelClassPath",
                    PHPTypesModifiers::PUBLIC,
                    'Creates an instance of the attached model'
                ))->addContents(
                    "return self::createResolver($this->modelClassPath)()"
                )
            );
        }

        $component = $component->addProperty(
            PHPClassProperty(
                '__PROPERTIES__',
                'array',
                PHPTypesModifiers::PROTECTED,
                $this->json_attributes_ ?? []
            )
        );

        $component = $component->addProperty(
            PHPClassProperty(
                '__HIDDEN__',
                'array',
                PHPTypesModifiers::PROTECTED,
                $this->hidden_ ?? []
            )
        );

        $component = $component->addProperty(
            PHPClassProperty(
                '__CASTS__',
                'array',
                PHPTypesModifiers::PROTECTED,
                []
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
        if (drewlabs_core_strings_contains($classname, '\\')) {
            return $classname;
        }

        return sprintf('%s%s%s', self::DEFAULT_NAMESPACE, '\\', $classname);
    }

    private function buildAsAssociativeArray(array $values = [])
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

    private function buildPropertiesTypeComments(array $properties = [])
    {
        $comments = [];
        foreach ($properties as $key => $value) {
            if (false !== strpos($value, ':')) {
                $name = Str::before(':', $value);
            } else {
                $name = $value;
            }
            $comments[] = '@property ' . $this->getPHPType($name) . ' ' . Str::camelize(trim($key), false);
        }

        return $comments;
    }
}
