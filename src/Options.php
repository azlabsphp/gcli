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

namespace Drewlabs\ComponentGenerators;

use Drewlabs\ComponentGenerators\Exceptions\IOException;
use Drewlabs\ComponentGenerators\Exceptions\NotLoadedExtensionException;
use Drewlabs\Core\Helpers\Arr;
use RuntimeException;

class Options
{
    /**
     * 
     * @var array
     */
    private $options = [];

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * Load the option from yaml configuration
     * 
     * @param string $path 
     * @return self
     *  
     * @throws RuntimeException 
     */
    public static function yaml(string $path)
    {
        if (!\function_exists('yaml_parse')) {
            throw new NotLoadedExtensionException('yaml');
        }
        if (@is_dir($path = realpath($path)) && @is_file("$path" . \DIRECTORY_SEPARATOR . 'dbconfig.yml')) {
            $path = "$path" . \DIRECTORY_SEPARATOR . 'dbconfig.yml';
        } elseif (@is_dir($path) && @is_file("$path" . \DIRECTORY_SEPARATOR . 'dbconfig.yaml')) {
            $path = "$path" . \DIRECTORY_SEPARATOR . 'dbconfig.yaml';
        }
        if (!is_file($path)) {
            throw IOException::missing($path);
        }

        if (!is_readable($path)) {
            throw IOException::readable($path);
        }
        return new static((array) (yaml_parse(file_get_contents($path)) ?? []));
    }

    /**
     * Load options from a json configuration disk resource
     * 
     * @param string $path 
     * @return static 
     * @throws IOException 
     */
    public static function json(string $path)
    {
        $path = realpath($path);
        if (@is_dir($path) && @is_file("$path" . \DIRECTORY_SEPARATOR . 'libman.json')) {
            $path = "$path" . \DIRECTORY_SEPARATOR . 'libman.json';
        }
        if (!is_file($path)) {
            throw IOException::missing($path);
        }

        if (!is_readable($path)) {
            throw IOException::readable($path);
        }
        return new static(json_decode(file_get_contents($path), true) ?? []);
    }



    /**
     * Get an options value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed 
     */
    public function get(string $key, $default = null)
    {
        return Arr::get($this->options, $key, $default);
    }

    /**
     * Merge values into the existing options
     * 
     * @param mixed $value 
     * @param string|null $key 
     * @return self 
     */
    public function merge($value, string $key = null)
    {
        if (null === $key) {
            foreach ($value as $k => $v) {
                $this->mergeAt($k, $v);
            }
            return $this;
        }
        return $this->mergeAt($key, $value);
    }

    /**
     * Update the value at a given index of the options property
     * 
     * @param mixed $key 
     * @param mixed $value 
     * @return self 
     */
    private function mergeAt($key, $value)
    {
        if (null === ($result = $this->get($key))) {
            Arr::set($this->options, $key, $value);
            return $this;
        }
        Arr::set($this->options, $key, is_array($result) ?
            array_merge($result, is_array($value) ? $value : [$value]) :
            $value);
        return $this;
    }
}
