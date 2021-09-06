<?php

use Doctrine\DBAL\DriverManager;
use Drewlabs\ComponentGenerators\DatabaseSchemaReverseEngineeringRunner;

require __DIR__ . '/vendor/autoload.php';

$connection = DriverManager::getConnection([
    "dbname" => 'lifecontrolrdb',
    "host" => 'localhost',
    "port" => '3306',
    "user" => 'lifecontrol',
    "password" => 'homestead',
    "driver" => 'pdo_mysql',
    "charset" => 'UTF8',
]);
$schemaManager =  $connection->createSchemaManager();
$schemaManager->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

dd(iterator_to_array((new DatabaseSchemaReverseEngineeringRunner(
    $connection->createSchemaManager(),
    __DIR__ . '/examples/src/lib/'
))->tableListFilterFunc(function ($table) {
    return !(drewlabs_core_strings_contains($table->getName(), 'auth_') ||
        drewlabs_core_strings_starts_with($table->getName(), 'acl_') ||
        ($table->getName() === 'accounts_verifications') ||
        drewlabs_core_strings_contains($table->getName(), 'file_authorization') ||
        drewlabs_core_strings_contains($table->getName(), 'uploaded_file') ||
        drewlabs_core_strings_contains($table->getName(), 'server_authorized_') ||
        drewlabs_core_strings_contains($table->getName(), 'shared_files') ||
        drewlabs_core_strings_contains($table->getName(), 'form_') ||
        ($table->getName() === 'forms') ||
        ($table->getName() === 'migrations') ||
        (drewlabs_core_strings_starts_with($table->getName(), 'log_model_')));
})->run()));

// DataType To Fluent Rules helper
// dump(array_map(function ($type) {
//     return DataTypeToFluentValidationRulesHelper::getRule($type);
// }, [
//     'text:65535',
//     'string:100',
//     'datetime:Y-m-d H:i:s',
//     'date',
//     'bigint',
//     'int',
//     'integer:999',
//     'decimal',
//     // Foreign key columns definition rules
//     new ORMColumnForeignKeyConstraintDefinition([
//         'local_table' => "auth_user_details",
//         'columns' => [
//             "user_id"
//         ],
//         'foreign_table' => "auth_users",
//         'foreign_columns' => [
//             "id"
//         ],
//     ]),
//     // Unique columns definition rules
//     new ORMColumnUniqueKeyDefinition([
//         'table' => 'posts',
//         'columns' => 'title'
//     ])
// ]));
