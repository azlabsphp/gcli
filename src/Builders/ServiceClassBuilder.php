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
use Drewlabs\CodeGenerator\Contracts\CallableInterface;
use function Drewlabs\CodeGenerator\Proxy\PHPClass;
use function Drewlabs\CodeGenerator\Proxy\PHPClassMethod;
use function Drewlabs\CodeGenerator\Proxy\PHPClassProperty;
use function Drewlabs\CodeGenerator\Proxy\PHPFunctionParameter;
use Drewlabs\CodeGenerator\Types\PHPTypesModifiers;
use Drewlabs\ComponentGenerators\Contracts\ComponentBuilder;
use Drewlabs\ComponentGenerators\Helpers\ComponentBuilderHelpers;
use function Drewlabs\ComponentGenerators\Proxy\PHPScript;
use Drewlabs\ComponentGenerators\Traits\HasNamespaceAttribute;
use Drewlabs\Core\Helpers\Str;
use Drewlabs\Filesystem\Exceptions\UnableToRetrieveMetadataException;
use Illuminate\Support\Pluralizer;
use InvalidArgumentException;
use RuntimeException;

class ServiceClassBuilder implements ComponentBuilder
{
    use HasNamespaceAttribute;

    /**
     * Service default class namespace.
     *
     * @var string
     */
    public const DEFAULT_NAMESPACE = 'App\\Services';

    private const CLASS_FUNCTION_PATHS = [
        'Drewlabs\\Packages\\Database\\Proxy\\useDMLQueryActionCommand',
        'Drewlabs\\Packages\\Database\\Proxy\\DMLManager',
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
     * Creates a service builder class
     * 
     * @param (null|string)|null $name 
     * @param (null|string)|null $namespace 
     * @param (null|string)|null $path 
     * @return void 
     * @throws InvalidArgumentException 
     * @throws RuntimeException 
     * @throws UnableToRetrieveMetadataException 
     */
    public function __construct(
        ?string $name = null,
        ?string $namespace = null,
        ?string $path = null
    ) {
        $this->setName($name ? (!Str::endsWith($name, 'Service') ?
            Str::camelize(Pluralizer::singular($name)) . 'Service' :
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
        $this->setName(Str::camelize(Pluralizer::singular($this->modelName_)) . 'Service');
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
        $handlMethodLines = [
            $this->asCRUD_ ? 'return useDMLQueryActionCommand($this->dbManager)($action, $callback)' : '#code...',
        ];
        $component = (PHPClass($this->name()));
        foreach (static::CLASS_FUNCTION_PATHS as $functionPath) {
            /**
             * @var BluePrint|PHPClass
             */
            $component = $component->addFunctionPath($functionPath);
        }

        foreach ($this->classPaths_ ?? [] as $value) {
            $component = $component->addClassPath($value);
        }

        $component->addImplementation(\Drewlabs\Contracts\Support\Actions\ActionHandler::class)
            ->asFinal()
            ->addProperty(
                PHPClassProperty(
                    'dbManager',
                    \Drewlabs\Contracts\Data\DML\DMLProvider::class,
                    PHPTypesModifiers::PRIVATE,
                    null,
                    'Database query manager'
                )
            )
            // Add the class constructor
            ->addMethod(
                (PHPClassMethod(
                    '__construct',
                    [
                        (PHPFunctionParameter(
                            'manager',
                            \Drewlabs\Contracts\Data\DML\DMLProvider::class,
                            null
                        ))->asOptional(),
                    ],
                    'self',
                    PHPTypesModifiers::PUBLIC,
                    'Creates an instance of the Service'
                ))->addLine(
                    "\$this->dbManager = \$manager ?? DMLManager($this->modelName_::class)"
                )
            )
            // Add Handler method
            ->addMethod(
                array_reduce(array_filter($handlMethodLines, static function ($line) {
                    return null !== $line;
                }), static function (CallableInterface $carry, $curr) {
                    return $carry->addLine($curr);
                }, (PHPClassMethod(
                    'handle',
                    [
                        PHPFunctionParameter(
                            'action',
                            \Drewlabs\Contracts\Support\Actions\Action::class,
                        ),
                        (PHPFunctionParameter(
                            'callback',
                            '\Closure',
                        ))->asOptional(),
                    ],
                    \Drewlabs\Contracts\Support\Actions\ActionResult::class,
                    PHPTypesModifiers::PUBLIC,
                    '{@inheritDoc}'
                )->throws(\Drewlabs\Contracts\Support\Actions\Exceptions\InvalidActionException::class)))
            )
            ->addToNamespace($this->namespace_ ?? self::DEFAULT_NAMESPACE);

        // Returns the builded component
        return PHPScript(
            $component->getName(),
            $component,
            ComponentBuilderHelpers::rebuildComponentPath(
                $this->namespace_ ?? self::DEFAULT_NAMESPACE,
                $this->path_ ?? self::DEFAULT_PATH
            ),
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
}
