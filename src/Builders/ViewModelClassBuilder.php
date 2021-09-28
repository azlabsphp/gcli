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
use function Drewlabs\CodeGenerator\Proxy\PHPVariable;
use function Drewlabs\ComponentGenerators\Proxy\PHPScript;

use Drewlabs\CodeGenerator\Types\PHPTypesModifiers;
use Drewlabs\ComponentGenerators\Contracts\ComponentBuilder;
use Drewlabs\ComponentGenerators\Helpers\ComponentBuilderHelpers;
use Drewlabs\ComponentGenerators\Traits\HasNamespaceAttribute;
use Drewlabs\ComponentGenerators\Traits\ViewModelBuilder;
use Drewlabs\Core\Validator\Traits\ViewModel;

use Drewlabs\Contracts\Validator\CoreValidatable;
use Drewlabs\Contracts\Validator\Validatable;
use Illuminate\Support\Pluralizer;

class ViewModelClassBuilder implements ComponentBuilder
{
    use HasNamespaceAttribute;
    use ViewModelBuilder;

    /**
     * @var string
     */
    private const DEFAULT_NAME = 'TestViewModel';

    /**
     * @var string
     */
    private const DEFAULT_PATH = 'Http/ViewModels';

    /**
     * Service default class namespace.
     *
     * @var string
     */
    public const DEFAULT_NAMESPACE = 'App\\Http\\ViewModels';

    public function __construct(
        ?string $name = null,
        ?string $namespace = null,
        ?string $path = null
    ) {
        if (null !== $name) {
            $this->setName($name);
        }
        // Set the component write path
        $this->setWritePath($path ?? self::DEFAULT_PATH);
        // Set the component namespace
        $this->setNamespace($namespace ?? self::DEFAULT_NAMESPACE);
    }

    public function bindModel(string $model)
    {
        if (empty($model)) {
            return $this;
        }
        $is_class_path = drewlabs_core_strings_contains($model, '\\'); // && class_exists($model); To uncomment if there is need to validate class existence
        $model_name = 'Test';
        $model_name = $is_class_path ? array_reverse(explode('\\', $model))[0] : (drewlabs_core_strings_contains($model, '\\') ? array_reverse(explode('\\', $model))[0] : $model);
        $name = drewlabs_core_strings_as_camel_case(Pluralizer::singular($model_name)) . 'ViewModel';
        $this->setName($name);

        return $this;
    }

    public function build()
    {
        /**
         * @var BluePrint|PHPClass
         */
        $component = (PHPClass($this->name() ?? self::DEFAULT_NAME))
            ->addToNamespace($this->namespace() ?? self::DEFAULT_NAMESPACE)
            ->addMethod(
                PHPClassMethod(
                    'rules',
                    [],
                    'array<string,string|string[]>',
                    PHPTypesModifiers::PUBLIC,
                    'Returns a fluent validation rules'
                )->addContents(
                    'return ' . PHPVariable('rules', null, $this->rules_ ?? [])->asRValue()->__toString()
                )
            )->addMethod(
                PHPClassMethod(
                    'messages',
                    [],
                    'array<string,string|string[]>',
                    PHPTypesModifiers::PUBLIC,
                    'Returns a list of validation error messages'
                )->addContents(
                    'return []'
                )
            );
        if (!$this->isSingleActionValidator_) {
            /**
             * @var BluePrint|PHPClass
             */
            $component = $component
                ->addImplementation(Validatable::class)
                ->addMethod(
                    PHPClassMethod(
                        'updateRules',
                        [],
                        'array<string,string|string[]>',
                        PHPTypesModifiers::PUBLIC,
                        'Returns a fluent validation rules applied during update actions'
                    )->addContents('return ' . PHPVariable('rules', null, $this->updateRules_ ?? [])->asRValue()->__toString())
                );
        } else {
            /**
             * @var Blueprint
             */
            $component = $component
                ->addImplementation(CoreValidatable::class);
        }

        // Add inputs traits
        if ($this->hasInputsTraits_) {
            /**
             * @var Blueprint
             */
            $component = $component->addTrait(ViewModel::class);
        }
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
}
