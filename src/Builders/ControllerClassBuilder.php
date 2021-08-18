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
use Drewlabs\CodeGenerator\Contracts\OOPComposableStruct;
use Drewlabs\CodeGenerator\Models\PHPClass;
use Drewlabs\CodeGenerator\Models\PHPClassMethod;
use Drewlabs\CodeGenerator\Models\PHPClassProperty;
use Drewlabs\CodeGenerator\Models\PHPFunctionParameter;
use Drewlabs\CodeGenerator\Types\PHPTypesModifiers;
use Drewlabs\ComponentGenerators\Contracts\ControllerBuilder as ContractsControllerBuilder;
use Drewlabs\ComponentGenerators\PHP\PHPScriptFile;
use Drewlabs\ComponentGenerators\Traits\HasNameAttribute;
use Drewlabs\ComponentGenerators\Traits\HasNamespaceAttribute;
use Drewlabs\Contracts\Validator\Validator;
use Drewlabs\Packages\Http\Contracts\IActionResponseHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Pluralizer;

class ControllerClassBuilder implements ContractsControllerBuilder
{
    use HasNamespaceAttribute;
    use HasNameAttribute;

    private const DEFAULT_NAME = 'TestsController';

    private const DEFAULT_PATH = 'app/Http/Controllers/';

    /**
     * Controller class namespace.
     *
     * @var string
     */
    private const DEFAULT_NAMESPACE = 'App\\Http\\Controllers';

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
    private $serviceClass_;

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
        if ($path) {
            $this->setWritePath(self::DEFAULT_PATH);
        }
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

    public function bindModel(string $model, $asViewModelClass = false)
    {
        if (empty($model)) {
            return $this;
        }
        // TODO: Makes this controller as a resource controller
        $this->asResourceController();
        // TODO : Generate and set the controller name
        $is_class_path = drewlabs_core_strings_contains($model, '\\'); // && class_exists($model); To uncomment if there is need to validate class existence
        $model_name = 'Tests';
        if ($is_class_path) {
            $model_name = array_reverse(explode('\\', $model))[0];
        } else {
            $model_name = drewlabs_core_strings_contains($model, '\\') ? array_reverse(explode('\\', $model))[0] : $model;
        }
        // TODO Set the controller name
        $controllerName = drewlabs_core_strings_as_camel_case(Pluralizer::plural($model_name)).'Controller';
        $this->setName($controllerName);
        // TODO Set the view model class property if specified
        if ($is_class_path && $asViewModelClass) {
            $this->bindViewModelClass($model);
        }

        return $this;
    }

    public function bindViewModelClass(string $viewModelClass)
    {
        if (drewlabs_core_strings_contains($viewModelClass, '\\')) {
            $this->viewModelClass_ = $viewModelClass;
        }

        return $this;
    }

    public function bindServiceClass(string $serviceClass)
    {
        if (drewlabs_core_strings_contains($serviceClass, '\\')) {
            $this->serviceClass_ = $serviceClass;
        }
        return $this;
    }

    public function build()
    {
        /**
         * @var Blueprint
         */
        $controller = (new PHPClass($this->name_ ?? self::DEFAULT_NAME))
            ->asFinal()
            ->addToNamespace($this->namespace_ ?? self::DEFAULT_NAMESPACE);
        if ($this->serviceClass_) {
            $controller = $controller->addProperty(
                new PHPClassProperty(
                    'service',
                    $this->serviceClass_,
                    PHPTypesModifiers::PRIVATE,
                    null,
                    'Injected instance of MVC service'
                )
            );
        }
        // Model associated table
        $controller->addProperty(
            new PHPClassProperty(
                'validator',
                Validator::class,
                PHPTypesModifiers::PRIVATE,
                null,
                'Injected instance of the validator class'
            )
        )
            ->addProperty(
                new PHPClassProperty(
                    'response',
                    IActionResponseHandler::class,
                    PHPTypesModifiers::PRIVATE,
                    null,
                    'Injected instance of the response handler class'
                )
            )
            ->addMethod(
                (new PHPClassMethod(
                    '__construct',
                    array_merge(
                        [
                            new PHPFunctionParameter(
                                'validator',
                                Validator::class,
                            ),
                            new PHPFunctionParameter(
                                'response',
                                IActionResponseHandler::class,
                            ),
                        ],
                        // Add the service class as parameter to the constructor
                        null !== $this->serviceClass_ ? [
                            new PHPFunctionParameter(
                                'service',
                                $this->serviceClass_,
                            ),
                        ] : []
                    ),
                    'self',
                    PHPTypesModifiers::PUBLIC,
                    'Create a new Http Controller class'
                ))->addLine(
                    '$this->validator = $validator'
                )->addLine(
                    '$this->response = $response'
                )
            );
        // Add resources Actions if the class is a resources controller
        if ($this->isResourceController_) {
            $controller = $this->addResourcesActions($controller);
        }

        // Add the __invokeMethod if the controller is invokable
        if ($this->isInvokable_) {
            $controller = $controller->addMethod(
                new PHPClassMethod(
                    '__invoke',
                    [
                        new PHPFunctionParameter('request', Request::class),
                    ],
                    Response::class,
                    PHPTypesModifiers::PUBLIC,
                    [
                        'Handles http request action',
                    ]
                )
            );
        }
        // Returns the builded component
        return new PHPScriptFile(
            $controller->getName(),
            $controller,
            $this->path_ ?? self::DEFAULT_PATH
        );
    }

    private function addResourcesActions(OOPComposableStruct $component)
    {
        $routeName = drewlabs_core_strings_as_snake_case(drewlabs_core_strings_to_lower_case(drewlabs_core_strings_replace('Controller', '', $component->getName())), '-');
        $validatable = null === $this->viewModelClass_ ? '[]' : $this->viewModelClass_;
        $actions = [
            [
                'name' => 'index',
                'params' => [
                    new PHPFunctionParameter('request', Request::class),
                    (new PHPFunctionParameter('id', null, 'null'))->asOptional(),
                ],
                'descriptors' => [
                    'Display or Returns a list of items',
                    '@Route /GET /'.$routeName.'[/{$id}]',
                ],
                'returns' => JsonResponse::class,
                'contents' => [
                    'if (!is_null($id)) {',
                    "\treturn \$this->show(\$request, \$id)",
                    '}',
                    '',
                    "\t// Code goes here...",
                    '',
                ],
            ],
            [
                'name' => 'show',
                'params' => [
                    new PHPFunctionParameter('request', Request::class),
                    (new PHPFunctionParameter('id', null)),
                ],
                'descriptors' => [
                    'Display or Returns an item matching the specified id',
                    '@Route /GET /'.$routeName.'/{$id}',
                ],
                'returns' => JsonResponse::class,
                'contents' => [
                    "\t// Code goes here...",
                    '',
                ],
            ],
            [
                'name' => 'store',
                'params' => [
                    new PHPFunctionParameter('request', Request::class),
                ],
                'descriptors' => [
                    'Stores a new item in the storage',
                    '@Route /POST /'.$routeName,
                ],
                'returns' => JsonResponse::class,
                'contents' => [
                    'try {',
                    "\t// validate request inputs",
                    "\t// Use your custom validation rules here",
                    "\t\$validator = \$this->validator->validate($validatable, \$request->all())",
                    "\tif (\$validator->fails()) {",
                    "\t\treturn \$this->response->badRequest(\$validator->errors())",
                    "\t}",
                    '',
                    "\t// Code goes here...",
                    '',
                    "} catch (\Exception \$e) {",
                    "\t// Return failure response to request client",
                    "\treturn \$this->response->error(\$e)",
                    '}',
                ],
            ], [
                'name' => 'update',
                'params' => [
                    new PHPFunctionParameter('request', Request::class),
                    (new PHPFunctionParameter('id', null)),
                ],
                'descriptors' => [
                    'Update the specified resource in storage.',
                    '@Route /PUT /'.$routeName.'/{id}',
                    '@Route /PATCH /'.$routeName.'/{id}',
                ],
                'returns' => JsonResponse::class,
                'contents' => [
                    'try {',
                    "\t\$request = \$request->merge([\"id\" => \$id])",
                    "\t// validate request inputs",
                    "\t// Use your custom validation rules here",
                    "\t\$validator = \$this->validator->setUpdate(true)->validate($validatable, \$request->all())",
                    "\tif (\$validator->fails()) {",
                    "\t\treturn \$this->response->badRequest(\$validator->errors())",
                    "\t}",
                    '',
                    "\t// Code goes here...",
                    '',
                    "} catch (\Exception \$e) {",
                    "\t// Return failure response to request client",
                    "\treturn \$this->response->error(\$e)",
                    '}',
                ],
            ],
            [
                'name' => 'destroy',
                'params' => [
                    new PHPFunctionParameter('request', Request::class),
                    (new PHPFunctionParameter('id', null)),
                ],
                'descriptors' => [
                    'Remove the specified resource from storage.',
                    '@Route /DELETE /'.$routeName.'/{id}',
                ],
                'returns' => JsonResponse::class,
                'contents' => [
                    'try {',
                    "\t// Code goes here ...",
                    "} catch (\Exception \$e) {",
                    "\t// Return failure response to request client",
                    "\treturn \$this->response->error(\$e)",
                    '}',
                ],
            ],
        ];
        $component = array_reduce($actions, static function (OOPComposableStruct $carry, $action) {
            $method = new PHPClassMethod(
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
        }, $component);

        // Returns the component back to the caller
        return $component;
    }
}
