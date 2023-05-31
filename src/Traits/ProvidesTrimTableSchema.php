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

namespace Drewlabs\GCli\Traits;

trait ProvidesTrimTableSchema
{
    /**
     * Remove the schema part from the table name.
     *
     * @return string
     */
    public static function trimschema(string $table, ?string $schema = null)
    {
        if (null === $schema) {
            return $table;
        }

        return self::prefixed($table, $schema.'_') ?
            substr($table ?? '', \strlen(sprintf('%s_', $schema))) : (self::prefixed($table, $schema) ?
                substr($table ?? '', \strlen(sprintf('%s', $schema))) :
                $table);
    }

    /**
     * Checks if a string have a given prefix.
     *
     * @return bool
     */
    public static function prefixed(string $haystack, string $prefix)
    {
        if (version_compare(\PHP_VERSION, '8.0.0') >= 0) {
            return str_starts_with($haystack, $prefix);
        }

        return ('' === $prefix) || (mb_substr($haystack, 0, mb_strlen($prefix)) === $prefix);
    }

    /**
     * Checks if a string have a given suffix.
     *
     * @return bool
     */
    public static function suffixed(string $haystack, string $prefix)
    {
        if (version_compare(\PHP_VERSION, '8.0.0') >= 0) {
            return str_ends_with($haystack, $prefix);
        }

        return ('' === $prefix) || (mb_substr($haystack, -(int) mb_strlen($prefix)) === $prefix);
    }
}
