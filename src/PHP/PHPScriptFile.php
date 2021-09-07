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

namespace Drewlabs\ComponentGenerators\PHP;

use Drewlabs\CodeGenerator\Contracts\Stringable;
use Drewlabs\ComponentGenerators\Contracts\SourceFileInterface;
use Drewlabs\CodeGenerator\Contracts\NamespaceComponent;

class PHPScriptFile implements SourceFileInterface
{
    private const DEFAULT_HEADER = <<<EOT
/*
 * This file is auto generated using the Drewlabs Code Generator package
 *
 * (c) Sidoine Azandrew <contact@liksoft.tg>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/

EOT;

    /**
     * Script must be of strict types or not.
     *
     * @var bool
     */
    private $strictType_ = false;

    /**
     * Specify the script header definitions.
     *
     * @var string[]
     */
    private $headers_;

    /**
     * 
     * @var NamespaceComponent|Stringable
     */
    private $content_;

    /**
     * @var string
     */
    private $path_;

    /**
     * @var string
     */
    private $extension_;

    /**
     * Name of the PHP file.
     *
     * @var string
     */
    private $name_;

    /**
     * 
     * @var mixed
     */
    private $namespace_;

    public function __construct(
        string $name,
        Stringable $content,
        string $path,
        string $extension = 'php'
    ) {
        $this->name_ = $name;
        $this->content_ = $content;
        $this->path_ = $path;
        $this->extension_ = $extension;
    }

    public function __toString(): string
    {
        // Insert PHP script start
        $parts[] = '<?php';
        $parts[] = '';
        // Add strict type definition if required
        if ($this->strictType_) {
            $parts[] = 'declare(strict_types=1);';
        }
        // Add File headers
        $parts[] = null !== $this->headers_ ? implode(\PHP_EOL, $this->headers_) : self::DEFAULT_HEADER;
        // Convert the stringeable content to string
        $parts[] = $this->content_->__toString();

        return implode(\PHP_EOL, $parts);
    }

    public function setHeaders($headers)
    {
        $headers = !\is_string($headers) && !\is_array($headers) ? (array) $headers : $headers;
        $this->headers_ = \is_string($headers) ? explode(\PHP_EOL, $headers) : $headers;

        return $this;
    }

    public function withStrictType()
    {
        $this->strictType_ = true;

        return $this;
    }

    public function setNamespace(string $namespace)
    {
        $this->namespace_ = $namespace;
        return $this;
    }

    public function getNamespace(): ?string
    {
        return $this->namespace_;
    }

    public function getPath(): string
    {
        return sprintf(
            '%s%s%s.%s',
            $this->path_,
            \DIRECTORY_SEPARATOR,
            $this->name_,
            $this->extension_
        );
    }

    public function getContent()
    {
        return $this->content_;
    }

    public function getName()
    {
        return drewlabs_core_strings_contains($this->name_ ?? '', '.') ?
            drewlabs_core_strings_before('.', $this->name_) :
            $this->name_ ?? 'Test';
    }
}
