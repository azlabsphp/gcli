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
use Drewlabs\CodeGenerator\Helpers\Str;

use function Drewlabs\CodeGenerator\Proxy\PHPClass;
use function Drewlabs\CodeGenerator\Proxy\PHPClassMethod;

use function Drewlabs\CodeGenerator\Proxy\PHPClassProperty;

use Drewlabs\CodeGenerator\Types\PHPTypesModifiers;

use Drewlabs\GCli\Contracts\ComponentBuilder as AbstractBuilder;
use Drewlabs\GCli\Factories\ComponentPath;
use Drewlabs\GCli\Helpers\ComponentBuilder;

use function Drewlabs\GCli\Proxy\PHPScript;

use Drewlabs\GCli\Traits\HasNamespaceAttribute;

class ServiceProviderBuilder implements AbstractBuilder
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
     * Domain route file path
     * 
     * @var string
     */
    private $routeFilePath;

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

    /**
     * Enable domain routing regitar for the policy class
     * 
     * @param string $filename 
     * @return static 
     */
    public function withDomainRouting(string $filename)
    {
        $self = clone $this;
        $self->routeFilePath = $filename;
        return $self;
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

        $registerMethod = PHPClassMethod('register', [], 'void', PHPTypesModifiers::PUBLIC, ['Register application services.'])
        ->addContents(implode(\PHP_EOL, array_map(static function ($binding) use ($values) {
            return '$this->app->bind(' . $binding . '::class, ' . $values[$binding] . '::class);';
        }, array_keys($values))));
        if ($this->routeFilePath) {
            $routeFilePath = Str::endsWith($this->routeFilePath, '.php') ? $this->routeFilePath : ($this->routeFilePath.".php");
            $registerMethod = $registerMethod->addLine(implode(PHP_EOL, [
                "",
                "\t\t// Register domain routes",
                "\t\t// Comment the lines below to remove domain level routes registar",
                "\t\t\$this->booted(function() {",
                "\t\t\t\\Illuminate\Support\Facades\Route::prefix('api')",
                "\t\t\t\t->middleware('api')",
                "\t\t\t\t//->namespace(\$this->namespace) //uncomment the line to provide controllers namespace",
                "\t\t\t\t->group(\$this->app->basePath('routes/$routeFilePath'));",
                "\t\t});",
                ""
            ]));
        }
        $component = $component->addMethod($registerMethod);

        // Boot method
        $bootMethod = PHPClassMethod('boot', [], 'void', PHPTypesModifiers::PUBLIC, ['Boot application services.']);
        if (!empty($this->policies)) {
            $bootMethod = $bootMethod->addLine('$this->registerPolicies()');
            $component = $component->addProperty(
                PHPClassProperty('policies', 'array', PHPTypesModifiers::PRIVATE, $this->policies, [
                    'Map application models to policies',
                ])
            )->addMethod(
                PHPClassMethod('policies', [], 'array', PHPTypesModifiers::PRIVATE, [
                    'Policies property getter',
                ])->addLine('return $this->policies')
            )->addMethod(
                PHPClassMethod('registerPolicies', [], 'void', PHPTypesModifiers::PRIVATE, ['Register authorization policies.'])
                    ->addLine('foreach ($this->policies() as $model => $policy) {')
                    ->addLine("\tGate::policy(\$model, \$policy)")
                    ->addLine('}')
            );
        }

        // Add boot method definitions
        $component = $component->addMethod($bootMethod);

        // Returns the builded component
        return PHPScript(
            $component->getName(),
            $component,
            ComponentPath::new()->create($this->namespace_ ?? self::__NAMESPACE__, $this->path_ ?? self::__PATH__)
        )->setNamespace($component->getNamespace());
    }
}
