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

use function Drewlabs\CodeGenerator\Proxy\PHPClass;
use function Drewlabs\CodeGenerator\Proxy\PHPClassMethod;
use function Drewlabs\CodeGenerator\Proxy\PHPFunctionParameter;

use Drewlabs\Core\Helpers\Str;

use Drewlabs\GCli\Contracts\ComponentBuilder as AbstractBuilder;
use Drewlabs\GCli\Factories\ComponentPath;

use function Drewlabs\GCli\Proxy\PHPScript;

use Drewlabs\GCli\Traits\HasNamespaceAttribute;

class PolicyClassBuilder implements AbstractBuilder
{
    use HasNamespaceAttribute;

    /**
     * class namespace.
     *
     * @var string
     */
    private const __NAMESPACE__ = 'App\\Policies';

    /**
     * @var string
     */
    private const __NAME__ = 'TestPolicy';

    /**
     * @var string
     */
    private const __PATH__ = 'Policies/';

    /**
     * List of classes to imports.
     *
     * @var array
     */
    private $classPaths = [
        'Illuminate\\Contracts\\Auth\\Authenticatable',
        'Illuminate\\Auth\\Access\\HandlesAuthorization',
    ];

    /**
     * The name of the model bound to the policy.
     *
     * @var string
     */
    private $model = 'Test';

    /**
     * The name of the view model bound to the policy.
     *
     * @var string
     */
    private $viewModel;

    /**
     * Creates class instances.
     *
     * @param (string|null)|null $name
     * @param (string|null)|null $namespace
     * @param (string|null)|null $path
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws UnableToRetrieveMetadataException
     *
     * @return void
     */
    public function __construct(
        string $name = null,
        string $namespace = null,
        string $path = null
    ) {
        $this->setName($name ? (!Str::endsWith($name, 'Policy') ? Str::camelize($name).'Policy' : Str::camelize($name)) : self::__NAME__);
        // Set the component write path
        $this->setWritePath($path ?? self::__PATH__);

        // Set the component namespace
        $this->setNamespace($namespace ?? self::__NAMESPACE__);
    }

    public function withModel(string $classPath)
    {
        $self = clone $this;
        if (empty($classPath)) {
            return $self;
        }
        if (str_contains($classPath, '\\')) {
            $self->model = array_reverse(explode('\\', $classPath))[0];
            $self->classPaths[$classPath] = $classPath;
        } else {
            $self->model = $classPath;
        }
        $self->setName(Str::camelize($self->model).'Policy');

        return $self;
    }

    /**
     * Copy the current instance and modify the view model name.
     *
     * @return self
     */
    public function withViewModel(string $classPath)
    {
        $self = clone $this;
        if (empty($classPath)) {
            return $self;
        }
        if (str_contains($classPath, '\\')) {
            $self->viewModel = array_reverse(explode('\\', $classPath))[0];
            $self->classPaths[$classPath] = $classPath;

            return $self;
        }

        // Defaults
        $self->viewModel = $classPath;

        return $self;
    }

    public function build()
    {
        $component = PHPClass($this->name());
        foreach ($this->classPaths ?? [] as $value) {
            $component = $component->addClassPath($value);
        }
        $component
            ->asFinal()
            ->addConstructor()
            ->addTrait('HandlesAuthorization')
            // Add viewAny method
            ->addMethod(
                PHPClassMethod(
                    'viewAny',
                    array_filter([
                        PHPFunctionParameter('user', 'Authenticatable')->asOptional(),
                        $this->viewModel ? PHPFunctionParameter('view', $this->viewModel)->asOptional() : null,
                    ]),
                    'bool|mixed',
                    'public',
                    '`index` action policy gate'
                )->addLine('return true')
            )
            // Add `view` method
            ->addMethod(
                PHPClassMethod(
                    'view',
                    array_filter([
                        PHPFunctionParameter('user', 'Authenticatable')->asOptional(),
                        PHPFunctionParameter('model', $this->model)->asOptional(),
                        $this->viewModel ? PHPFunctionParameter('view', $this->viewModel)->asOptional() : null,
                    ]),
                    'bool|mixed',
                    'public',
                    '`show` action policy gate handler'
                )->addLine('if (null === $model) {')
                    ->addLine("\treturn \$this->denyWithStatus(404)")
                    ->addLine('}')
                    ->addLine('return true')
            )
            // Add `create` method
            ->addMethod(
                PHPClassMethod(
                    'create',
                    array_filter([
                        PHPFunctionParameter('user', 'Authenticatable')->asOptional(),
                        $this->viewModel ? PHPFunctionParameter('view', $this->viewModel)->asOptional() : null,
                    ]),
                    'bool|mixed',
                    'public',
                    '`store/create` action policy gate policy'
                )
                    ->addLine('return true')
            )
            // Add `update` method
            ->addMethod(
                PHPClassMethod(
                    'update',
                    array_filter([
                        PHPFunctionParameter('user', 'Authenticatable')->asOptional(),
                        PHPFunctionParameter('model', $this->model)->asOptional(),
                        $this->viewModel ? PHPFunctionParameter('view', $this->viewModel)->asOptional() : null,
                    ]),
                    'bool|mixed',
                    'public',
                    '`edit/update` action policy gate handler'
                )
                    ->addLine('if (null === $model) {')
                    ->addLine("\treturn \$this->denyWithStatus(404)")
                    ->addLine('}')
                    ->addLine('return true')
            )
            // Add `update` method
            ->addMethod(
                PHPClassMethod(
                    'delete',
                    array_filter([
                        PHPFunctionParameter('user', 'Authenticatable')->asOptional(),
                        PHPFunctionParameter('model', $this->model)->asOptional(),
                        $this->viewModel ? PHPFunctionParameter('view', $this->viewModel)->asOptional() : null,
                    ]),
                    'bool|mixed',
                    'public',
                    '`delete/destroy` action policy gate handler'
                )
                    ->addLine('if (null === $model) {')
                    ->addLine("\treturn \$this->denyWithStatus(404)")
                    ->addLine('}')
                    ->addLine('return true')
            )
            ->addToNamespace($this->namespace_ ?? self::__NAMESPACE__);

        // Returns the builded component
        return PHPScript(
            $component->getName(),
            $component,
            ComponentPath::new()->create($this->namespace_ ?? self::__NAMESPACE__, $this->path_ ?? self::__PATH__)
        )->setNamespace($component->getNamespace());
    }
}
