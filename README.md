# drewlabs-mvc-components-generator

This project uses the code generator package to create components like controllers, services, models etc...

## Installation

The generator project is not an official php package and must be used as github PHP package. To configure and install required dependencies one is require to use composer to manage dependencies in it project. At the root of your project define a composer.json file containing the requirements below:

```json
    "require": {
        "drewlabs/core-helpers": "^2.0"
    },
    "require-dev": {
        // Other project dev dependencies
        // We only install the generator in dev mode because we don't want to ship it
        // when running in production
        "drewlabs/code-generator": "^0.2|^2.0",
        "drewlabs/component-generators": "^2.10"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:liksoft/drewlabs-php-core-helpers.git"
        },
        {
            "type": "vcs",
            "url": "git@github.com:liksoft/drewlabs-mvc-components-generator.git"
        }
    ]
```

**Note**
The generated code for controllers, database and Services handlers depends on some package that are internal to drewlabs namespaces and therefore must be added as well to your composer.json file. Please add the following package to your project and to install the required dependencies:

```json
    //...
    "require": {
        //...
        "drewlabs/contracts": "^2.0",
        "drewlabs/core": "^2.0",
        "drewlabs/database": "^2.0",
        "drewlabs/http": "^2.0",
        "drewlabs/support": "^2.0",
        "drewlabs/php-value": "^0.1.5"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:liksoft/drewlabs-php-contracts.git"
        },
        {
            "type": "vcs",
            "url": "git@github.com:liksoft/drewlabs-php-core.git"
        },
        {
            "type": "vcs",
            "url": "git@github.com:liksoft/drewlabs-php-packages-database.git"
        },
        {
            "type": "vcs",
            "url": "git@github.com:liksoft/drewlabs-laravel-http.git"
        },
        {
            "type": "vcs",
            "url": "git@github.com:liksoft/drewlabs-php-support.git"
        },
        {
            "type": "vcs",
            "url": "git@github.com:liksoft/drewlabs-php-value.git"
        }
    ]
```

## Usage

### Programming API

This section present you with PHP classes API for creating controllers, Services, Model, ViewModel and Data Transfert Object

#### The Controller Builder

This package provide you with a controller class builder that can be used to build a resource controller, an invokable controller or a pre-made CRUD controller.

```php

// ...
use function Drewlabs\ComponentGenerators\Proxy\ComponentsScriptWriter;
use function Drewlabs\ComponentGenerators\Proxy\MVCControllerBuilder;
// ...

// This code creates an invokable controller
ComponentsScriptWriter(__DIR__ . '/examples/src')->write(
    (MVCControllerBuilder())
        ->bindServiceClass(
            "App\\Services\\PersonsService"
        )
        ->asInvokableController()
        ->build()
);

// This code creates a resource controller and bind it to a model
// If conroller name is not provided, it's generated from the model name
ComponentsScriptWriter(__DIR__ . '/examples/src/')->write(
    (MVCControllerBuilder('PostsController', '\\App\\Http\\Controllers\\Common'))
        ->bindServiceClass(
            "App\\Services\\PersonsService"
        )
        ->build()
)
```

#### Service class builder

A service is like a delegate that handle controller action and has access to the database model.

You can create a service that provides empty implementation or is pre-filled with CRUD operations.

```php

// ...
use function Drewlabs\ComponentGenerators\Proxy\ComponentsScriptWriter;
use function Drewlabs\ComponentGenerators\Proxy\MVCServiceBuilder;
// ...

// Creating a prefilled service with name derived from a model class name
ComponentsScriptWriter(__DIR__ . '/examples/src/')->write(
    (MVCServiceBuilder())
        ->bindModel(
            "App\\Models\\Human"
        )
        ->asCRUDService()
        ->build()
)

// Creates a simple service with only a handle method
ComponentsScriptWriter(__DIR__ . '/examples/src/')->write(
    (MVCServiceBuilder())
        ->bindModel(
            "App\\Models\\Person"
        )
        ->build()
)
```

#### View Model class builder

A view model is a class that wrap arround user provided values, validation rules and if possible a reference to the authenticated user.

```php

// ...
use function Drewlabs\ComponentGenerators\Proxy\ComponentsScriptWriter;
use function Drewlabs\ComponentGenerators\Proxy\ViewModelBuilder;
// ...

// Creating a fully complete view model
ComponentsScriptWriter(__DIR__ . '/examples/src/')->write(
    (ViewModelBuilder())
        ->bindModel(
            "App\\Models\\Person"
        )
        ->addInputsTraits()
        ->addFileInputTraits()
        ->addAuthenticatableTraits()
        ->setRules([
            'firstname' => 'required|max:50',
            'lastname' => 'required|max:50'
        ])
        ->build()
);

// Create a view model that can only be used with the validator to validate user input
ComponentsScriptWriter(__DIR__ . '/examples/src/'))->write(
    (ViewModelBuilder())
        ->bindModel(
            "App\\Models\\Person"
        )
        ->asSingleActionValidator()
        ->setRules([
            'firstname' => 'required|max:50',
            'lastname' => 'required|max:50'
        ])
        ->build()
)
```

#### Model class builder

A database model is like en entity manager class that interact with database on your behalf. They packahe provides an implementation of the Eloquent ORM model.

This implementation use the drewlabs/database package for the builder and it suppose that package is install as dependency. You are free to provide an implementation of your on builder.

```php
// ....
use function Drewlabs\ComponentGenerators\Proxy\ComponentsScriptWriter;
use function Drewlabs\ComponentGenerators\Proxy\EloquentORMModelBuilder;
use function Drewlabs\ComponentGenerators\Proxy\ORMColumnDefinition;
use function Drewlabs\ComponentGenerators\Proxy\ORMModelDefinition;
//...

// Building a model
ComponentsScriptWriter(__DIR__ . '/examples/src'))->write(EloquentORMModelBuilder(ORMModelDefinition([
    'primaryKey' => 'id',
    'name' => null,
    'table' => 'persons',
    'columns' => [
        ORMColumnDefinition([
            'name' => 'firstname',
            'type' => 'string'
        ]),
        ORMColumnDefinition([
            'name' => 'lastname',
            'type' => 'string'
        ])
    ],
    'increments' => false,
    'namespace' => "App\\Models"
]))->build()


// To build a model as a view model
ComponentsScriptWriter(__DIR__ . '/examples/src')->write((EloquentORMModelBuilder(ORMModelDefinition([
    'primaryKey' => 'id',
    'name' => null,
    'table' => 'humans',
    'columns' => [
        ORMColumnDefinition([
            'name' => 'firstname',
            'type' => 'string'
        ]),
        ORMColumnDefinition([
            'name' => 'lastname',
            'type' => 'string'
        ])
    ],
    'increments' => false,
    'namespace' => "App\\Models"
])))->asViewModel()->build();
```

## Laravel Commands interfaces

The package offers some laravel framework commands for easily creating component from your terminal application.
Those commands are:

### drewlabs:mvc:create command

This command allow devolpper to generate an entire api stack from database tables, with a pre-defined structure. The generated output is configurable using artisan command interface input:

- Creating mvc component along with controllers and routes

```sh
php artisan drewlabs:mvc:create --http 
```

#### Disabling caching

By default the command use caching to optimize the task when generating more than once required components. using the command below, the command will ignore the cache and re-generate previously generated files:

```sh
php artisan drewlabs:mvc:create --http --force
```

#### Removing schema prefix

In some application, databases are prefixed using schema name. Generated code will mostly add the schema prefix to classes and interfaces. To prevent such output, developper can use `--schema` option to allow the tell the command to trim the schema part from generated classes:

```sh
php artisan drewlabs:mvc:create --http --schema=test
```

#### Adding middleware

Sometimes developpers might want to group generated routes in a given middleware. The command interface provide an option to specify the middleware to use when grouping generated route using:

```sh
php artisan drewlabs:mvc:create --http --middleware=auth
```

#### Table filters

The command interface also support an `--only` option wich allow developpers to specify the list of table to include when generating code:

```sh
php artisan drewlabs:mvc:create --http --only=users
```

**Warning** The `--only` option is in an experimental status, and as it can rewrite your routing file removing previously generated routes.

#### Setting route file name

By default the routing file name used by the command is `web.php` located in the /routes directory of the laravel project. To override the default:

```sh
php artisan drewlabs:mvc:create --http --routingfilename=api.php
```

The command above use or create a file in the project /routes directory for routing.

#### Model relations

`drewlabs:mvc:create` provides a flag for generating relation method definition while generating model class definition. Using `--relations` flag, developpers can generate model with corresponding `relations`.

```sh
php artisan drewlabs:mvc:create --http --relations
```

**FAQ** How can model relation methods can be customized ?

The command support argument for model relation customization. using `--manytomany`, `--toones`, `--manythroughs`, `--onethroughs`, developpers are able to specify relation that are `many to many`, `one to one`, `many through` and `one through` relation respectively.

The syntax for various customization are:

- `manytomany`
  `source_table->pivot_table->destination_table:method`

```sh
php artisan drewlabs:mvc:create --http --relations --manytomany=posts->post_comments->comments --manytomany=posts->post_tags->tags
```

**Note** `[method]` part of the syntax can be omitted and will be generated by the command.

- `toones`
  `source_table->destination_table:method`

```sh
php artisan drewlabs:mvc:create --http --relations --toones=employees->employee_managers
```

**Note** `[method]` part of the syntax can be omitted and will be generated by the command.

- `onethroughs` & `manythroughs`
  `source_table->pivot_table->destination_table:method`

**Note** `[method]` part of the syntax can be omitted and will be generated by the command.

```sh
php artisan drewlabs:mvc:create --http --relations --manythroughs=post_types->posts->comments:comments
```

#### Policy & Guard

From version `2.9`, `drewlabs:mvc:create` command suport flag for adding policy guard definition to your project with default to allowing every controller action using `authorize` method. To generate policy classes use the `--policies` flag when running command.

**Note** A service provider class `[\App\Providers\PoliciesServiceProvider::class]` class is generated at the end of the command output. Please add it to let laravel know how to guard your model classes using generated policies.

```sh
php artisan drewlabs:mvc:create --http --policies
```

**Note**
To preview the list of available options, please use the `php artisan drewlabs:mvc:create --help`

### Create a database model

```sh
# Create a database model
php artisan drewlabs:mvc:make:model --table=comments --columns="label|string" --columns="description|text" --columns="likes|number" --hidden=likes --path=src --namespace="\\Application\\Models" --appends=posts
```

### Create a php class

**Note** Use `php artisan drewlabs:mvc:make:class --help` command view the list of option available

```ssh
php artisan drewlabs:mvc:make:class --path='src' --name=Post
```

### Create a data transfert object

**Note** Use `php artisan drewlabs:mvc:make:dto --help` command view the list of option available

```sh
php artisan drewlabs:mvc:make:dto --path=src --namespace=\\Application\\Dto
```

### Create a MVC service

**Note** Use `php artisan drewlabs:mvc:make:service --help` command view the list of option available

```sh
php artisan drewlabs:mvc:make:service --model=\\Application\\Models\\Comment --asCRUD --path=src
```

### Create a MVC view model

**Note** Use `php artisan drewlabs:mvc:make:viewmodel --help` command view the list of option available

```sh
php artisan drewlabs:mvc:make:viewmodel --model=\\Application\\Models\\Comment --single --path=src
```

### Create a MVC controller

**Note** Use `php artisan drewlabs:mvc:make:controller --help` command view the list of option available

```sh
php artisan drewlabs:mvc:make:controller --name=Posts --path=src --model="\\Application\\Models\\Comment"
```
