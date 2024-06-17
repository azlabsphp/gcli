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

namespace Drewlabs\GCli\Extensions\Console\Commands;

use Drewlabs\GCli\Contracts\ProvidesModuleMetadata;
use Drewlabs\GCli\DBAL\DriverOptionsFactory;
use Drewlabs\GCli\DBAL\T\IteratorFactory;
use Drewlabs\GCli\Extensions\ProgressBar;
use Drewlabs\GCli\Options;
use Drewlabs\GCli\Plugins\TSModule\V1\Plugin;
use Illuminate\Console\Command;

/**
 * @property \Illuminate\Contracts\Console\Application|\Illuminate\Contracts\Foundation\Application $laravel
 * @property mixed output
 *
 * @method bool                   confirm($question, $default = false)
 * @method array|string|boo|null  option($key = null)
 * @method string|array           choice($question, array $choices, $default = null, $attempts = null, $multiple = false)
 * @method string|array|bool|null option(string $key)
 * @method array                  options()
 * @method void                   info($string, $verbosity = null)
 * @method void                   warn($string, $verbosity = null)
 * @method void                   error($string, $verbosity = null)
 */
class MakeTsModuleCommand extends Command
{
    /** @var string */
    protected $signature = 'gcli:make:ts '
        . '{--srcPath= : Path to the business logic component folder}'
        . '{--connectionURL= : Database connection URL}'
        . '{--dbname= : Database name}'
        . '{--host= : Database host name}'
        . '{--port= : Database host port number}'
        . '{--user= : Database authentication user}'
        . '{--password= : Database authentication password}'
        . '{--driver= : Database driver name}'
        . '{--server_version= : Database server version}'
        . '{--charset= : Database Connection collation}'
        . '{--unix_socket= : Unix socket to use for connections}'
        . '{--tables=* : List of tables not to be included in the generated output}'
        . '{--excepts=* : List of tables not to be excluded in the generated output}'
        . '{--schema= : Database tables schema prefix}'
        . '{--camelize : Rename table columns into their camel case corresponding value}'
        . '{--output= : Directory where source code should be written}'
        . '{--force : Force rewrite of existing classes}';

    /** @var string */
    protected $description = 'Generates Typescript built-type component from database tables';

    /**
     * Creates laravel command instance.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Handle command execution.
     *
     * @throws \Exception
     *
     * @return void
     */
    public function handle()
    {
        $options = new Options($this->options() ?? []);
        $excludes = array_merge($options->get('excludes', []) ?? [], ['migrations']);
        $tables = $options->get('tables', []);
        $factory = new DriverOptionsFactory();
        $plugin = new Plugin($options->get('output') ?? $this->laravel->publicPath('assets/lib'), boolval($this->option('camelize')));
        $dbOptions = $factory->createOptions($options, static function ($key, $default = null) {
            return config($key, $default);
        });
        $factory = new IteratorFactory('App', $options->get('schema'), $dbOptions->get(), $tables, $excludes);
        $values = iterator_to_array($factory->createIterator()->getIterator());
        $progress = new ProgressBar($this->output->createProgressBar(\count($values)));

        $this->info('Creating typescript module files, please wait...');
        $progress->start();
        /** @var \Traversable<\Drewlabs\GCli\Contracts\ORMModelDefinition> $values */
        foreach ($values as $value) {
            $plugin->generate($value, $value instanceof ProvidesModuleMetadata ? $value->getModuleName() : null);
            $progress->advance();
        }
        $progress->finish();

        $this->info("\nTask completed. Thank U for using the gcli:make:ts utility 😉.");
    }
}
