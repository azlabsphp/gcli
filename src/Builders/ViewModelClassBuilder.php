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
use Drewlabs\CodeGenerator\Types\PHPTypes;
use Drewlabs\CodeGenerator\Types\PHPTypesModifiers;

use Drewlabs\ComponentGenerators\Contracts\ComponentBuilder;
use Drewlabs\ComponentGenerators\Helpers\ComponentBuilderHelpers;
use function Drewlabs\ComponentGenerators\Proxy\PHPScript;
use Drewlabs\ComponentGenerators\Traits\HasNamespaceAttribute;
use Drewlabs\ComponentGenerators\Traits\ViewModelBuilder;
use Drewlabs\Core\Helpers\Str;
use Illuminate\Support\Pluralizer;

class ViewModelClassBuilder implements ComponentBuilder
{
    use HasNamespaceAttribute;
    use ViewModelBuilder;

    /**
     * Service default class namespace.
     *
     * @var string
     */
    public const DEFAULT_NAMESPACE = 'App\\Http\\ViewModels';

    /**
     * @var string
     */
    private const DEFAULT_NAME = 'TestViewModel';

    /**
     * @var string
     */
    private const DEFAULT_PATH = 'Http/ViewModels';

    /**
     * @var bool
     */
    private $hasHttpHandlers_ = false;

    /**
     * @var string
     */
    private $modelClassPath_;

    /**
     * @var string
     */
    private $modelName_;

    public function __construct(
        ?string $name = null,
        ?string $namespace = null,
        ?string $path = null
    ) {
        if (null !== $name) {
            $this->setName(!Str::endsWith($name, 'ViewModel') ? Str::camelize(Pluralizer::singular($name)).'ViewModel' : Str::camelize(Pluralizer::singular($name)));
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
        $isClassPath = Str::contains($model, '\\');
        if ($isClassPath) {
            $this->modelClassPath_ = $model;
        }
        if (\is_object($model)) {
            $this->modelClassPath_ = \get_class($model);
        }
        $this->modelName_ = 'Test';
        if ($isClassPath) {
            $this->modelName_ = $isClassPath ? array_reverse(explode('\\', $this->modelClassPath_))[0] : $this->modelClassPath_;
        }
        $name = Str::camelize(Pluralizer::singular($this->modelName_)).'ViewModel';
        if (empty($this->name())) {
            $this->setName($name);
        }

        return $this;
    }

    public function withHttpHandlers()
    {
        $this->hasHttpHandlers_ = true;

        return $this;
    }

    public function build()
    {
        // var_dump([$this->name()]);
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
                    'return '.PHPVariable('rules', null, $this->rules_ ?? [])->asRValue()->__toString()
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
                ->addImplementation(\Drewlabs\Contracts\Validator\ViewModel::class)
                ->addMethod(
                    PHPClassMethod(
                        'updateRules',
                        [],
                        'array<string,string|string[]>',
                        PHPTypesModifiers::PUBLIC,
                        'Returns a fluent validation rules applied during update actions'
                    )->addContents('return '.PHPVariable('rules', null, $this->updateRules_ ?? [])->asRValue()->__toString())
                );
        } else {
            /**
             * @var Blueprint
             */
            $component = $component
                ->addImplementation(\Drewlabs\Contracts\Validator\CoreValidatable::class);
        }
        // Add inputs traits
        if ($this->hasInputsTraits_) {
            if ($this->modelClassPath_ && $this->modelName_) {
                /**
                 * @var Blueprint
                 */
                $component = $component->addProperty(
                    PHPClassProperty(
                        'model_',
                        PHPTypes::STRING,
                        PHPTypesModifiers::PRIVATE,
                        $this->modelName_.'::class',
                        'Model class associated with the view model'
                    )
                );
                $component = $component->addClassPath($this->modelClassPath_);
            }
            /**
             * @var Blueprint
             */
            $component = $this->hasHttpHandlers_ ? $component->addTrait(\Drewlabs\Packages\Http\Traits\HttpViewModel::class) : $component->addTrait(\Drewlabs\Core\Validator\Traits\ViewModel::class);

            // Here we add the CreatesFilters trait to the view model
            /**
             * @var Blueprint
             */
            $component = $component->addTrait(\Drewlabs\Packages\Database\Traits\CreatesFilters::class);
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
        if (Str::contains($classname, '\\')) {
            return $classname;
        }

        return sprintf('%s%s%s', self::DEFAULT_NAMESPACE, '\\', $classname);
    }
}
