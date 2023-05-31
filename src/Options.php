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

namespace Drewlabs\GCli;

use Drewlabs\GCli\Exceptions\IOException;
use Drewlabs\GCli\Exceptions\NotLoadedExtensionException;
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
        $realpath = realpath($path);

        if (false === $realpath) {
            throw IOException::missing($path);
        }

        if (@is_dir($realpath) && @is_file("$realpath" . \DIRECTORY_SEPARATOR . 'input.yml')) {
            $realpath = "$realpath" . \DIRECTORY_SEPARATOR . 'input.yml';
        } elseif (@is_dir($realpath) && @is_file("$realpath" . \DIRECTORY_SEPARATOR . 'input.yaml')) {
            $realpath = "$realpath" . \DIRECTORY_SEPARATOR . 'input.yaml';
        }
        if (!is_file($realpath)) {
            throw IOException::missing($realpath);
        }

        if (!is_readable($realpath)) {
            throw IOException::readable($realpath);
        }
        return new static((array) (yaml_parse(file_get_contents($realpath)) ?? []));
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
        $realpath = realpath($path);

        if (false === $realpath) {
            throw IOException::missing($path);
        }

        if (@is_dir($realpath) && @is_file("$realpath" . \DIRECTORY_SEPARATOR . 'libman.json')) {
            $realpath = "$realpath" . \DIRECTORY_SEPARATOR . 'libman.json';
        }
        if (!is_file($realpath)) {
            throw IOException::missing($realpath);
        }

        if (!is_readable($realpath)) {
            throw IOException::readable($realpath);
        }
        return new static(json_decode(file_get_contents($realpath), true) ?? []);
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
