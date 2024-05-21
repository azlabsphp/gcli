<?php

namespace Drewlabs\GCli\DBAL;

use Closure;
use Drewlabs\GCli\DBDriverOptions;
use Drewlabs\GCli\Options;

class DriverOptionsFactory
{

    /**
     * Creates database driver options instance
     * 
     * @param Options $options 
     * @param Closure|null $resolveFn 
     * @return DBDriverOptions 
     */
    public function createOptions(Options $options, \Closure $resolveFn = null): DBDriverOptions
    {
        $resolveFn = $resolveFn ?? static function ($_, $default = null) {
            return $default;
        };
        if (null !== ($url = $options->get('connectionURL'))) {
            return DBDriverOptions::new(['url' => $url])->get();
        }
        $default_driver = $resolveFn('database.default') ?? 'pdo_sqlite';
        if (!is_null($db_driver = $options->get('driver'))) {
            $driver = self::hasPrefix($db_driver, 'pdo') ? $db_driver : sprintf('pdo_%s', $db_driver);
        } else {
            $driver = self::hasPrefix($default_driver, 'pdo') ? $default_driver : sprintf('pdo_%s', $default_driver);
        }
        $database = $options->get('dbname') ?? $resolveFn("database.connections.$default_driver.database");
        $port = $options->get('port') ?? $resolveFn("database.connections.$default_driver.port");
        $username = $options->get('user') ?? $resolveFn("database.connections.$default_driver.username");
        $host = $options->get('host') ?? $resolveFn("database.connections.$default_driver.host");
        $password = $options->get('password') ?? $resolveFn("database.connections.$default_driver.password");
        $charset = $options->get('charset') ?? ('pdo_mysql' === $driver ? 'utf8mb4' : ('pdo_sqlite' === $driver ? null : 'utf8'));
        $server_version = $options->get('server_version') ?? null;

        return DBDriverOptions::new([
            'dbname' => $database,
            'host' => 'pdo_sqlite' === $driver ? null : $host ?? '127.0.0.1',
            'port' => 'pdo_sqlite' === $driver ? null : $port ?? 3306,
            'user' => $username,
            'password' => $password,
            'driver' => $driver ?? 'pdo_sqlite',
            'server_version' => $server_version,
            'charset' => $charset,
        ]);
    }

    /**
     * Checks if the table has a schema prefix.
     *
     * @return bool
     */
    private static function hasPrefix(string $table, string $prefix)
    {
        if (version_compare(\PHP_VERSION, '8.0.0') >= 0) {
            return str_starts_with($table, $prefix);
        }
        return ('' === $prefix) || (mb_substr($table, 0, mb_strlen($prefix)) === $prefix);
    }
}