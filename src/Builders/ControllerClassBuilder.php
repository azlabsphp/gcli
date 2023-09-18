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

use Drewlabs\CodeGenerator\Types\PHPTypesModifiers;
use Drewlabs\Contracts\Support\Actions\ActionHandler;
use Drewlabs\Core\Helpers\Str;
use Drewlabs\GCli\Contracts\ControllerBuilder as ContractsControllerBuilder;
use Drewlabs\GCli\Helpers\ComponentBuilderHelpers;

use function Drewlabs\GCli\Proxy\PHPScript;

use Drewlabs\GCli\Traits\HasNamespaceAttribute;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Pluralizer;

class ControllerClassBuilder implements ContractsControllerBuilder
{
    use HasNamespaceAttribute;

    /**
     * Controller class namespace.
     *
     * @var string
     */
    public const DEFAULT_NAMESPACE = 'App\\Http\\Controllers';

    public const CLASS_PATHS = [
        'Drewlabs\\Http\\Factory\\OkResponseFactoryInterface',
        'Drewlabs\\PHPValue\\Utils\\SanitizeCustomProperties',
    ];

    /**
     * @var string[]
     */
    private const DATABASE_ACTIONS_PATH = [
        'Drewlabs\\Laravel\\Query\\Proxy\\CreateQueryAction',
        'Drewlabs\\Laravel\\Query\\Proxy\\SelectQueryAction',
        'Drewlabs\\Laravel\\Query\\Proxy\\UpdateQueryAction',
        'Drewlabs\\Laravel\\Query\\Proxy\\DeleteQueryAction',
    ];

    /**
     * @var string
     */
    private const USE_QUERY_RESULT_PROXY = 'Drewlabs\\Laravel\\Query\\Proxy\\useMapQueryResult';

    /**
     * @var string
     */
    private const DEFAULT_NAME = 'TestsController';

    /**
     * @var string
     */
    private const DEFAULT_PATH = 'Http/Controllers/';

    /**
     * @var bool
     */
    private $isResourceController_ = false;

    /**
     * @var bool
     */
    private $isInvokable_ = false;

    /**
     * @var string
     */
    private $viewModelClass_;

    /**
     * @var string
     */
    private $dtoClass_;

    /**
     * @var string
     */
    private $serviceClass_;

    /**
     * @var string|null
     */
    private $serviceType;

    /**
     * List of classes to imports.
     *
     * @var array
     */
    private $classPaths_ = [];

    /**
     * @var string
     */
    private $modelName_ = 'Test';

    /**
     * Route name for various resources actions.
     *
     * @var string
     */
    private $routeName_;

    /**
     * Indicates whether the controller should provide authenticatable handlers.
     *
     * @var bool
     */
    private $hasAuthenticatable_ = true;

    /**
     * @var bool
     */
    private $policies = false;

    /**
     * Create an instance of the controller builder.
     *
     * @return self
     */
    public function __construct(
        string $name = null,
        string $namespace = null,
        string $path = null
    ) {
        $this->setName($name ? (!Str::endsWith($name, 'Controller') ?
            Str::camelize(Pluralizer::plural($name)).'Controller' :
            Str::camelize(Pluralizer::plural($name))) : self::DEFAULT_NAME);
        // Set the component write path
        $this->setWritePath($path ?? self::DEFAULT_PATH);

        // Set the component namespace
        $this->setNamespace($namespace ?? self::DEFAULT_NAMESPACE);
    }

    public function asResourceController()
    {
        $this->isResourceController_ = true;

        return $this;
    }

    public function asInvokableController()
    {
        $this->isInvokable_ = true;

        return $this;
    }

    public function bindModel(string $value, $asvm = false)
    {
        if (empty($value)) {
            return $this;
        }
        $this->asResourceController();
        $isClassPath = Str::contains($value, '\\'); // && class_exists($model); To uncomment if there is need to validate class existence
        $this->modelName_ = $isClassPath ? array_reverse(explode('\\', $value))[0] : (Str::contains($value, '\\') ? array_reverse(explode('\\', $value))[0] : $value);
        $this->setName(sprintf('%s%s', Str::camelize(Pluralizer::plural($this->modelName_)), 'Controller'));
        if ($isClassPath && $asvm) {
            $this->bindViewModel($value);
        }

        return $this;
    }

    public function bindViewModel(string $viewModelClass)
    {
        if (Str::contains($viewModelClass, '\\')) {
            $this->classPaths_[] = $viewModelClass;
            $this->viewModelClass_ = $this->getClassFromClassPath($viewModelClass);
        }

        return $this;
    }

    public function bindDTOObject(string $dtoClass)
    {
        if (Str::contains($dtoClass, '\\')) {
            $this->classPaths_[] = $dtoClass;
            $this->dtoClass_ = $this->getClassFromClassPath($dtoClass);
        }

        return $this;
    }

    public function bindService(string $serviceClass, string $type = null)
    {
        if (!Str::contains($serviceClass, '\\')) {
            return $this;
        }
        $this->serviceType = $type ?? (interface_exists(ActionHandler::class) ? ActionHandler::class : $this->serviceType);
        $this->serviceClass_ = $this->getClassFromClassPath($serviceClass);
        $this->classPaths_[] = $serviceClass;

        return $this;
    }

    public function routeName()
    {
        return $this->routeName_;
    }

    public function withoutAuthenticatable()
    {
        $this->hasAuthenticatable_ = false;

        return $this;
    }

    /**
     * Makes the controller authorizable.
     *
     * @return self
     */
    public function authorizable()
    {
        $this->policies = true;

        return $this;
    }

    public function build()
    {
        // Set the route name of the controller
        $this->setRouteName($this->name());
        /**
         * @var Blueprint
         */
        $component = PHPClass($this->name())
            ->asFinal()
            ->addConstructor(
                array_merge(
                    [
                        PHPFunctionParameter('validator', \Drewlabs\Contracts\Validator\Validator::class),
                        PHPFunctionParameter('response', 'OkResponseFactoryInterface'),
                    ],
                    // Add the service class as parameter to the constructor
                    (null !== $this->serviceClass_) || (null !== $this->serviceType) ? ($this->serviceType ? [
                        PHPFunctionParameter('service', $this->serviceType)->asOptional(),
                    ] : [
                        PHPFunctionParameter('service', $this->serviceClass_),
                    ]) : [],
                ),
                array_merge(['$this->validator = $validator', '$this->response = $response'], (null !== $this->serviceClass_) || $this->serviceType ?
                    [(null !== $this->serviceType) && (null !== $this->serviceClass_) ? "\$this->service = \$service ?? new $this->serviceClass_()" : '$this->service = $service'] : [])
            )
            ->addToNamespace($this->namespace_ ?? self::DEFAULT_NAMESPACE);

        // #region Add class paths
        foreach (static::CLASS_PATHS as $classPath) {
            $component = $component->addClassPath($classPath);
            // code...
        }
        // #endregion Add class paths

        if ($this->dtoClass_) {
            /**
             * @var Blueprint
             */
            $component = $component->addFunctionPath(self::USE_QUERY_RESULT_PROXY);
        }
        foreach ($this->classPaths_ ?? [] as $value) {
            /**
             * @var Blueprint
             */
            $component = $component->addClassPath($value);
        }
        if ($this->serviceClass_) {
            $component = $component->addProperty(
                PHPClassProperty(
                    'service',
                    $this->serviceType ?? $this->serviceClass_,
                    PHPTypesModifiers::PRIVATE,
                    null,
                    'Injected instance of MVC service'
                )
            );
        }
        // Model associated table
        $component->addProperty(
            PHPClassProperty(
                'validator',
                \Drewlabs\Contracts\Validator\Validator::class,
                PHPTypesModifiers::PRIVATE,
                null,
                'Injected instance of the validator class'
            )
        )
            ->addProperty(
                PHPClassProperty(
                    'response',
                    'OkResponseFactoryInterface',
                    PHPTypesModifiers::PRIVATE,
                    null,
                    'Injected instance of the response handler class'
                )
            );
        // Add resources Actions if the class is a resources controller
        if ($this->isResourceController_) {
            $component = $this->addResourcesActions($component);
        }

        // Add the __invokeMethod if the controller is invokable
        if ($this->isInvokable_) {
            $component = $component->addMethod(
                PHPClassMethod(
                    '__invoke',
                    [
                        PHPFunctionParameter('request', Request::class),
                    ],
                    Response::class,
                    PHPTypesModifiers::PUBLIC,
                    [
                        'Handles http request action',
                        '@Route /POST /'.$this->routeName_.'/{id}',
                    ]
                )
            );
        }
        // Returns the builded component

        return PHPScript(
            $component->getName(),
            $component,
            ComponentBuilderHelpers::rebuildComponentPath(
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

    private function setRouteName(string $classname)
    {
        $this->routeName_ = ComponentBuilderHelpers::buildRouteName($classname ?? '');

        return $this;
    }

    private function getClassFromClassPath(string $classPath)
    {
        $list = explode('\\', $classPath);

        return array_reverse(array_values($list))[0];
    }

    private function addResourcesActions(Blueprint $component)
    {
        $vmParamName = null === $this->viewModelClass_ ? 'request' : 'viewModel';
        $actions = [
            [
                'name' => 'index',
                'params' => [
                    null === $this->viewModelClass_ ? PHPFunctionParameter($vmParamName, Request::class) : PHPFunctionParameter($vmParamName, $this->viewModelClass_),
                ],
                'descriptors' => [
                    'Display or Returns a list of items',
                    '@Route /GET /'.$this->routeName_.'[/{$id}]',
                ],
                'returns' => 'mixed',
                'contents' => array_merge(
                    $this->hasAuthenticatable_ && $this->policies && (null !== $this->viewModelClass_) ? [
                        sprintf("\$%s->authorize('viewAny',  [\$%s->getModel(), \$$vmParamName])", $vmParamName, $vmParamName),
                        '',
                    ] : [],
                    $this->mustGenerateActionContents() ? array_merge(
                        [
                            '//#region Excepts & attributes',
                            sprintf("\$columns = \$%s->getColumns()", $vmParamName),
                            sprintf("\$excepts = \$%s->getExcludes()", $vmParamName),
                            '$properties = (new SanitizeCustomProperties(true))($columns)',
                            '//#endregion Excepts & attributes',
                            '',
                            '$result = $this->service->handle(', // \t
                            sprintf("\t\$%s->has('per_page') ? SelectQueryAction(", $vmParamName),
                            "\t\t\$viewModel->makeFilters(),",
                            sprintf("\t\t(int)\$%s->get('per_page'),", $vmParamName),
                            "\t\t\$columns,",
                            sprintf("\t\t\$%s->has('page') ? (int)\$%s->get('page') : null,", $vmParamName, $vmParamName),
                            "\t) : SelectQueryAction(",
                            "\t\t\$viewModel->makeFilters(),",
                            "\t\t\$columns,",
                            "\t),",

                        ],
                        $this->dtoClass_ ? [
                            "\tuseMapQueryResult(function (\$value)  use (\$excepts, \$properties) {",
                            "\t\treturn \$value ? $this->dtoClass_::new(\$value)->addProperties(\$properties)->mergeHidden(array_merge(\$excepts, ".'$value->getHidden() ?? [])) : $value',
                            "\t})",
                            ')',
                        ] :
                            [')'],
                        ['return $this->response->create($result)']
                    ) : []
                ),
            ],
            [
                'name' => 'show',
                'params' => [
                    null === $this->viewModelClass_ ? PHPFunctionParameter($vmParamName, Request::class) : PHPFunctionParameter($vmParamName, $this->viewModelClass_),
                    PHPFunctionParameter('id', null),
                ],
                'descriptors' => [
                    'Display or Returns an item matching the specified id',
                    '@Route /GET /'.$this->routeName_.'/{$id}',
                ],
                'returns' => 'mixed',
                'contents' => $this->mustGenerateActionContents() ? array_merge(
                    $this->hasAuthenticatable_ && $this->policies && (null !== $this->viewModelClass_) ? [
                        sprintf("\$%s->authorize('view', [\$%s->find(\$id), \$$vmParamName])", $vmParamName, $vmParamName),
                        '',
                    ] : [],
                    [
                        '//#region Excepts & attributes',
                        sprintf("\$columns = \$%s->getColumns()", $vmParamName),
                        sprintf("\$excepts = \$%s->getExcludes()", $vmParamName),
                        '$properties = (new SanitizeCustomProperties(true))($columns)',
                        '//#endregion Excepts & attributes',
                        '',
                    ],
                    null !== $this->dtoClass_ ? [
                        '$result = $this->service->handle(',
                        "\tSelectQueryAction(\$id, \$columns),",
                        "\tfunction (\$value)  use (\$excepts, \$properties) {",
                        "\t\treturn null !== \$value ? $this->dtoClass_::new(\$value)->addProperties(\$properties)->mergeHidden(array_merge(\$excepts, ".'$value->getHidden() ?? [])) : $value',
                        "\t}",
                        ')',
                    ] : ['$result = $this->service->handle(SelectQueryAction($id, $columns)'],
                    [
                        'return $this->response->create($result)',
                    ]
                ) : [],
            ],
            [
                'name' => 'store',
                'params' => array_filter([
                    null === $this->viewModelClass_ ? PHPFunctionParameter('request', Request::class) : null,
                    null === $this->viewModelClass_ ? null : PHPFunctionParameter('viewModel', $this->viewModelClass_),
                ]),
                'descriptors' => [
                    'Stores a new item in the storage',
                    '@Route /POST /'.$this->routeName_,
                ],
                'returns' => 'mixed',
                'contents' => $this->mustGenerateActionContents() ? array_merge(
                    $this->hasAuthenticatable_ && $this->policies && (null !== $this->viewModelClass_) ? [
                        sprintf("\$%s->authorize('create', [\$%s->getModel(), \$$vmParamName])", $vmParamName, $vmParamName),
                        '',
                    ] : [],
                    null === $this->viewModelClass_ ? [
                        '$result = $this->validator->validate([], $request->all(), function () use ($request) {',
                        '// After validation logic goes here...',
                    ] : [
                        '$result = $viewModel->validate($this->validator, function () use ($viewModel) {',
                    ],
                    [
                        (null === $this->viewModelClass_) ? "\treturn \$this->service->handle(CreateQueryAction(\$request, [" : "\treturn \$this->service->handle(CreateQueryAction(\$viewModel, [",
                    ],
                    (null === $this->viewModelClass_) ? [
                        "\t\t// TODO: Uncomment the code below to support relations insertion",
                        "\t\t//'relations' => \$viewModel->get('_query.relations') ?? []",
                        "\t\t'upsert_conditions' => \$query['upsert_conditions'] ?? (\$request->has('id') ?",
                        "\t\t\t['id' => \$request->get('id'),] : []),",
                    ] : [
                        "\t\t// TODO: Uncomment the code below to support relations insertion",
                        "\t\t//'relations' => \$viewModel->get('_query.relations') ?? []",
                        "\t\t'upsert_conditions' => \$query['upsert_conditions'] ?? (\$viewModel->has(\$viewModel->getPrimaryKey()) ?",
                        "\t\t\t[\$viewModel->getPrimaryKey() => \$viewModel->get(\$viewModel->getPrimaryKey()),] : []),",
                    ],
                    [
                        null === $this->dtoClass_ ? "\t])," : "\t]), function (\$value) {",
                    ],
                    null !== $this->dtoClass_ ? [
                        "\t\treturn null !== \$value ? new $this->dtoClass_(\$value) : \$value",
                        "\t});",
                    ] : ["\t)"],
                    [
                        '});',
                        '',
                        'return $this->response->create($result)',
                    ]
                ) : [],
            ], [
                'name' => 'update',
                'params' => array_filter([
                    null === $this->viewModelClass_ ? PHPFunctionParameter('request', Request::class) : null,
                    null === $this->viewModelClass_ ? null : PHPFunctionParameter('viewModel', $this->viewModelClass_),
                    PHPFunctionParameter('id', null),
                ]),
                'descriptors' => [
                    'Update the specified resource in storage.',
                    '@Route /PUT /'.$this->routeName_.'/{id}',
                    '@Route /PATCH /'.$this->routeName_.'/{id}',
                ],
                'returns' => 'mixed',
                'contents' => $this->mustGenerateActionContents() ? array_merge(
                    [
                        (null !== $this->viewModelClass_) ? null : '$request = $request->merge(["id" => $id])',
                        '',
                    ],
                    $this->hasAuthenticatable_ && $this->policies && (null !== $this->viewModelClass_) ? [
                        sprintf("\$%s->authorize('update', [\$$vmParamName", $vmParamName).("->find(\$id), \$$vmParamName])"),
                        '',
                    ] : [],
                    null === $this->viewModelClass_ ? [
                        '$result = $this->validator->updating()->validate([], $request->all(), function () use ($id, $request) {',
                        '// After validation logic goes here...',
                    ] : [
                        '$result = $viewModel->merge(["id" => $id])->validate($this->validator->updating(), function () use ($id, $viewModel) {',
                    ],
                    [
                        (null === $this->viewModelClass_) ? "\treturn \$this->service->handle(UpdateQueryAction(\$id, \$request, [" : "\treturn \$this->service->handle(UpdateQueryAction(\$id, \$viewModel, [",
                    ],
                    [
                        "\t\t// TODO: Uncomment the code below to support relations insertion",
                        "\t\t//'relations' => \$viewModel->get('_query.relations') ?? [],",
                    ],
                    [
                        null === $this->dtoClass_ ? "\t\t])," : "\t]), function (\$value) {",
                    ],
                    null !== $this->dtoClass_ ? [
                        "\t\treturn null !== \$value ? new $this->dtoClass_(\$value) : \$value",
                        "\t});",
                    ] : ["\t)"],
                    [
                        '});',
                        '',
                        'return $this->response->create($result)',
                    ]
                ) : [],
            ],
            [
                'name' => 'destroy',
                'params' => [
                    null === $this->viewModelClass_ ? PHPFunctionParameter($vmParamName, Request::class) : PHPFunctionParameter($vmParamName, $this->viewModelClass_),
                    PHPFunctionParameter('id', null),
                ],
                'descriptors' => [
                    'Remove the specified resource from storage.',
                    '@Route /DELETE /'.$this->routeName_.'/{id}',
                ],
                'returns' => 'mixed',
                'contents' => $this->mustGenerateActionContents() ? [
                    $this->hasAuthenticatable_ && $this->policies && (null !== $this->viewModelClass_) ? sprintf("\$%s->authorize('delete', [\$$vmParamName", $vmParamName).("->find(\$id), \$$vmParamName])") : null,
                    '',
                    '$result = $this->service->handle(DeleteQueryAction($id))',
                    'return $this->response->create($result)',
                ] : [],
            ],
        ];

        foreach (static::DATABASE_ACTIONS_PATH as $functionPath) {
            $component = $component->addFunctionPath($functionPath);
        }
        $component = array_reduce($actions, static function (Blueprint $carry, $action) {
            $method = PHPClassMethod(
                $action['name'],
                $action['params'],
                $action['returns'] ?? null,
                PHPTypesModifiers::PUBLIC,
                $action['descriptors']
            );
            if ($contents = $action['contents'] ?? null) {
                $contents = \is_array($contents) ? $contents : [$contents];
                foreach (array_filter($contents, static function ($item) {
                    return null !== $item;
                }) as $value) {
                    // code...
                    $method = $method->addLine($value);
                }
            }
            $carry = $carry->addMethod($method);

            return $carry;
        }, $component);

        // Returns the component back to the caller
        return $component;
    }

    private function mustGenerateActionContents()
    {
        return $this->isResourceController_ && (null !== $this->modelName_) && (null !== $this->serviceClass_);
    }
}
