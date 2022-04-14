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
use function Drewlabs\CodeGenerator\Proxy\PHPFunctionParameter;
use Drewlabs\CodeGenerator\Types\PHPTypesModifiers;
use Drewlabs\ComponentGenerators\Contracts\ControllerBuilder as ContractsControllerBuilder;
use Drewlabs\ComponentGenerators\Helpers\ComponentBuilderHelpers;
use function Drewlabs\ComponentGenerators\Proxy\PHPScript;
use Drewlabs\ComponentGenerators\Traits\HasNamespaceAttribute;
use Drewlabs\Core\Helpers\Str;
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

    private const ACTION_FUNCTION_PATH = 'Drewlabs\\Support\\Proxy\\Action';

    private const USE_QUERY_RESULT_PROXY = 'Drewlabs\\Packages\\Database\\Proxy\\useMapQueryResult';

    private const DEFAULT_NAME = 'TestsController';

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
     * @var false
     */
    private $hasActionHandlerInterface_ = false;

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
     * Create an instance of the controller builder.
     *
     * @return self
     */
    public function __construct(
        ?string $name = null,
        ?string $namespace = null,
        ?string $path = null
    ) {
        if ($name) {
            $this->setName(sprintf('%s%s', drewlabs_core_strings_as_camel_case(Pluralizer::plural($name)), 'Controller'));
        }
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

    public function bindModel(string $value, $asViewModelClass = false)
    {
        if (empty($value)) {
            return $this;
        }
        // TODO: Makes this controller as a resource controller
        $this->asResourceController();
        // TODO : Generate and set the controller name
        $is_class_path = drewlabs_core_strings_contains($value, '\\'); // && class_exists($model); To uncomment if there is need to validate class existence
        if ($is_class_path) {
            $this->modelName_ = array_reverse(explode('\\', $value))[0];
            $this->classPaths_[] = $value;
        } else {
            $this->modelName_ = drewlabs_core_strings_contains($value, '\\') ? array_reverse(explode('\\', $value))[0] : $value;
        }
        $this->setName(sprintf('%s%s', drewlabs_core_strings_as_camel_case(Pluralizer::plural($this->modelName_)), 'Controller'));
        // TODO Set the view model class property if specified
        if ($is_class_path && $asViewModelClass) {
            $this->bindViewModel($value);
        }

        return $this;
    }

    public function bindViewModel(string $viewModelClass)
    {
        if (drewlabs_core_strings_contains($viewModelClass, '\\')) {
            $this->classPaths_[] = $viewModelClass;
            $this->viewModelClass_ = $this->getClassFromClassPath($viewModelClass);
        }

        return $this;
    }

    public function bindDTOObject(string $dtoClass)
    {
        if (drewlabs_core_strings_contains($dtoClass, '\\')) {
            $this->classPaths_[] = $dtoClass;
            $this->dtoClass_ = $this->getClassFromClassPath($dtoClass);
        }

        return $this;
    }

    public function bindService(string $serviceClass)
    {
        $clazz = null;
        if (!drewlabs_core_strings_contains($serviceClass, '\\')) {
            return $this;
        }
        $clazz = class_exists($serviceClass) ? new $serviceClass() : $clazz;
        $actionHandlerInterface = \Drewlabs\Contracts\Support\Actions\ActionHandler::class;
        if ((null !== $clazz) &&
            interface_exists($actionHandlerInterface) &&
            ($clazz instanceof \Drewlabs\Contracts\Support\Actions\ActionHandler)
        ) {
            $this->hasActionHandlerInterface_ = true;
            $this->serviceClass_ = $this->getClassFromClassPath($serviceClass);
        } else {
            $this->serviceClass_ = $this->getClassFromClassPath($serviceClass);
        }
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

    public function build()
    {
        // Set the route name of the controller
        $this->setRouteName($this->name_ ?? self::DEFAULT_NAME);
        /**
         * @var Blueprint
         */
        $component = PHPClass($this->name_ ?? self::DEFAULT_NAME)
            ->asFinal()
            ->addConstructor(
                array_merge(
                    [
                        PHPFunctionParameter(
                            'validator',
                            \Drewlabs\Contracts\Validator\Validator::class,
                        ),
                        PHPFunctionParameter(
                            'response',
                            \Drewlabs\Packages\Http\Contracts\IActionResponseHandler::class,
                        ),
                    ],
                    // Add the service class as parameter to the constructor
                    (null !== $this->serviceClass_) || $this->hasActionHandlerInterface_ ? ($this->hasActionHandlerInterface_ ? [
                        PHPFunctionParameter(
                            'service',
                            \Drewlabs\Contracts\Support\Actions\ActionHandler::class,
                        )->asOptional(),
                    ] : [
                        PHPFunctionParameter(
                            'service',
                            $this->serviceClass_,
                        )->asOptional(),
                    ]) : [],
                ),
                array_merge([
                    '$this->validator = $validator',
                    '$this->response = $response',
                ], (null !== $this->serviceClass_) || $this->hasActionHandlerInterface_ ?
                    [
                        $this->hasActionHandlerInterface_ && (null !== $this->serviceClass_) ? "\$this->service = \$service ?? new $this->serviceClass_()" : '$this->service = $service',
                    ] : [])
            )
            ->addToNamespace($this->namespace_ ?? self::DEFAULT_NAMESPACE);
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
                    $this->hasActionHandlerInterface_ ? \Drewlabs\Contracts\Support\Actions\ActionHandler::class : $this->serviceClass_,
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
                    \Drewlabs\Packages\Http\Contracts\IActionResponseHandler::class,
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
                        '@Route /POST /' . $this->routeName_ . '/{id}',
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

    public static function defaultClassPath(?string $classname = null)
    {
        $classname = $classname ?? 'Test';
        if (drewlabs_core_strings_contains($classname, '\\')) {
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
                    '@Route /GET /' . $this->routeName_ . '[/{$id}]',
                ],
                'returns' => 'mixed',
                'contents' => array_merge(
                    [
                        $this->hasAuthenticatable_ ? '// TODO : Provides policy handlers' : null,
                    ],
                    $this->isResourceController_ ? array_merge([
                        (null !== $this->modelName_) ? "\$filters = drewlabs_databse_parse_client_request_query(new $this->modelName_, \${$vmParamName})" : null,
                        '',
                        '$result = $this->service->handle(', // \t
                        "\tAction([",
                        "\t\t'type' => 'SELECT',",
                        "\t\t'payload' => \${$vmParamName}->has('per_page') ? [",
                        "\t\t\t\$filters,",
                        "\t\t\t(int)\${$vmParamName}->get('per_page'),",
                        "\t\t\t\${$vmParamName}->has('_columns') ? (is_array(\$colums_ = \${$vmParamName}->get('_columns')) ? \$colums_ : (@json_decode(\$colums_, true) ?? ['*'])): ['*'],",
                        "\t\t\t\${$vmParamName}->has('page') ? (int)\${$vmParamName}->get('page') : null,",
                        "\t\t] :",
                        "\t\t\t[",
                        "\t\t\t\t\$filters,",
                        "\t\t\t\t\${$vmParamName}->has('_columns') ? (is_array(\$colums_ = \${$vmParamName}->get('_columns')) ? \$colums_ : (@json_decode(\$colums_, true) ?? ['*']))  : ['*'],",
                        "\t\t\t],",

                    ], $this->dtoClass_ ? [
                        "\t]),",
                        "\tuseMapQueryResult(function (\$value)  use (\$$vmParamName) {",
                        "\t\treturn \$value ? (new $this->dtoClass_(\$value))->mergeHidden(\${$vmParamName}->get('_hidden') ?? []) : \$value;",
                        "\t})",
                        ")",
                    ] : [']))'], ['return $this->response->ok($result)',]) : []
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
                    '@Route /GET /' . $this->routeName_ . '/{$id}',
                ],
                'returns' => 'mixed',
                'contents' => array_merge(
                    [
                        $this->hasAuthenticatable_ ? '// TODO: Provide Policy handlers if required' : null,
                        '$result = $this->service->handle(Action([',
                        "\t'type' => 'SELECT',",
                        "\t'payload' => [",
                        "\t\t\$id,",
                        "\t\t\${$vmParamName}->has('_columns') ? (is_array(\$colums_ = \${$vmParamName}->get('_columns')) ? \$colums_ : (@json_decode(\$colums_, true) ?? ['*'])): ['*'],",
                        "\t],",
                    ],
                    null !== $this->dtoClass_ ? [
                        "]), function(\$value) use (\$$vmParamName) {",
                        "\treturn null !== \$value ? (new $this->dtoClass_(\$value))->mergeHidden(\${$vmParamName}->get('_hidden') ?? []) : \$value",
                        '});',
                    ] : [']))'],
                    [
                        'return $this->response->ok($result)',
                    ]
                ),
            ],
            [
                'name' => 'store',
                'params' => array_filter([
                    null === $this->viewModelClass_ ? PHPFunctionParameter('request', Request::class) : null,
                    null === $this->viewModelClass_ ? null : PHPFunctionParameter('viewModel', $this->viewModelClass_)
                ]),
                'descriptors' => [
                    'Stores a new item in the storage',
                    '@Route /POST /' . $this->routeName_,
                ],
                'returns' => 'mixed',
                'contents' => array_merge(
                    [
                        '// validate request inputs',
                    ],
                    null === $this->viewModelClass_ ? [
                        "\$result = \$this->validator->validate([], \$request->all(), function() use (\$request) {",
                        '// After validation logic goes here...',
                    ] : [
                        '$result = $this->validator->validate($viewModel, function() use ($viewModel) {',
                    ],
                    [
                        null === $this->viewModelClass_ ? "\t\$query = \$request->get('_query') ?? []" : "\t\$query = \$viewModel->get('_query') ?? []",
                        "\treturn \$this->service->handle(Action([",
                        "\t\t'type' => 'CREATE',",
                    ],
                    null === $this->viewModelClass_ ? [
                        "\t\t'payload' => [",
                        "\t\t\t\$request->all(),",
                        "\t\t\t\$request->has('id') ?",
                        "\t\t\t\t[",
                        "\t\t\t\t\t'method' => \$query['method'] ?? null,",
                        "\t\t\t\t\t'upsert_conditions' => \$query['upsert_conditions'] ?? [",
                        "\t\t\t\t\t\t\'id' => \$request->get('id'),",
                    ] : [
                        "\t\t'payload' => [",
                        "\t\t\t\$viewModel->all(),",
                        "\t\t\t[",
                        "\t\t\t\t'method' => \$query['method'] ?? null,",
                        "\t\t\t\t'upsert_conditions' => \$query['upsert_conditions'] ?? (\$viewModel->has(\$viewModel->getPrimaryKey()) ?",
                        "\t\t\t\t\t[\$viewModel->getPrimaryKey() => \$viewModel->get(\$viewModel->getPrimaryKey()),] : []),",
                    ],
                    [
                        "\t\t\t],",
                        "\t\t],",
                    ],
                    null !== $this->dtoClass_ ? [
                        "\t]), function(\$value) {",
                        "\t\treturn null !== \$value ? new $this->dtoClass_(\$value) : \$value",
                        "\t});",
                    ] : ["\t]))"],
                    [
                        '});',
                        '',
                        'return $this->response->ok($result)',
                    ]
                ),
            ], [
                'name' => 'update',
                'params' => array_filter([
                    null === $this->viewModelClass_ ? PHPFunctionParameter('request', Request::class) : null,
                    null === $this->viewModelClass_ ? null : PHPFunctionParameter('viewModel', $this->viewModelClass_),
                    PHPFunctionParameter('id', null),
                ]),
                'descriptors' => [
                    'Update the specified resource in storage.',
                    '@Route /PUT /' . $this->routeName_ . '/{id}',
                    '@Route /PATCH /' . $this->routeName_ . '/{id}',
                ],
                'returns' => 'mixed',
                'contents' => array_merge(
                    [
                        '$viewModel = $viewModel->merge(["id" => $id])',
                    ],
                    null === $this->viewModelClass_ ? [
                        "\$result = \$this->validator->updating()->validate([], \$request->all(), function() use (\$id, \$request) {",
                        '// After validation logic goes here...',
                    ] : [
                        '$result = $this->validator->updating()->validate($viewModel, function() use ($id, $viewModel) {',
                    ],
                    [
                        null === $this->viewModelClass_ ? "\t\$query = \$request->get('_query') ?? []" : "\t\$query = \$viewModel->get('_query') ?? []",
                        "\treturn \$this->service->handle(Action([",
                        "\t\t'type' => 'UPDATE',",
                    ],
                    null === $this->viewModelClass_ ? [
                        "\t\t'payload' => [",
                        "\t\t\t\$id,",
                        "\t\t\t\$request->all(),",
                        "\t\t\t[ 'method' => \$query['method'] ?? null, ]",
                    ] : [
                        "\t\t'payload' => [",
                        "\t\t\t\$id,",
                        "\t\t\t\$viewModel->all(),",
                        "\t\t\t['method' => \$query['method'] ?? null]",
                    ],
                    [
                        "\t\t],",
                    ],
                    null !== $this->dtoClass_ ? [
                        "\t]), function(\$value) {",
                        "\t\treturn null !== \$value ? new $this->dtoClass_(\$value) : \$value",
                        "\t});",
                    ] : ["\t]))"],
                    [
                        '});',
                        '',
                        'return $this->response->ok($result)',
                    ]
                ),
            ],
            [
                'name' => 'destroy',
                'params' => [
                    null === $this->viewModelClass_ ? PHPFunctionParameter($vmParamName, Request::class) : PHPFunctionParameter($vmParamName, $this->viewModelClass_),
                    (PHPFunctionParameter('id', null)),
                ],
                'descriptors' => [
                    'Remove the specified resource from storage.',
                    '@Route /DELETE /' . $this->routeName_ . '/{id}',
                ],
                'returns' => 'mixed',
                'contents' => [
                    $this->hasAuthenticatable_ ? '// TODO: Provide Policy handlers if required' : null,
                    '$result = $this->service->handle(Action([',
                    "\t'type' => 'DELETE',",
                    "\t'payload' => [\$id],",
                    ']))',
                    'return $this->response->ok($result)',
                ],
            ],
        ];
        $component = array_reduce(
            $actions,
            static function (Blueprint $carry, $action) {
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
            },
            $component
                ->addFunctionPath(self::ACTION_FUNCTION_PATH)
        );

        // Returns the component back to the caller
        return $component;
    }
}
