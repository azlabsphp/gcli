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

use function Drewlabs\CodeGenerator\Proxy\PHPClass;

use function Drewlabs\CodeGenerator\Proxy\PHPClassMethod;
use function Drewlabs\CodeGenerator\Proxy\PHPClassProperty;
use function Drewlabs\CodeGenerator\Proxy\PHPFunctionParameter;
use function Drewlabs\CodeGenerator\Proxy\PHPVariable;

use Drewlabs\CodeGenerator\Types\PHPTypes;
use Drewlabs\CodeGenerator\Types\PHPTypesModifiers;

use Drewlabs\Core\Helpers\Str;
use Drewlabs\GCli\Contracts\ViewModelBuilder as AbstractBuilder;
use Drewlabs\GCli\Factories\ComponentPath;

use Drewlabs\GCli\Plugins\Laravel\Traits\HasNamespaceAttribute;

use Drewlabs\GCli\Plugins\Laravel\Traits\ViewModelBuilder;

use function Drewlabs\GCli\Proxy\PHPScript;

use Illuminate\Support\Pluralizer;

final class ViewModelClassBuilder implements AbstractBuilder
{
    use HasNamespaceAttribute;
    use ViewModelBuilder;

    /**
     * Service default class namespace.
     *
     * @var string
     */
    public const DEFAULT_NAMESPACE = 'App\\ViewModels';

    /** @var string[] */
    public const HTTP_CLASS_PATHS = [
        'Drewlabs\\Contracts\\Validator\\ViewModel as AbstractViewModel',
        'Drewlabs\\Laravel\\Http\\Traits\\HttpViewModel as ViewModel',
        'Drewlabs\\Laravel\\Http\\Traits\\InteractsWithServerRequest',
        'Drewlabs\\Laravel\\Http\\Traits\\AuthorizeRequest',
        'Drewlabs\\Validation\\Traits\\ModelAware',
        'Drewlabs\\Validation\\Traits\\ProvidesRulesFactory',
        'Drewlabs\\Validation\\Traits\\Validatable',
        'Illuminate\\Http\\Request',
    ];

    /** @var string[] */
    public const HTTP_CLASS_TRAITS = [
        'ViewModel',
        'InteractsWithServerRequest',
        'AuthorizeRequest',
        'ModelAware',
        'ProvidesRulesFactory',
        'Validatable',
    ];

    /** @var string[] */
    public const DEFAULT_CLASS_PATHS = [
        'Drewlabs\\Contracts\\Validator\\ViewModel as AbstractViewModel',
        'Drewlabs\\Validation\\Traits\\FilesAttributesAware',
        'Drewlabs\\Validation\\Traits\\ModelAware',
        'Drewlabs\\Validation\\Traits\\ProvidesRulesFactory',
        'Drewlabs\\Validation\\Traits\\Validatable',
        'Drewlabs\\Validation\\Traits\\ViewModel',
    ];

    /** @var string[] */
    public const DEFAULT_CLASS_TRAITS = [
        'ViewModel',
        'ModelAware',
        'ProvidesRulesFactory',
        'Validatable',
        'FilesAttributesAware',
    ];

    /** @var string */
    private const DEFAULT_NAME = 'TestViewModel';

    /** @var string */
    private const DEFAULT_PATH = 'ViewModels';

    /** @var bool */
    private $supportHttp = false;

    /** @var string */
    private $modelPath;

    /** @var string */
    private $model;

    /** @var ?string */
    private $dtoPath;

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
        ?string $name = null,
        ?string $namespace = null,
        ?string $path = null
    ) {
        $this->setName($name ?
            (!Str::endsWith($name, 'ViewModel') ?
                Str::camelize(Pluralizer::singular($name)) . 'ViewModel' :
                Str::camelize(Pluralizer::singular($name))) :
            static::DEFAULT_NAME);
        // Set the component write path
        $this->setWritePath($path ?? static::DEFAULT_PATH);
        // Set the component namespace
        $this->setNamespace($namespace ?? static::DEFAULT_NAMESPACE);
    }

    /**
     * {@inheritDoc}
     * @param mixed $model 
     * @return static 
     */
    public function bindModel($model)
    {
        if (empty($model)) {
            return $this;
        }
        $path = \is_object($model) ? $model::class : (string) $model;
        $isPath = Str::contains($path, '\\');
        $this->modelPath = $isPath ? $path : $this->modelPath;
        $this->model = $isPath ? $this->getClassNameFromPath($path) : $path;
        $this->setName(Str::camelize(Pluralizer::singular($this->model)) . 'ViewModel');

        return $this;
    }

    public function withHttpHandlers()
    {
        $this->supportHttp = true;

        return $this;
    }

    public function withDto(string $path)
    {
        $this->dtoPath = $path;

        return $this;
    }

    public function build()
    {
        /** @var Blueprint */
        $component = PHPClass($this->name())
            ->asFinal()
            ->addToNamespace($this->namespace() ?? static::DEFAULT_NAMESPACE)
            ->addImplementation('AbstractViewModel')
            ->addMethod(PHPClassMethod(
                'rules',
                [],
                'array<string,string|string[]>',
                PHPTypesModifiers::PUBLIC,
                'Returns a fluent validation rules'
            )->addContents('return ' . PHPVariable('rules', null, $this->rules ?? [])->asRValue()->__toString()))
            ->addMethod(PHPClassMethod(
                'messages',
                [],
                'array<string,string|string[]>',
                PHPTypesModifiers::PUBLIC,
                'Returns a list of validation error messages'
            )->addContents('return []'));

        if (!$this->isSingleActionValidator) {
            /** @var Blueprint */
            $component = $component->addMethod(PHPClassMethod(
                'updateRules',
                [],
                'array<string,string|string[]>',
                PHPTypesModifiers::PUBLIC,
                'Returns a fluent validation rules applied during update actions'
            )->addContents('return ' . PHPVariable('rules', null, $this->updateRules ?? [])->asRValue()->__toString()));
        }
        // Add inputs traits
        if ($this->hasInputsTraits) {
            if ($this->modelPath && $this->model) {
                /**
                 * @var Blueprint
                 */
                $component = $component->addProperty(
                    PHPClassProperty(
                        'model',
                        PHPTypes::STRING,
                        PHPTypesModifiers::PRIVATE,
                        $this->model . '::class',
                        'model class name'
                    )
                )->addClassPath($this->modelPath)
                    ->addClassPath("Drewlabs\\Query\\PreparesFiltersBag")
                    ->addMethod(PHPClassMethod('makeFilters', [PHPFunctionParameter('defaults', 'array', '[]')], 'array', 'public', ['creates a list of filters based on view model input & query parameters'])
                        ->addLine("return PreparesFiltersBag::new(\$this)->call(new \$this->model, \$defaults ?? [])"));
            }

            #remove dependency this class in future release if not required
            if ($this->dtoPath) {
                /** @var Blueprint */
                $component = $component->addProperty(
                    PHPClassProperty(
                        'dtoclass',
                        PHPTypes::STRING,
                        PHPTypesModifiers::PRIVATE,
                        Str::afterLast('\\', $this->dtoPath) . '::class',
                        'data transfer object class name'
                    )
                );
                $component = $component->addClassPath($this->dtoPath);
            }
        }
        if ($this->supportHttp) {

            // #region Add class paths
            foreach (static::HTTP_CLASS_PATHS as $classPath) {
                $component = $component->addClassPath($classPath);
                // code...
            }
            // #endregion Add class paths

            // #region Add class traits
            foreach (static::HTTP_CLASS_TRAITS as $trait) {
                /** @var Blueprint */
                $component = $component->addTrait($trait);
                // code...
            }
            // #endregion Add class traits

            $component = $component->addConstructor(
                [PHPFunctionParameter('request', 'Request', null)->asOptional()],
                ['$this->bootInstance($request)']
            )->addMethod(
                PHPClassMethod('getModel', [], 'string', 'public', 'returns the model class')
                    ->addLine('return $this->model')
            )
                ->addMethod(
                    PHPClassMethod('getColumns', [], 'array', 'public', 'returns the list of queried columns')
                        ->addLine("\$columns = \$this->has('_columns') ? (is_array(\$columns = \$this->get('_columns')) ? \$columns : (@json_decode(\$columns, true) ?? ['*'])): ['*']")
                        ->addLine("return \\is_string(\$columns) ? \\explode(\$columns, ',') : (\$columns === false ? [] : \$columns)")
                )
                ->addMethod(
                    PHPClassMethod('getExcludes', [], 'array', 'public', 'returns the list of excluded columns')
                        ->addLine("\$columns = \$this->has('_hidden') ? (is_array(\$columns = \$this->get('_hidden')) ? \$columns : (@json_decode(\$columns, true) ?? ['*'])): ['*']")
                        ->addLine("return \\is_string(\$columns) ? \\explode(\$columns, ',') : (\$columns === false ? [] : \$columns)")
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
            )->addMethod(PHPClassMethod('getModel', [], 'string', 'public', 'returns the model class')->addLine('return $this->model'));
        }

        if ($this->dtoPath) {
            // @phpstan-ignore smaller.alwaysFalse
            if (intval(version_compare(\PHP_VERSION, '7.4')) < 0) {
                $component = $component->addMethod(
                    PHPClassMethod('useResourceBuilder', [PHPFunctionParameter('properties', 'array')], '\\' . \Closure::class, PHPTypesModifiers::PUBLIC)
                        ->addLine('return function ($value) use ($properties) {')
                        ->addLine(sprintf('return null !== $value ? %s::new($value)->addProperties($properties)->mergeHidden(array_merge($this->getExcludes(), $value->getHidden() ?? [])) : $value', $this->getClassNameFromPath($this->dtoPath)))
                        ->addLine('})')
                );
            }

            if (version_compare(\PHP_VERSION, '7.4') > 0) {
                $component = $component->addMethod(
                    PHPClassMethod('useResourceBuilder', [PHPFunctionParameter('properties', 'array')], '\\' . \Closure::class, PHPTypesModifiers::PUBLIC)
                        ->addLine(sprintf('return fn ($value) => null !== $value ? %s::new($value)->addProperties($properties)->mergeHidden(array_merge($this->getExcludes(), $value->getHidden() ?? [])) : $value', $this->getClassNameFromPath($this->dtoPath)))
                );
            }
        }

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

    private function getClassNameFromPath(string $name)
    {
        return array_reverse(explode('\\', $name))[0];
    }
}
