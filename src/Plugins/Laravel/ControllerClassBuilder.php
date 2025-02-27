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
use function Drewlabs\CodeGenerator\Proxy\PHPConstructorParameter;
use function Drewlabs\CodeGenerator\Proxy\PHPFunctionParameter;

use Drewlabs\CodeGenerator\Types\PHPTypesModifiers;
use Drewlabs\Contracts\Support\Actions\ActionHandler;
use Drewlabs\Core\Helpers\Str;
use Drewlabs\GCli\Contracts\ControllerBuilder as AbstractBuilder;
use Drewlabs\GCli\Factories\ComponentPath;
use Drewlabs\GCli\Factories\RouteName;

use Drewlabs\GCli\Plugins\Laravel\Traits\HasNamespaceAttribute;

use function Drewlabs\GCli\Proxy\PHPScript;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Pluralizer;

class ControllerClassBuilder implements AbstractBuilder
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

    /** @var string[] */
    private const DATABASE_ACTIONS_PATH = [
        'Drewlabs\\Laravel\\Query\\Proxy\\CreateQueryAction',
        'Drewlabs\\Laravel\\Query\\Proxy\\SelectQueryAction',
        'Drewlabs\\Laravel\\Query\\Proxy\\UpdateQueryAction',
        'Drewlabs\\Laravel\\Query\\Proxy\\DeleteQueryAction',
    ];

    /** @var string */
    private const USE_QUERY_RESULT_PROXY = 'Drewlabs\\Laravel\\Query\\Proxy\\useMapQueryResult';

    /** @var string
     */
    private const DEFAULT_NAME = 'TestsController';

    /** @var string */
    private const DEFAULT_PATH = 'Http/Controllers/';

    /** @var bool */
    private $providesActions = false;

    /** @var bool */
    private $invokable = false;

    /** @var string */
    private $viewModelName;

    /** @var string */
    private $dtoName;

    /** @var string */
    private $serviceName;

    /** @var string|null */
    private $serviceType;

    /**
     * List of classes to imports.
     *
     * @var array
     */
    private $classPaths = [];

    /** @var string */
    private $modelName = 'Test';

    /**
     * Route name for various resources actions.
     *
     * @var string
     */
    private $routeName;

    /**
     * Indicates whether the controller should provide authenticatable handlers.
     *
     * @var bool
     */
    private $supportAuth = true;

    /** @var bool */
    private $policies = false;

    /** @var string */
    private $primaryKey = 'id';

    /**
     * Create an instance of the controller builder.
     *
     * @return self
     */
    public function __construct(
        ?string $name = null,
        ?string $namespace = null,
        ?string $path = null
    ) {
        $this->setName($name ? (!Str::endsWith($name, 'Controller') ? Str::camelize(Pluralizer::plural($name)) . 'Controller' : Str::camelize(Pluralizer::plural($name))) : self::DEFAULT_NAME);
        // Set the component write path
        $this->setWritePath($path ?? self::DEFAULT_PATH);

        // Set the component namespace
        $this->setNamespace($namespace ?? self::DEFAULT_NAMESPACE);
    }

    public function asResourceController()
    {
        $this->providesActions = true;

        return $this;
    }

    public function asInvokableController()
    {
        $this->invokable = true;

        return $this;
    }

    public function bindModel(string $value, $asvm = false)
    {
        if (empty($value)) {
            return $this;
        }
        $this->asResourceController();
        $isClassPath = Str::contains($value, '\\'); // && class_exists($model); To uncomment if there is need to validate class existence
        $this->modelName = $isClassPath ? array_reverse(explode('\\', $value))[0] : (Str::contains($value, '\\') ? array_reverse(explode('\\', $value))[0] : $value);
        $this->setName(sprintf('%s%s', Str::camelize(Pluralizer::plural($this->modelName)), 'Controller'));
        if ($isClassPath && $asvm) {
            $this->bindViewModel($value);
        }

        return $this;
    }

    public function bindViewModel(string $viewModelClass)
    {
        if (Str::contains($viewModelClass, '\\')) {
            $this->classPaths[] = $viewModelClass;
            $this->viewModelName = $this->getClassFromClassPath($viewModelClass);
        }

        return $this;
    }

    public function bindDTOObject(string $dtoClass)
    {
        if (Str::contains($dtoClass, '\\')) {
            //#TODO: remove the line below in future release as dto class is not needed anymore, it job is handled by the view model
            // $this->classPaths[] = $dtoClass;
            $this->dtoName = $this->getClassFromClassPath($dtoClass);
        }

        return $this;
    }

    public function bindService(string $serviceClass, ?string $type = null)
    {
        if (!Str::contains($serviceClass, '\\')) {
            return $this;
        }
        $this->serviceType = $type ?? (interface_exists(ActionHandler::class) ? ActionHandler::class : $this->serviceType);
        $this->serviceName = $this->getClassFromClassPath($serviceClass);
        //#TODO : remove line below in future release as using PHP 7.4 property promotion, service class is no more required
        // $this->classPaths[] = $serviceClass;

        return $this;
    }

    public function routeName()
    {
        return $this->routeName;
    }

    public function withoutAuthenticatable()
    {
        $this->supportAuth = false;

        return $this;
    }

    /**
     * Set the name for the primary key object.
     *
     * @return $this
     */
    public function withPrimaryKey(string $key)
    {
        $this->primaryKey = $key;

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
        /** @var Blueprint */
        $component = PHPClass($this->name())
            ->withPromotedProperties()
            ->asFinal()
            ->addConstructor(
                array_merge(
                    [
                        PHPConstructorParameter('validator', \Drewlabs\Contracts\Validator\Validator::class)->setVisibility(PHPTypesModifiers::PRIVATE),
                        PHPConstructorParameter('response', 'OkResponseFactoryInterface')->setVisibility(PHPTypesModifiers::PRIVATE),
                    ],
                    // Add the service class as parameter to the constructor
                    (null !== $this->serviceName) || (null !== $this->serviceType) ? ($this->serviceType ? [
                        PHPConstructorParameter('service', $this->serviceType)->setVisibility(PHPTypesModifiers::PRIVATE),
                    ] : [
                        PHPConstructorParameter('service', $this->serviceName)->setVisibility(PHPTypesModifiers::PRIVATE),
                    ]) : [],
                )
            )
            ->addToNamespace($this->namespace_ ?? self::DEFAULT_NAMESPACE);

        $classPaths = static::CLASS_PATHS;

        // Remove custom property import
        if (is_null($this->dtoName)) {
            $classPaths = array_filter($classPaths,  function($p) {
                return $p !== 'Drewlabs\\PHPValue\\Utils\\SanitizeCustomProperties';
            });
        }

        // #region Add class paths
        foreach ($classPaths as $classPath) {
            $component = $component->addClassPath($classPath);
        }
        // #endregion Add class paths

        if ($this->dtoName) {
            /** @var Blueprint */
            $component = $component->addFunctionPath(self::USE_QUERY_RESULT_PROXY);
        }

        foreach ($this->classPaths ?? [] as $value) {
            /** @var Blueprint */
            $component = $component->addClassPath($value);
        }

        if ($this->serviceName) {
            $component = $component->addProperty(
                PHPClassProperty(
                    'service',
                    $this->serviceType ?? $this->serviceName,
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
        if ($this->providesActions) {
            $component = $this->addResourcesActions($component);
        }

        // Add the __invokeMethod if the controller is invokable
        if ($this->invokable) {
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
                        '@Route /POST /' . $this->routeName . '/{id}',
                    ]
                )
            );
        }
        // Returns the builded component

        return PHPScript(
            $component->getName(),
            $component,
            ComponentPath::new()->create($this->namespace_ ?? self::DEFAULT_NAMESPACE, $this->path_ ?? self::DEFAULT_PATH)
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

    private function setRouteName(string $classname)
    {
        $this->routeName = RouteName::new()->createRouteName($classname ?? '');

        return $this;
    }

    private function getClassFromClassPath(string $classPath)
    {
        $list = explode('\\', $classPath);

        return array_reverse(array_values($list))[0];
    }

    private function addResourcesActions(Blueprint $component)
    {
        $vmParamName = null === $this->viewModelName ? 'request' : 'view';
        $actions = [
            [
                'name' => 'index',
                'params' => [
                    null === $this->viewModelName ? PHPFunctionParameter($vmParamName, Request::class) : PHPFunctionParameter($vmParamName, $this->viewModelName),
                ],
                'descriptors' => [
                    'Display or Returns a list of items',
                    '@Route /GET /' . $this->routeName . '[/{$id}]',
                ],
                'returns' => 'mixed',
                'contents' => array_merge(
                    $this->supportAuth && $this->policies && (null !== $this->viewModelName) ? [
                        sprintf("\$%s->authorize('viewAny',  [\$%s->getModel(), \$$vmParamName])", $vmParamName, $vmParamName),
                        '',
                    ] : [],
                    $this->mustGenerateActionContents() ? array_merge(
                        [
                            sprintf('$columns = $%s->getColumns()', $vmParamName),
                            sprintf("\$query = \$%s->has('per_page') ? SelectQueryAction(\$%s->makeFilters(), (int)\$%s->get('per_page'), \$columns, \$%s->has('page') ? (int)\$%s->get('page') : null) :  SelectQueryAction(\$%s->makeFilters(), \$columns)", ...array_fill(0, 6, $vmParamName)),
                        ],
                        (bool) $this->dtoName ? [
                            sprintf("\$result = \$this->service->handle(\$query, useMapQueryResult(\$%s->useResourceBuilder((new SanitizeCustomProperties(true))(\$columns))))", $vmParamName),
                        ] : ['$result = $this->service->handle($query)'],
                        ['', 'return $this->response->create($result)']
                    ) : []
                ),
            ],
            [
                'name' => 'show',
                'params' => [
                    null === $this->viewModelName ? PHPFunctionParameter($vmParamName, Request::class) : PHPFunctionParameter($vmParamName, $this->viewModelName),
                    PHPFunctionParameter('id', null),
                ],
                'descriptors' => [
                    'Display or Returns an item matching the specified id',
                    '@Route /GET /' . $this->routeName . '/{$id}',
                ],
                'returns' => 'mixed',
                'contents' => $this->mustGenerateActionContents() ? array_merge(
                    $this->supportAuth && $this->policies && (null !== $this->viewModelName) ? [
                        sprintf("\$%s->authorize('view', [\$%s->find(\$id), \$$vmParamName])", $vmParamName, $vmParamName),
                        '',
                    ] : [],
                    [
                        sprintf('$columns = $%s->getColumns()', $vmParamName),
                    ],
                    null !== $this->dtoName ? [
                        sprintf("\$result = \$this->service->handle(SelectQueryAction(\$id, \$columns), \$%s->useResourceBuilder((new SanitizeCustomProperties(true))(\$columns)))", $vmParamName),
                    ] : ['$result = $this->service->handle(SelectQueryAction($id, $columns))'],
                    [
                        '',
                        'return $this->response->create($result)',
                    ]
                ) : [],
            ],
            [
                'name' => 'store',
                'params' => array_filter([
                    null === $this->viewModelName ? PHPFunctionParameter('request', Request::class) : null,
                    null === $this->viewModelName ? null : PHPFunctionParameter('view', $this->viewModelName),
                ]),
                'descriptors' => [
                    'Stores a new item in the storage',
                    '@Route /POST /' . $this->routeName,
                ],
                'returns' => 'mixed',
                'contents' => $this->mustGenerateActionContents() ? array_merge(
                    $this->supportAuth && $this->policies && (null !== $this->viewModelName) ? [
                        sprintf("\$%s->authorize('create', [\$%s->getModel(), \$$vmParamName])", $vmParamName, $vmParamName),
                        '',
                    ] : [],
                    null === $this->viewModelName ? [
                        sprintf("\$result = \$this->validator->validate([], \$%s->all(), fn() => \$this->service->handle(CreateQueryAction(\$%s, [", ...array_fill(0, 2, $vmParamName)),
                    ] : [
                        sprintf("\$result = \$%s->validate(\$this->validator, fn() => \$this->service->handle(CreateQueryAction(\$%s, [", ...array_fill(0, 2, $vmParamName)),
                    ],
                    [
                        "\t// TODO: Uncomment the code below to support relations insertion",
                        sprintf("\t//'relations' => \$%s->input('_query.relations') ?? []", $vmParamName),
                        sprintf("\t'upsert_conditions' => \$%s->get('_query.upsert_conditions') ?? (\$%s->has(\"$this->primaryKey\") ? [\"$this->primaryKey\" => \$%s->input(\"$this->primaryKey\")] : []),", ...array_fill(0, 3, $vmParamName)),
                        // "\t\t\t",
                    ],
                    [
                        null === $this->dtoName ? "])))" : sprintf("]), \$%s->useResourceBuilder((new SanitizeCustomProperties(true))($%s->getColumns()))))", ...array_fill(0, 2, $vmParamName)),
                    ],
                    [
                        '',
                        'return $this->response->create($result)',
                    ]
                ) : [],
            ],
            [
                'name' => 'update',
                'params' => array_filter([
                    null === $this->viewModelName ? PHPFunctionParameter('request', Request::class) : null,
                    null === $this->viewModelName ? null : PHPFunctionParameter('view', $this->viewModelName),
                    PHPFunctionParameter('id', null),
                ]),
                'descriptors' => [
                    'Update the specified resource in storage.',
                    '@Route /PUT /' . $this->routeName . '/{id}',
                    '@Route /PATCH /' . $this->routeName . '/{id}',
                ],
                'returns' => 'mixed',
                'contents' => $this->mustGenerateActionContents() ? array_merge(
                    [
                        (null !== $this->viewModelName) ? null : '$request = $request->merge(["' . $this->primaryKey . '" => $id])',
                        '',
                    ],
                    $this->supportAuth && $this->policies && (null !== $this->viewModelName) ? [
                        sprintf("\$%s->authorize('update', [\$$vmParamName", $vmParamName) . ("->find(\$id), \$$vmParamName])"),
                        '',
                    ] : [],
                    null === $this->viewModelName ? [
                        sprintf("\$result = \$this->validator->updating()->validate([], \$%s->all(), fn() => \$this->service->handle(UpdateQueryAction(\$id, \$%s, [", ...array_fill(0, 2, $vmParamName)),
                    ] : [
                        sprintf("\$result = \$%s->merge([\"" . $this->primaryKey . "\" => \$id])->validate(\$this->validator->updating(), fn() => \$this->service->handle(UpdateQueryAction(\$id, \$%s, [", ...array_fill(0, 2, $vmParamName)),
                    ],
                    [
                        "\t// TODO: Uncomment the code below to support relations insertion",
                        sprintf("\t//'relations' => \$%s->get('_query.relations') ?? [],", ...array_fill(0, 1, $vmParamName)),
                    ],
                    [
                        null === $this->dtoName ? "])))" : sprintf("]), \$%s->useResourceBuilder((new SanitizeCustomProperties(true))($%s->getColumns()))))", ...array_fill(0, 2, $vmParamName)),
                    ],
                    [
                        '',
                        'return $this->response->create($result)',
                    ]
                ) : [],
            ],
            [
                'name' => 'destroy',
                'params' => [
                    null === $this->viewModelName ? PHPFunctionParameter($vmParamName, Request::class) : PHPFunctionParameter($vmParamName, $this->viewModelName),
                    PHPFunctionParameter('id', null),
                ],
                'descriptors' => [
                    'Remove the specified resource from storage.',
                    '@Route /DELETE /' . $this->routeName . '/{id}',
                ],
                'returns' => 'mixed',
                'contents' => $this->mustGenerateActionContents() ? [
                    $this->supportAuth && $this->policies && (null !== $this->viewModelName) ? sprintf("\$%s->authorize('delete', [\$$vmParamName", $vmParamName) . ("->find(\$id), \$$vmParamName])") : null,
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
                foreach (
                    array_filter($contents, static function ($item) {
                        return null !== $item;
                    }) as $value
                ) {
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
        return $this->providesActions && (null !== $this->modelName) && (null !== $this->serviceName);
    }
}
