<?php

namespace Drewlabs\ComponentGenerators\Contracts;

use Drewlabs\CodeGenerator\Contracts\Stringable;

interface SourceFileInterface extends Writable, Stringable
{
    /**
     * Set the source code namespace
     * 
     * @param string $namespace 
     * @return self 
     */
    public function setNamespace(string $namespace);

    /**
     * Return the source code namespace
     * 
     * @return null|string 
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
     * Returns the name of the source file without the extension part
     * 
     * @return string 
     */
    public function getName();
}