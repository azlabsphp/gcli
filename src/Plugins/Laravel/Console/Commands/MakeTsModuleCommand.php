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

namespace Drewlabs\GCli\Plugins\Laravel\Console\Commands;

use Drewlabs\GCli\Console\Options;
use Drewlabs\GCli\Console\ProgressBar;
use Drewlabs\GCli\Contracts\HasModuleMetadata;
use Drewlabs\GCli\DBAL\DriverOptionsFactory;
use Drewlabs\GCli\DBAL\T\IteratorFactory;
use Drewlabs\GCli\Plugins\TSModule\V1\Plugin;
use Drewlabs\GCli\SQLDBCollector;
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
        . '{--manytomany=* :  List of * -> * relations. (ex - lefttable->middletable->righttable:name) }'
        . '{--toones=* :  List of 1 -> 1 relations. (ex - lefttable->righttable:name) }'
        . '{--manythroughs=* :  List of 1 -> t -> * relations. (ex - lefttable->middletable->righttable:name) }'
        . '{--onethroughs=* :  List of 1 -> t -> 1 relations. (ex - lefttable->middletable->righttable:name) }'
        . '{--onetomany=* :  List of 1 -> * relations. (ex - lefttable->righttable:name) }'
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
        $plugin = new Plugin($options->get('output') ?? $this->laravel->publicPath('assets/lib'), boolval($this->option('camelize')));

        // #region Load database tables
        $toones = $this->flatten($options->get('toones', []));
        $manytomany = $this->flatten($options->get('manytomany', []));
        $onethroughs = $this->flatten($options->get('onethroughs', []));
        $manythroughs = $this->flatten($options->get('manythroughs', []));
        $oneToMany = $this->flatten($options->get('onetomany', []));
        $collector = SQLDBCollector::new(
            $manytomany,
            $toones,
            $oneToMany,
            $manythroughs,
            $onethroughs,
            $options->get('schema')
        )->withRelations();

        $dbOptions = DriverOptionsFactory::new()->createOptions($options, static function ($key, $default = null) {
            return config($key, $default);
        });
        $factory = new IteratorFactory('App', $options->get('schema'), $dbOptions->get(), $tables, $excludes);
        $dbConfig = $collector->collect($factory->createIterator());
        // #endregion Load database tables

        $progress = new ProgressBar($this->output->createProgressBar(\count($dbConfig->getTables())));

        $this->info('Creating typescript module files, please wait...');
        $progress->start();

        foreach ($dbConfig->getTables() as $value) {
            $tableType = $value->getType();
            $plugin->generate($tableType, $tableType instanceof HasModuleMetadata ? $tableType->getModuleName() : null);
            $progress->advance();
        }
        $progress->finish();

        $this->info("\nTask completed. Thank U for using the gcli:make:ts utility!😉.");
    }

    /**
     * Creates a generator of flatten array.
     *
     * @return array
     */
    private function flatten(array $composed): array
    {
        return iterator_to_array((function () use ($composed) {
            foreach ($composed as $relation) {
                if (str_contains((string) $relation, ',')) {
                    $values = explode(',', (string) $relation);
                    foreach ($values as $part) {
                        yield $part;
                    }
                    continue;
                }
                yield $relation;
            }
        })());
    }
}
