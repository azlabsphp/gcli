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

namespace Drewlabs\GCli;

use Drewlabs\CodeGenerator\Contracts\Stringable;
use Drewlabs\Core\Helpers\Str;
use Drewlabs\GCli\Contracts\SourceFileInterface;
use Drewlabs\CodeGenerator\Contracts\NamespaceComponent;

final class PHPScript implements SourceFileInterface
{
    private const DEFAULT_HEADER = <<<EOT
/*
 * This file is auto generated using the Drewlabs Code Generator package (v2.3)
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
    private $strictType = false;

    /** @var string[] */
    private $headers;

    /** @var NamespaceComponent&Stringable */
    private $content;

    /** @var string */
    private $path;

    /** @var string */
    private $extension;

    /** @var ?string */
    private $name;

    /** @var string|null */
    private $namespace;

    public function __construct(
        string $name,
        NamespaceComponent&Stringable $content,
        string $path,
        string $extension = 'php'
    ) {
        $this->name = $name;
        $this->content = $content;
        $this->path = $path;
        $this->extension = $extension;
    }

    public function __toString(): string
    {
        // Insert PHP script start
        $parts[] = '<?php';
        $parts[] = '';
        // Add strict type definition if required
        if ($this->strictType) {
            $parts[] = 'declare(strict_types=1);';
        }
        // Add File headers
        $parts[] = null !== $this->headers ? implode(\PHP_EOL, $this->headers) : static::DEFAULT_HEADER;
        // Convert the stringeable content to string
        $parts[] = $this->content->__toString();

        return implode(\PHP_EOL, $parts);
    }

    public function setHeaders($headers)
    {
        $headers = !\is_string($headers) && !\is_array($headers) ? (array) $headers : $headers;
        $this->headers = \is_string($headers) ? explode(\PHP_EOL, $headers) : $headers;

        return $this;
    }

    public function withStrictType()
    {
        $this->strictType = true;

        return $this;
    }

    public function setNamespace(string $namespace)
    {
        $this->namespace = $namespace;

        return $this;
    }

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    public function getPath(): string
    {
        return sprintf(
            '%s%s%s.%s',
            $this->path,
            \DIRECTORY_SEPARATOR,
            $this->name,
            $this->extension
        );
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getName()
    {
        return Str::contains($this->name ?? '', '.') ?
            Str::before('.', $this->name) :
            $this->name ?? 'Test';
    }

    public function getClassPath()
    {
        return sprintf('%s\\%s', $this->getNamespace(), Str::camelize($this->getName()));
    }
}
