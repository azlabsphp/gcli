<?php

namespace Drewlabs\GCli\Plugins;

// Plugins files generator class implementation
// It provides method to register plugins and generate source code using the plugin implementation
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
     * Get class singleton instance
     * 
     * @return G|static 
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new self;
        }
        return static::$instance;
    }


    /**
     * Add a plugin to list of generator 
     * 
     * @param Plugin $plugin 
     * @return void 
     */
    public function addPlugin(Plugin $plugin)
    {
        // In order not to register the same plugin twice
        if (in_array($plugin, $this->plugins)) {
            return;
        }
        $this->plugins[] = $plugin;
    }

    public function run()
    {
        // TODO: Implement the generator
        foreach ($this->plugins as $plugin) {
            $components = $plugin->getComponents();
        }

    }
}