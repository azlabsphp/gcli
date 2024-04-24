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
     * @var array
     */
    private $relations = [];

    /**
     * Creates class instance.
     */
    public function __construct(string $name, array $post, array $put, array $relations)
    {
        $this->name = $name;
        $this->postBody = array_reduce(
            array_filter(array_keys($post), static function ($item) {
                return !\in_array($item, ['created_at', 'updated_at', 'id'], true);
            }),
            static function ($carry, $current) use ($post) {
                $carry[] = [
                    'name' => $current,
                    'value' => '',
                    'type' => 'text',
                    'required' => in_array('nullable', $post[$current] ?? []) ? false : true
                ];

                return $carry;
            },
            []
        );
        $this->putBody = array_reduce(
            array_filter(array_keys($put), static function ($item) {
                return !\in_array($item, ['created_at', 'updated_at', 'id'], true);
            }),
            static function ($carry, $current) use ($put) {
                $carry[] = [
                    'name' => $current,
                    'value' => '',
                    'type' => 'text',
                    'required' => in_array('nullable', $put[$current] ?? []) ? false : true
                ];

                return $carry;
            },
            []
        );
        $this->relations = $relations;
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

    /**
     * Returns the list of supported relation on the request.
     *
     * @return string[]
     */
    public function getRelations()
    {
        return $this->relations;
    }
}
