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
use Drewlabs\CodeGenerator\Contracts\CallableInterface;

use function Drewlabs\CodeGenerator\Proxy\PHPClass;
use function Drewlabs\CodeGenerator\Proxy\PHPClassMethod;
use function Drewlabs\CodeGenerator\Proxy\PHPFunctionParameter;

use Drewlabs\CodeGenerator\Types\PHPTypesModifiers;
use Drewlabs\Core\Helpers\Str;
use Drewlabs\GCli\Contracts\ComponentBuilder as AbstractBuilder;
use Drewlabs\GCli\Factories\ComponentPath;

use function Drewlabs\GCli\Proxy\PHPScript;

use Drewlabs\GCli\Traits\HasNamespaceAttribute;
use Drewlabs\GCli\Traits\ProvidesContracts;
use Illuminate\Support\Pluralizer;

class ServiceClassBuilder implements AbstractBuilder
{
    use HasNamespaceAttribute;
    use ProvidesContracts;

    /**
     * Service default class namespace.
     *
     * @var string
     */
    public const DEFAULT_NAMESPACE = 'App\\Services';

    /**
     * @var string[]
     */
    private const CLASS_FUNCTION_PATHS = [
        'Drewlabs\Laravel\Query\Proxy\\useActionQueryCommand',
    ];

    /**
     * @var string
     */
    private const DEFAULT_NAME = 'TestService';

    /**
     * @var string
     */
    private const DEFAULT_PATH = 'Services/';

    /**
     * @var bool
     */
    private $asCRUD_ = false;

    /**
     * List of classes to imports.
     *
     * @var array
     */
    private $classPaths_ = [];

    /**
     * The name of the model the service will be bound to.
     *
     * @var string
     */
    private $modelName_ = 'Test';

    /**
     * Creates a service builder class.
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
        string $name = null,
        string $namespace = null,
        string $path = null
    ) {
        $this->setName($name ? (!Str::endsWith($name, 'Service') ?
            Str::camelize(Pluralizer::singular($name)).'Service' :
            Str::camelize(Pluralizer::singular($name))) : self::DEFAULT_NAME);
        // Set the component write path
        $this->setWritePath($path ?? self::DEFAULT_PATH);

        // Set the component namespace
        $this->setNamespace($namespace ?? self::DEFAULT_NAMESPACE);
    }

    public function bindModel(string $classPath)
    {
        if (empty($classPath)) {
            return $this;
        }
        $isclasspath = Str::contains($classPath, '\\'); // && class_exists($model); To uncomment if there is need to validate class existence
        if ($isclasspath) {
            $this->modelName_ = array_reverse(explode('\\', $classPath))[0];
            $this->classPaths_[$classPath] = $classPath;
        } else {
            $this->modelName_ = $classPath;
        }
        $this->setName(Str::camelize(Pluralizer::singular($this->modelName_)).'Service');

        return $this;
    }

    public function addImplementation(...$implementations)
    {
        $this->contracts = $implementations;

        return $this;
    }

    /**
     * Indicates to generate a simple Model service that will be usable with minimum modification.
     *
     * @return self
     */
    public function asCRUDService()
    {
        $this->asCRUD_ = true;

        return $this;
    }

    public function build()
    {
        $component = PHPClass($this->name());
        foreach (static::CLASS_FUNCTION_PATHS as $functionPath) {
            /**
             * @var BluePrint|PHPClass
             */
            $component = $component->addFunctionPath($functionPath);
        }

        foreach ($this->classPaths_ ?? [] as $value) {
            $component = $component->addClassPath($value);
        }

        foreach ($this->getContracts() as $contract) {
            $component = $component->addImplementation($contract);
        }

        $component->asFinal()
            // Add Handler method
            ->addMethod(
                array_reduce(array_filter([$this->asCRUD_ ? "return useActionQueryCommand($this->modelName_::class)(\$action, \$callback)" : '#code...'], static function ($line) {
                    return null !== $line;
                }), static function (CallableInterface $carry, $curr) {
                    return $carry->addLine($curr);
                }, PHPClassMethod(
                    'handle',
                    [
                        PHPFunctionParameter(
                            'action',
                            \Drewlabs\Contracts\Support\Actions\Action::class,
                        ),
                        PHPFunctionParameter(
                            'callback',
                            '\Closure',
                        )->asOptional(),
                    ],
                    \Drewlabs\Contracts\Support\Actions\ActionResult::class,
                    PHPTypesModifiers::PUBLIC,
                    '{@inheritDoc}'
                )->throws(\Drewlabs\Contracts\Support\Actions\Exceptions\InvalidActionException::class))
            )
            ->addToNamespace($this->namespace_ ?? self::DEFAULT_NAMESPACE);

        // Returns the builded component
        return PHPScript(
            $component->getName(),
            $component,
            ComponentPath::new()->create($this->namespace_ ?? self::DEFAULT_NAMESPACE, $this->path_ ?? self::DEFAULT_PATH)
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
}
