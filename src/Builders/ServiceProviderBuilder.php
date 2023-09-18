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

use Drewlabs\CodeGenerator\Types\PHPTypesModifiers;

use Drewlabs\GCli\Contracts\ComponentBuilder;
use Drewlabs\GCli\Helpers\ComponentBuilderHelpers;

use function Drewlabs\GCli\Proxy\PHPScript;

use Drewlabs\GCli\Traits\HasNamespaceAttribute;

class ServiceProviderBuilder implements ComponentBuilder
{
    use HasNamespaceAttribute;

    /**
     * class namespace.
     *
     * @var string
     */
    private const __NAMESPACE__ = 'App\\Providers';

    /**
     * @var string
     */
    private const __NAME__ = 'PoliciesServiceProvider';

    /**
     * @var string
     */
    private const __PATH__ = 'Providers/';

    /**
     * @var array
     */
    private $policies = [];

    /**
     * @var array
     */
    private $bindings = [];

    /**
     * Creates new class instance.
     *
     * @throws RuntimeException
     * @throws \Exception
     *
     * @return void
     */
    public function __construct(array $policies = [], array $bindings = [], string $namespace = null, string $path = null, string $name = null)
    {
        $this->setName($name ?? self::__NAME__);

        $this->policies = $policies;
        $this->bindings = $bindings ?? [];

        // Set the component write path
        $this->setWritePath($path ?? self::__PATH__);

        // Set the component namespace
        $this->setNamespace($namespace ?? self::__NAMESPACE__);
    }

    public function build()
    {
        /**
         * @var Blueprint
         */
        $component = PHPClass($this->name());
        $component = $component = $component->asFinal()
            ->setBaseClass('BaseServiceProvider')
            ->addToNamespace($this->namespace_ ?? self::__NAMESPACE__);
        $classPaths = empty($this->policies) ? ['Illuminate\\Support\\ServiceProvider as BaseServiceProvider'] : ['Illuminate\\Support\\Facades\\Gate', 'Illuminate\\Support\\ServiceProvider as BaseServiceProvider'];
        foreach ($classPaths ?? [] as $value) {
            /**
             * @var Blueprint
             */
            $component = $component->addClassPath($value);
        }
        $values = [];
        if ($this->bindings) {
            foreach ($this->bindings as $abstract => $concrete) {
                $component = $component->addClassPath($abstract);
                $component = $component->addClassPath($concrete);
                $values[array_reverse(explode('\\', ltrim($abstract, '\\')))[0]] = array_reverse(explode('\\', ltrim($concrete, '\\')))[0];
            }
        }

        $component = $component->addMethod(
            PHPClassMethod('register', [], 'void', PHPTypesModifiers::PUBLIC, ['Register application services.'])
                ->addContents(implode(\PHP_EOL, array_map(static function ($binding) use ($values) {
                    return '$this->app->bind('.$binding.'::class, '.$values[$binding].'::class);';
                }, array_keys($values))))
        );

        if (!empty($this->policies)) {
            $component = $component->addProperty(PHPClassProperty('policies', 'array', PHPTypesModifiers::PRIVATE, $this->policies, [
                'Map application models to policies',
            ]))
                ->addMethod(
                    PHPClassMethod('policies', [], 'array', PHPTypesModifiers::PRIVATE, [
                        'Policies property getter',
                    ])->addLine('return $this->policies')
                )
                ->addMethod(
                    PHPClassMethod('registerPolicies', [], 'void', PHPTypesModifiers::PRIVATE, ['Register authorization policies.'])
                        ->addLine('foreach ($this->policies() as $model => $policy) {')
                        ->addLine("\tGate::policy(\$model, \$policy)")
                        ->addLine('}')
                )
                ->addMethod(
                    PHPClassMethod('boot', [], 'void', PHPTypesModifiers::PUBLIC, ['Boot application services.'])
                        ->addLine('$this->registerPolicies()')
                );
        }

        // Returns the builded component
        return PHPScript(
            $component->getName(),
            $component,
            ComponentBuilderHelpers::rebuildComponentPath($this->namespace_ ?? self::__NAMESPACE__, $this->path_ ?? self::__PATH__)
        )->setNamespace($component->getNamespace());
    }
}
