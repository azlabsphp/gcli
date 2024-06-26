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

/**
 * @deprecated
 */
interface ViewModelRulesFactory
{
    /**
     * Create view model rules array based.
     * If $update parameter is provided, the returns rules
     * will not require most attributes.
     *
     * @return array
     */
    public function createRules(bool $update = false);
}
