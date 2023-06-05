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
use function Drewlabs\CodeGenerator\Proxy\PHPClassProperty;

use Drewlabs\CodeGenerator\Types\PHPTypesModifiers;

use Drewlabs\GCli\Contracts\ComponentBuilder;
use Drewlabs\GCli\Helpers\ComponentBuilderHelpers;

use function Drewlabs\GCli\Proxy\PHPScript;

use Drewlabs\GCli\Traits\HasNamespaceAttribute;

class PolicyServiceProviderBuilder implements ComponentBuilder
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
     * List of classes to imports.
     *
     * @var array
     */
    private const CLASS_PATHS = [
        'Illuminate\\Support\\Facades\\Gate',
        'Illuminate\\Support\\ServiceProvider',
    ];

    /**
     * @var array
     */
    private $policies = [];

    /**
     * Creates new class instance.
     *
     * @throws RuntimeException
     * @throws \Exception
     *
     * @return void
     */
    public function __construct(array $policies = [], ?string $path = null, ?string $namespace = null)
    {
        $this->setName(self::__NAME__);

        $this->policies = $policies;

        // Set the component write path
        $this->setWritePath($path ?? self::__PATH__);

        // Set the component namespace
        $this->setNamespace($namespace ?? self::__NAMESPACE__);
    }

    public function build()
    {

        $component = PHPClass($this->name());
        foreach (self::CLASS_PATHS ?? [] as $value) {
            $component = $component->addClassPath($value);
        }
        $component = $component->asFinal()
            ->setBaseClass('ServiceProvider')
            ->addProperty(PHPClassProperty('policies', 'array', PHPTypesModifiers::PRIVATE, $this->policies, [
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
            )
            ->addToNamespace($this->namespace_ ?? self::__NAMESPACE__);

        // Returns the builded component
        return PHPScript(
            $component->getName(),
            $component,
            ComponentBuilderHelpers::rebuildComponentPath($this->namespace_ ?? self::__NAMESPACE__, $this->path_ ?? self::__PATH__)
        )->setNamespace($component->getNamespace());
    }
}
