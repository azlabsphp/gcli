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

namespace Drewlabs\GCli\Traits;

trait ViewModelBuilder
{
    /**
     * Indicates whether to add the updateRules() method to the generated class.
     *
     * @var bool
     */
    private $isSingleActionValidator = false;

    /**
     * List of rules to add to the view model.
     *
     * @var array
     */
    private $rules = [];

    /**
     * List of update rules to add to the view model.
     *
     * @var array
     */
    private $updateRules = [];

    /**
     * Provides methods or traits for writing inputs to / reading inputs from the viewmodel.
     *
     * @var bool
     */
    private $hasInputsTraits = false;

    public function setRules(array $rules = [])
    {
        if (null !== $rules) {
            $this->rules = $rules;
        }

        return $this;
    }

    /**
     * returns the list of rules during create action.
     *
     * @return array
     */
    public function getRules()
    {
        return $this->rules ?? [];
    }

    public function setUpdateRules(array $rules = [])
    {
        if (null !== $rules) {
            $this->updateRules = $rules;
        }

        return $this;
    }

    /**
     * returns list of rules during update action.
     *
     * @return array
     */
    public function getUpdateRules()
    {
        return $this->updateRules ?? [];
    }

    public function asSingleActionValidator()
    {
        $this->isSingleActionValidator = true;

        return $this;
    }

    public function addInputsTraits()
    {
        $this->hasInputsTraits = true;

        return $this;
    }
}
