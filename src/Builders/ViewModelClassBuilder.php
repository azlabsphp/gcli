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

use function Drewlabs\CodeGenerator\Proxy\PHPClass;

use function Drewlabs\CodeGenerator\Proxy\PHPClassMethod;
use function Drewlabs\CodeGenerator\Proxy\PHPClassProperty;
use function Drewlabs\CodeGenerator\Proxy\PHPFunctionParameter;
use function Drewlabs\CodeGenerator\Proxy\PHPVariable;

use Drewlabs\CodeGenerator\Types\PHPTypes;
use Drewlabs\CodeGenerator\Types\PHPTypesModifiers;

use Drewlabs\Core\Helpers\Str;
use Drewlabs\GCli\Contracts\ComponentBuilder;
use Drewlabs\GCli\Helpers\ComponentBuilderHelpers;

use function Drewlabs\GCli\Proxy\PHPScript;

use Drewlabs\GCli\Traits\HasNamespaceAttribute;
use Drewlabs\GCli\Traits\ViewModelBuilder;
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
    public const DEFAULT_NAMESPACE = 'App\\ViewModels';

    /**
     * @var string[]
     */
    public const HTTP_CLASS_PATHS = [
        'Drewlabs\\Contracts\\Validator\\ViewModel as AbstractViewModel',
        'Drewlabs\\Laravel\\Http\\Traits\\HttpViewModel as ViewModel',
        'Drewlabs\\Laravel\\Http\\Traits\\InteractsWithServerRequest',
        'Drewlabs\\Laravel\\Http\\Traits\\AuthorizeRequest',
        'Drewlabs\\Validation\\Traits\\ModelAware',
        'Drewlabs\\Validation\\Traits\\ProvidesRulesFactory',
        'Drewlabs\\Validation\\Traits\\Validatable',
        'Drewlabs\Laravel\\Query\Traits\CreatesFilters',
        'Illuminate\\Http\\Request',
    ];

    /**
     * @var string[]
     */
    public const HTTP_CLASS_TRAITS = [
        'ViewModel',
        'InteractsWithServerRequest',
        'AuthorizeRequest',
        'ModelAware',
        'ProvidesRulesFactory',
        'Validatable',
        'CreatesFilters',
    ];

    /**
     * @var string[]
     */
    public const DEFAULT_CLASS_PATHS = [
        'Drewlabs\\Contracts\\Validator\\ViewModel as AbstractViewModel',
        'Drewlabs\\Validation\\Traits\\FilesAttributesAware',
        'Drewlabs\\Validation\\Traits\\ModelAware',
        'Drewlabs\\Validation\\Traits\\ProvidesRulesFactory',
        'Drewlabs\\Validation\\Traits\\Validatable',
        'Drewlabs\Laravel\\Query\Traits\CreatesFilters',
        'Drewlabs\\Validation\\Traits\\ViewModel',
    ];

    /**
     * @var string[]
     */
    public const DEFAULT_CLASS_TRAITS = [
        'ViewModel',
        'ModelAware',
        'ProvidesRulesFactory',
        'Validatable',
        'CreatesFilters',
        'FilesAttributesAware',
    ];

    /**
     * @var string
     */
    private const DEFAULT_NAME = 'TestViewModel';

    /**
     * @var string
     */
    private const DEFAULT_PATH = 'ViewModels';

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

    /**
     * @var string
     */
    private $dtoclasspath;

    /**
     * Creates an instance of view model class.
     *
     * @param (string|null)|null $name
     * @param (string|null)|null $namespace
     * @param (string|null)|null $path
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \Exception
     *
     * @return void
     */
    public function __construct(
        string $name = null,
        string $namespace = null,
        string $path = null
    ) {
        $this->setName($name ?
            (!Str::endsWith($name, 'ViewModel') ?
                Str::camelize(Pluralizer::singular($name)).'ViewModel' :
                Str::camelize(Pluralizer::singular($name))) :
            self::DEFAULT_NAME);
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
        $isclasspath = Str::contains($model, '\\');
        $this->modelName_ = 'Test';
        if ($isclasspath) {
            $this->modelClassPath_ = $model;
        }
        if (\is_object($model)) {
            $this->modelClassPath_ = $model::class;
        }
        if ($isclasspath) {
            $this->modelName_ = $isclasspath ? array_reverse(explode('\\', $this->modelClassPath_))[0] : $this->modelClassPath_;
        }
        $this->setName(Str::camelize(Pluralizer::singular($this->modelName_)).'ViewModel');

        return $this;
    }

    public function withHttpHandlers()
    {
        $this->hasHttpHandlers_ = true;

        return $this;
    }

    public function setDTOClassPath(string $classname)
    {
        $this->dtoclasspath = $classname;

        return $this;
    }

    public function build()
    {
        /**
         * @var BluePrint|PHPClass
         */
        $component = PHPClass($this->name())
            ->asFinal()
            ->addToNamespace($this->namespace() ?? self::DEFAULT_NAMESPACE)
            ->addImplementation('AbstractViewModel')
            ->addMethod(PHPClassMethod(
                'rules',
                [],
                'array<string,string|string[]>',
                PHPTypesModifiers::PUBLIC,
                'Returns a fluent validation rules'
            )->addContents('return '.PHPVariable('rules', null, $this->rules ?? [])->asRValue()->__toString()))
            ->addMethod(PHPClassMethod(
                'messages',
                [],
                'array<string,string|string[]>',
                PHPTypesModifiers::PUBLIC,
                'Returns a list of validation error messages'
            )->addContents('return []'));

        if (!$this->isSingleActionValidator) {
            /**
             * @var BluePrint|PHPClass
             */
            $component = $component->addMethod(PHPClassMethod(
                'updateRules',
                [],
                'array<string,string|string[]>',
                PHPTypesModifiers::PUBLIC,
                'Returns a fluent validation rules applied during update actions'
            )->addContents('return '.PHPVariable('rules', null, $this->updateRules ?? [])->asRValue()->__toString()));
        }
        // Add inputs traits
        if ($this->hasInputsTraits) {
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

            if ($this->dtoclasspath) {
                /**
                 * @var Blueprint
                 */
                $component = $component->addProperty(
                    PHPClassProperty(
                        'dtoclass_',
                        PHPTypes::STRING,
                        PHPTypesModifiers::PRIVATE,
                        Str::afterLast('\\', $this->dtoclasspath).'::class',
                        'Data transfer class associated with the view model'
                    )
                );
                $component = $component->addClassPath($this->dtoclasspath);
            }
        }
        if ($this->hasHttpHandlers_) {

            // #region Add class paths
            foreach (static::HTTP_CLASS_PATHS as $classPath) {
                $component = $component->addClassPath($classPath);
                // code...
            }
            // #endregion Add class paths

            // #region Add class traits
            foreach (static::HTTP_CLASS_TRAITS as $trait) {
                /**
                 * @var Blueprint
                 */
                $component = $component->addTrait($trait);
                // code...
            }
            // #endregion Add class traits

            $component = $component->addConstructor(
                [PHPFunctionParameter('request', 'Request', null)->asOptional()],
                ['$this->bootInstance($request)']
            )->addMethod(
                PHPClassMethod('getModel', [], 'string', 'public', 'returns the model class')
                    ->addLine('return $this->model_')
            )
                ->addMethod(
                    PHPClassMethod('getColumns', [], 'array', 'public', 'returns the list of queried columns')
                        ->addLine("return \$this->has('_columns') ? (is_array(\$columns = \$this->get('_columns')) ? \$columns : (@json_decode(\$columns, true) ?? ['*'])): ['*']")
                )
                ->addMethod(
                    PHPClassMethod('getExcludes', [], 'array', 'public', 'returns the list of excluded columns')
                        ->addLine("return \$this->has('_hidden') ? (is_array(\$columns = \$this->get('_hidden')) ? \$columns : (@json_decode(\$columns, true) ?? ['*'])): ['*']")
                );
        } else {

            // #region Add class paths
            foreach (static::DEFAULT_CLASS_PATHS as $classPath) {
                $component = $component->addClassPath($classPath);
                // code...
            }
            // #endregion Add class paths

            // #region Add class traits
            foreach (static::DEFAULT_CLASS_TRAITS as $trait) {
                $component = $component->addTrait($trait);
                // code...
            }
            // #endregion Add class traits
            $component = $component->addConstructor(
                [PHPFunctionParameter('inputs', 'array', [])->asOptional(), PHPFunctionParameter('files', 'array', [])->asOptional()],
                ['$this->set($inputs)', '$this->files($files)']
            )->addMethod(PHPClassMethod('getModel', [], 'string', 'public', 'returns the model class')->addLine('return $this->model_'));
        }
        // Returns the builded component
        return PHPScript(
            $component->getName(),
            $component,
            ComponentBuilderHelpers::rebuildComponentPath($this->namespace_ ?? self::DEFAULT_NAMESPACE, $this->path_ ?? self::DEFAULT_PATH)
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
}
