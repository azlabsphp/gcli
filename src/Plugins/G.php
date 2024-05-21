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

namespace Drewlabs\GCli\Plugins;

use Drewlabs\GCli\Contracts\ProvidesModuleMetadata;
use Drewlabs\GCli\Contracts\Type;

// Plugins files generator class implementation
// It provides method to register plugins and generate source codes using the plugin implementation
class G
{
    /** @var Plugin[] */
    private $plugins = [];

    /** @var static */
    private static $instance;

    // Private constructor
    private function __construct()
    {
    }

    /**
     * Get class singleton instance.
     *
     * @return G|static
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    /**
     * Add a plugin to list of generator.
     *
     * @return void
     */
    public function addPlugin(string $name, Plugin $plugin)
    {
        if (\in_array($name, array_keys($this->plugins), true)) {
            return;
        }
        $this->plugins[$name] = $plugin;
    }

    /**
     * Use the registered plugin to generate source code for the
     * list of provided type instances.
     *
     * @param Type|Type[] $types
     *
     * @return void
     */
    public function generate($types)
    {
        $types = \is_array($types) ? $types : [$types];
        foreach ($this->plugins as $plugin) {
            foreach ($types as $value) {
                $plugin->generate($value, $value instanceof ProvidesModuleMetadata ? $value->getModuleName() : null);
            }
        }
    }
}
