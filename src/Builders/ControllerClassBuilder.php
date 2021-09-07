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
use Drewlabs\CodeGenerator\Types\PHPTypesModifiers;
use Drewlabs\ComponentGenerators\Contracts\ControllerBuilder as ContractsControllerBuilder;
use Drewlabs\ComponentGenerators\Helpers\ComponentBuilderHelpers;
use Drewlabs\ComponentGenerators\Traits\HasNamespaceAttribute;
use Drewlabs\Contracts\Support\Actions\ActionHandler;
use Drewlabs\Contracts\Validator\Validator;
use Drewlabs\Core\Validator\Exceptions\ValidationException;
use Drewlabs\Packages\Http\Contracts\IActionResponseHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Pluralizer;

use function Drewlabs\CodeGenerator\Proxy\PHPClass;
use function Drewlabs\CodeGenerator\Proxy\PHPClassMethod;
use function Drewlabs\CodeGenerator\Proxy\PHPClassProperty;
use function Drewlabs\CodeGenerator\Proxy\PHPFunctionParameter;
use function Drewlabs\ComponentGenerators\Proxy\PHPScript;

class ControllerClassBuilder implements ContractsControllerBuilder
{
    use HasNamespaceAttribute;

    private const ACTION_FUNCTION_PATH = 'Drewlabs\\Support\\Proxy\\Action';

    private const DEFAULT_NAME = 'TestsController';

    private const DEFAULT_PATH = 'Http/Controllers/';

    /**
     * Controller class namespace.
     *
     * @var string
     */
    public const DEFAULT_NAMESPACE = 'App\\Http\\Controllers';

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
     * 
     * @var false
     */
    private $hasActionHandlerInterface_ = false;

    /**
     * List of classes to imports
     * 
     * @var array
     */
    private $classPaths_ = [];

    /**
     * 
     * @var string
     */
    private $modelName_ = 'Test';

    /**
     * Route name for various resources actions
     * 
     * @var string
     */
    private $routeName_;

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
            $this->setName($name);
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
        $this->setName(sprintf("%s%s", drewlabs_core_strings_as_camel_case(Pluralizer::plural($this->modelName_)), 'Controller'));
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

        $clazz = class_exists($serviceClass) ? new $serviceClass : $clazz;
        if ((null !== $clazz) && ($clazz instanceof ActionHandler)) {
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

    public function build()
    {
        // Set the route name of the controller
        $this->setRouteName($this->name_ ?? self::DEFAULT_NAME);
        /**
         * @var Blueprint
         */
        $component = PHPClass($this->name_ ?? self::DEFAULT_NAME)
            ->addConstructor(
                array_merge(
                    [
                        PHPFunctionParameter(
                            'validator',
                            Validator::class,
                        ),
                        PHPFunctionParameter(
                            'response',
                            IActionResponseHandler::class,
                        ),
                    ],
                    // Add the service class as parameter to the constructor
                    (null !== $this->serviceClass_) || $this->hasActionHandlerInterface_ ? ($this->hasActionHandlerInterface_ ? [
                        PHPFunctionParameter(
                            'service',
                            ActionHandler::class,
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
                    '$this->response = $response'
                ], (null !== $this->serviceClass_) || $this->hasActionHandlerInterface_ ?
                    [
                        $this->hasActionHandlerInterface_ && (null !== $this->serviceClass_) ? "\$this->service = \$service ?? new $this->serviceClass_()" : "\$this->service = \$service"
                    ] : [])
            )
            ->asFinal()
            ->addToNamespace($this->namespace_ ?? self::DEFAULT_NAMESPACE);
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
                    $this->hasActionHandlerInterface_ ? ActionHandler::class : $this->serviceClass_,
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
                Validator::class,
                PHPTypesModifiers::PRIVATE,
                null,
                'Injected instance of the validator class'
            )
        )
            ->addProperty(
                PHPClassProperty(
                    'response',
                    IActionResponseHandler::class,
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

    private function setRouteName(string $classname)
    {
        $this->routeName_ = ComponentBuilderHelpers::buildRouteName($classname ?? '');
        return $this;
    }

    private function getClassFromClassPath(string $classPath)
    {
        $list = explode("\\", $classPath);
        return array_reverse(array_values($list))[0];
    }

    private function addResourcesActions(Blueprint $component)
    {
        $validatable = null === $this->viewModelClass_ ? '[]' : sprintf("%s::class", $this->viewModelClass_);
        $actions = [
            [
                'name' => 'index',
                'params' => [
                    PHPFunctionParameter('request', Request::class),
                    PHPFunctionParameter('id', null, 'null')->asOptional(),
                ],
                'descriptors' => [
                    'Display or Returns a list of items',
                    '@Route /GET /' . $this->routeName_ . '[/{$id}]',
                ],
                'returns' => JsonResponse::class,
                'contents' => array_merge(
                    [
                        'if (!is_null($id)) {',
                        "\treturn \$this->show(\$request, \$id)",
                        '}',
                        "// TODO : Provides policy handlers",
                    ],
                    // Transformer part
                    $this->dtoClass_ ? [
                        "\$tranformFunc_ = function( \$items) {",
                        "\treturn map_query_result(\$items, function (\$value) {",
                        "\t\treturn \$value ? (new $this->dtoClass_)->withModel(\$value) : \$value",
                        "\t});",
                        "};"
                    ] : [],
                    $this->isResourceController_ ? [
                        (null !== $this->modelName_) ? "\$filters = drewlabs_databse_parse_client_request_query(new $this->modelName_, \$request);" : null,
                        "\$result = \$this->service->handle(Action([",
                        "\t'type' => 'SELECT',",
                        "\t'payload' => \$request->has('per_page') ? [\$filters, (int)\$request->get('per_page'), \$request->has('page') ? (int)\$request->get('page') : null] : [\$filters],",
                        $this->dtoClass_ ? "]), \$tranformFunc_)" : "]))",
                        "return \$this->response->ok(\$result)", //
                    ] : []

                ), // 
            ],
            [
                'name' => 'show',
                'params' => [
                    PHPFunctionParameter('request', Request::class),
                    PHPFunctionParameter('id', null),
                ],
                'descriptors' => [
                    'Display or Returns an item matching the specified id',
                    '@Route /GET /' . $this->routeName_ . '/{$id}',
                ],
                'returns' => JsonResponse::class,
                'contents' => array_merge(
                    [
                        "// TODO: Provide Policy handlers if required",
                        "\$result = \$this->service->handle(Action([",
                        "\t'type' => 'SELECT',",
                        "\t'payload' => [\$id],",
                    ],
                    null !== $this->dtoClass_ ? [
                        "]), function(\$value) {",
                        "\treturn null !== \$value ? new $this->dtoClass_(\$value->toArray()) : \$value",
                        "});"
                    ] : ["]))"],
                    [
                        "return \$this->response->ok(\$result)", //
                    ]
                ),
            ],
            [
                'name' => 'store',
                'params' => [
                    PHPFunctionParameter('request', Request::class),
                ],
                'descriptors' => [
                    'Stores a new item in the storage',
                    '@Route /POST /' . $this->routeName_,
                ],
                'returns' => JsonResponse::class,
                'contents' => array_merge(
                    [
                        'try {',
                        "\t// validate request inputs",
                    ],
                    $validatable === '[]' ? [
                        "\t\$result = \$this->validator->validate($validatable, \$request->all(), function() use (\$request) {",
                        "\t// After validation logic goes here...",
                    ] : [
                        "\t\$viewModel_ = (new " . drewlabs_core_strings_replace('::class', '', $validatable) . ")->setUser(\$request->user())->set(\$request->all())->files(\$request->allFiles())",
                        "",
                        "\t\$result = \$this->validator->validate(\$viewModel_, \$request->all(), function() use (\$viewModel_) {",
                    ],
                    [
                        "\t\treturn \$this->service->handle(Action([",
                        "\t\t\t'type' => 'CREATE',",
                    ],
                    $validatable === '[]' ? [
                        "\t\t\t'payload' => [",
                        "\t\t\t\t\$request->all(),",
                        "\t\t\t\t\$request->has(self::RESOURCE_PRIMARY_KEY) ?",
                        "\t\t\t\t\t[",
                        "\t\t\t\t\t\t'upsert' => true,",
                        "\t\t\t\t\t\t'upsert_conditions' => [",
                        "\t\t\t\t\t\t\tself::RESOURCE_PRIMARY_KEY => \$request->get(self::RESOURCE_PRIMARY_KEY),",
                    ] : [
                        "\t\t\t'payload' => [",
                        "\t\t\t\t\$viewModel_->all(),",
                        "\t\t\t\t\$viewModel_->has(self::RESOURCE_PRIMARY_KEY) ?",
                        "\t\t\t\t\t[",
                        "\t\t\t\t\t\t'upsert' => true,",
                        "\t\t\t\t\t\t'upsert_conditions' => [",
                        "\t\t\t\t\t\t\tself::RESOURCE_PRIMARY_KEY => \$viewModel_->get(self::RESOURCE_PRIMARY_KEY),",
                    ],
                    [

                        "\t\t\t\t\t\t],",
                        "\t\t\t\t\t] :",
                        "\t\t\t\t\t[]",
                        "\t\t\t],",
                    ],
                    null !== $this->dtoClass_ ? [
                        "\t\t]), function( \$value) {",
                        "\t\t\treturn null !== \$value ? new $this->dtoClass_(\$value->toArray()) : \$value",
                        "\t\t});"
                    ] : ["\t\t]))"],
                    [
                        "\t});",
                        "",
                        "\treturn \$this->response->ok(\$result)", //
                        "} catch (ValidationException \$e) {",
                        "\t// Return failure response to request client",
                        "\treturn \$this->response->badRequest(\$e->getErrors())",
                        "} catch (\Exception \$e) {",
                        "\t// Return failure response to request client",
                        "\treturn \$this->response->error(\$e)",
                        '}',
                    ]
                ),
            ], [
                'name' => 'update',
                'params' => [
                    PHPFunctionParameter('request', Request::class),
                    (PHPFunctionParameter('id', null)),
                ],
                'descriptors' => [
                    'Update the specified resource in storage.',
                    '@Route /PUT /' . $this->routeName_ . '/{id}',
                    '@Route /PATCH /' . $this->routeName_ . '/{id}',
                ],
                'returns' => JsonResponse::class,
                'contents' => array_merge(
                    [
                        'try {',
                        "\t\$request = \$request->merge([\"id\" => \$id])",
                        "\t// validate request inputs",
                        "\t// Use your custom validation rules here",
                    ],
                    $validatable === '[]' ? [
                        "\t\$result = \$this->validator->setUpdate(true)->validate($validatable, \$request->all(), function() use (\$id, \$request) {",
                        "\t// After validation logic goes here...",
                    ] : [
                        "\t\$viewModel_ = (new " . drewlabs_core_strings_replace('::class', '', $validatable) . ")->setUser(\$request->user())->set(\$request->all())->files(\$request->allFiles())",
                        "",
                        "\t\$result = \$this->validator->setUpdate(true)->validate(\$viewModel_, \$request->all(), function() use (\$id, \$viewModel_) {",
                    ],
                    [
                        "\t\treturn \$this->service->handle(Action([",
                        "\t\t\t'type' => 'UPDATE',",
                    ],
                    $validatable === '[]' ? [
                        "\t\t\t'payload' => [\$id, \$request->all()],",
                    ] : [
                        "\t\t\t'payload' => [\$id, \$viewModel_->all()],",
                    ],
                    null !== $this->dtoClass_ ? [
                        "\t\t]), function( \$value) {",
                        "\t\t\treturn null !== \$value ? new $this->dtoClass_(\$value->toArray()) : \$value",
                        "\t\t});"
                    ] : ["\t\t]))"],
                    [
                        "\t});",
                        "",
                        "\treturn \$this->response->ok(\$result)", // ValidationException
                        "} catch (ValidationException \$e) {",
                        "\t// Return failure response to request client",
                        "\treturn \$this->response->badRequest(\$e->getErrors())",
                        "} catch (\Exception \$e) {",
                        "\t// Return failure response to request client",
                        "\treturn \$this->response->error(\$e)",
                        '}',
                    ]
                ),
            ],
            [
                'name' => 'destroy',
                'params' => [
                    PHPFunctionParameter('request', Request::class),
                    (PHPFunctionParameter('id', null)),
                ],
                'descriptors' => [
                    'Remove the specified resource from storage.',
                    '@Route /DELETE /' . $this->routeName_ . '/{id}',
                ],
                'returns' => JsonResponse::class,
                'contents' => [
                    "// TODO: Provide Policy handlers if required",
                    "\$result = \$this->service->handle(Action([",
                    "\t'type' => 'DELETE',",
                    "\t'payload' => [\$id],",
                    "]))",
                    "return \$this->response->ok(\$result)", //
                ],
            ],
        ];
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
                foreach ($contents as $value) {
                    // code...
                    $method = $method->addLine($value);
                }
            }
            $carry = $carry->addMethod($method);

            return $carry;
        }, $component
            ->addClassPath(ValidationException::class)
            ->addFunctionPath(self::ACTION_FUNCTION_PATH)
            ->addConstant(PHPClassProperty(
                'RESOURCE_PRIMARY_KEY',
                'string',
                PHPTypesModifiers::PRIVATE,
                'id',
                'Resource primary key name'
            )));

        // Returns the component back to the caller
        return $component;
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
