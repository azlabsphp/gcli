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
use Drewlabs\CodeGenerator\Models\PHPClass;
use Drewlabs\CodeGenerator\Models\PHPClassMethod;
use Drewlabs\CodeGenerator\Models\PHPClassProperty;
use Drewlabs\CodeGenerator\Models\PHPFunctionParameter;
use Drewlabs\CodeGenerator\Types\PHPTypesModifiers;
use Drewlabs\ComponentGenerators\Contracts\ComponentBuilder;
use Drewlabs\ComponentGenerators\PHP\PHPScriptFile;
use Drewlabs\ComponentGenerators\Traits\HasNameAttribute;
use Drewlabs\ComponentGenerators\Traits\HasNamespaceAttribute;
use Drewlabs\Contracts\Data\DML\DMLProvider;
use Drewlabs\Contracts\Support\Actions\Action;
use Drewlabs\Contracts\Support\Actions\ActionHandler;
use Drewlabs\Contracts\Support\Actions\ActionPayload;
use Drewlabs\Contracts\Support\Actions\ActionResult;
use Drewlabs\Contracts\Support\Actions\Exceptions\InvalidActionException;
use Drewlabs\Packages\Database\EloquentDMLManager;
use Illuminate\Support\Pluralizer;

class ServiceClassBuilder implements ComponentBuilder
{
    use HasNameAttribute;
    use HasNamespaceAttribute;

    public const ACTION_RESULT_FUNCTION_PATH = 'Drewlabs\\Core\\Support\\Proxy\\ActionResult';

    /**
     * @var string
     */
    private const DEFAULT_NAME = 'TestService';

    /**
     * @var string
     */
    private const DEFAULT_PATH = 'app/Services/';

    /**
     * Service default class namespace.
     *
     * @var string
     */
    private const DEFAULT_NAMESPACE = 'App\\Services';

    /**
     * @var bool
     */
    private $asCRUD_ = false;

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

    public function bindModel(string $model)
    {
        if (empty($model)) {
            return $this;
        }
        $is_class_path = drewlabs_core_strings_contains($model, '\\'); // && class_exists($model); To uncomment if there is need to validate class existence
        $model_name = 'Test';
        $model_name = $is_class_path ? array_reverse(explode('\\', $model))[0] : (drewlabs_core_strings_contains($model, '\\') ? array_reverse(explode('\\', $model))[0] : $model);
        $name = drewlabs_core_strings_as_camel_case(Pluralizer::singular($model_name)).'Service';
        $this->setName($name);

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
            $this->asCRUD_ ? '$payload = $action->payload()' : null,
            $this->asCRUD_ ? '$payload = $payload instanceof ActionPayload ? $payload->toArray() : (is_array($payload) ? $payload : [])' : null,
            $this->asCRUD_ ? '' : null,
            '// Handle switch statements',
            'switch (strtoupper($action->type())) {',
            "\tcase \"CREATE\":",
            "\t\t//Create handler code goes here",
            $this->asCRUD_ ? "\t\t\$payload = null !== \$callback ? array_merge(\$payload, [\$callback]) : \$payload" : null,
            $this->asCRUD_ ? "\t\treturn ActionResult(\$this->dbManager->create(...\$payload))" : "\t\treturn",
            "\tcase \"UPDATE\":",
            "\t\t//Update handler code goes here",
            $this->asCRUD_ ? "\t\t\$payload = null !== \$callback ? array_merge(\$payload, [\$callback]) : \$payload" : null,
            $this->asCRUD_ ? "\t\treturn ActionResult(\$this->dbManager->update(...\$payload))" : "\t\treturn",
            "\tcase \"DELETE\":",
            "\t\t//Delete handler code goes here",
            $this->asCRUD_ ? "\t\treturn ActionResult(\$this->dbManager->delete(...\$payload))" : "\t\treturn",
            "\tcase \"SELECT\":",
            "\t\t//Select handler code goes here",
            $this->asCRUD_ ? "\t\t\$payload = null !== \$callback ? array_merge(\$payload, [\$callback]) : \$payload" : null,
            $this->asCRUD_ ? "\t\treturn ActionResult(\$this->dbManager->select(...\$payload))" : "\t\treturn",
            "\tdefault:",
            "\t\t//Provides default handler or throws exception",
            $this->asCRUD_ ? "\t\tthrow new InvalidActionException(\"This \" . __CLASS__ . \" can only handle CREATE,DELETE,UPDATE AND SELECT actions\")" : "\t\treturn",
            '}',
        ];
        /**
         * @var BluePrint|PHPClass
         */
        $component = (new PHPClass($this->name_ ?? self::DEFAULT_NAME))
            ->addClassPath(EloquentDMLManager::class);

        if ($this->asCRUD_) {
            $component = $component->addClassPath(ActionPayload::class)
                ->addFunctionPath(self::ACTION_RESULT_FUNCTION_PATH)
                ->addClassPath(InvalidActionException::class);
        }

        $component->addImplementation(ActionHandler::class)
            ->asFinal()
            ->addProperty(
                new PHPClassProperty(
                    'dbManager',
                    DMLProvider::class,
                    PHPTypesModifiers::PRIVATE,
                    null,
                    'Database query manager'
                )
            )
            // Add the class constructor
            ->addMethod(
                (new PHPClassMethod(
                    '__construct',
                    [
                        (new PHPFunctionParameter(
                            'manager',
                            DMLProvider::class,
                            null
                        ))->asOptional(),
                    ],
                    'self',
                    PHPTypesModifiers::PUBLIC,
                    'Creates an instance of the Service'
                ))->addLine(
                    '$this->dbManager = $manager ?? new EloquentDMLManager(Test::class)'
                )
            )
            // Add Handler method
            ->addMethod(
                array_reduce(array_filter($handlMethodLines, static function ($line) {
                    return null !== $line;
                }), static function (CallableInterface $carry, $curr) {
                    return $carry->addLine($curr);
                }, (new PHPClassMethod(
                    'handle',
                    [
                        new PHPFunctionParameter(
                            'action',
                            Action::class,
                        ),
                        (new PHPFunctionParameter(
                            'callback',
                            '\Closure',
                        ))->asOptional(),
                    ],
                    ActionResult::class,
                    PHPTypesModifiers::PUBLIC,
                    '{@inheritDoc}'
                )))
            )
            ->addToNamespace($this->namespace_ ?? self::DEFAULT_NAMESPACE);

        // Returns the builded component
        return new PHPScriptFile(
            $component->getName(),
            $component,
            $this->path_ ?? self::DEFAULT_PATH
        );
    }
}
