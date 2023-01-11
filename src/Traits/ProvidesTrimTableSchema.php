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

namespace Drewlabs\ComponentGenerators\Traits;


trait ProvidesTrimTableSchema
{
    /**
     * Remove the schema part from the table name
     * 
     * @param string $table 
     * @param string|null $schema 
     * @return string 
     */
    public static function trimschema(string $table, string $schema = null)
    {
        if (is_null($schema)) {
            return $table;
        }
        return self::prefixed($table, $schema . '_') ?
            substr($table ?? '', strlen(sprintf('%s_', $schema))) : (self::prefixed($table, $schema) ?
                substr($table ?? '', strlen(sprintf('%s', $schema))) :
                $table);
    }

    /**
     * Checks if the table has a schema prefix
     * 
     * @param string $table 
     * @param string $needle 
     * @return bool 
     */
    public static function prefixed(string $table, string $prefix)
    {
        if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
            return str_starts_with($table, $prefix);
        }
        return ('' === $prefix) || (mb_substr($table, 0, mb_strlen($prefix)) === $prefix);
    }
}
