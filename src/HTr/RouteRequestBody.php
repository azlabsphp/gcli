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

namespace Drewlabs\GCli\HTr;

class RouteRequestBody
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $postBody;

    /**
     * @var array
     */
    private $putBody;

    /**
     * Creates class instance.
     */
    public function __construct(string $name, array $rules, array $updateRules)
    {
        $this->name = $name;
        $this->postBody = array_reduce(
            array_filter(array_keys($rules), function ($item) {
                return !in_array($item, ['created_at', 'updated_at', 'id']);
            }),
            static function ($carry, $current) {
                $carry[$current] = '';

                return $carry;
            },
            []
        );
        $this->putBody = array_reduce(
            array_filter(array_keys($updateRules), function ($item) {
                return !in_array($item, ['created_at', 'updated_at', 'id']);
            }),
            static function ($carry, $current) {
                $carry[$current] = '';

                return $carry;
            },
            []
        );
    }

    /**
     * returns the route name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * returns the default route rules.
     *
     * @return array
     */
    public function getPostBody()
    {
        return $this->postBody ?? [];
    }

    /**
     * return the update rules.
     *
     * @return array
     */
    public function getPutBody()
    {
        return $this->putBody ?? [];
    }
}
