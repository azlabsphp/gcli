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

namespace Drewlabs\GCli\Contracts;

use Drewlabs\CodeGenerator\Contracts\Stringable;

interface SourceFileInterface extends Writable, Stringable
{
    /**
     * Set the source code namespace.
     *
     * @return self
     */
    public function setNamespace(string $namespace);

    /**
     * Return the source code namespace.
     */
    public function getNamespace(): ?string;

    /**
     * Set the PHP headers linest.
     *
     * @param string|string[] $headers
     *
     * @return self
     */
    public function setHeaders($headers);

    /**
     * Returns the name of the source file without the extension part.
     *
     * @return string
     */
    public function getName();

    /**
     * Returns the content of the source file.
     *
     * @return NamespaceComponent
     */
    public function getContent();
}
