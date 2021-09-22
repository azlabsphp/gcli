<?php

namespace Drewlabs\ComponentGenerators\Extensions\Contracts;

interface Progress
{

    /**
     * Initialize the progresss element
     * 
     * @return void 
     */
    public function start(): void;

    /**
     * Advance the progress element 
     * 
     * @return void 
     */
    public function advance(): void;

    /**
     * Stop the progression. Indicates that the progress reach an end
     * 
     * @return void 
     */
    public function complete(): void;
}