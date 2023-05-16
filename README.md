# drewlabs-mvc-components-generator

This project uses the code generator package to create components like controllers, services, models etc...

## Installation

The generator project is not an official php package and must be used as github PHP package. To configure and install required dependencies one is require to use composer to manage dependencies in it project. At the root of your project define a composer.json file containing the requirements below:

```json
    "require": {
        // other project dependencies
        "drewlabs/filesystem": "^1.0",
        "drewlabs/psr7-stream": "^1.0",
        "drewlabs/core-helpers": "^2.0",
        "drewlabs/php-value": "^0.1.5"
    },
    "require-dev": {
        // Other project dev dependencies
        // We only install the generator in dev mode because we don't want to ship it
        // when running in production
        "drewlabs/code-generator": "^2.0",
        "drewlabs/component-generators": "^2.1.25"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:liksoft/drewlabs-php-core-helpers.git"
        },
        {
            "type": "vcs",
            "url": "git@github.com:liksoft/drewlabs-php-filesystem.git"
        },
        {
            "type": "vcs",
            "url": "git@github.com:liksoft/drewlabs-psr7-stream.git"
        },
        {
            "type": "vcs",
            "url": "git@github.com:liksoft/drewlabs-mvc-components-generator.git"
        },
        {
            "type": "vcs",
            "url": "git@github.com:liksoft/drewlabs-php-code-generator.git"
        },
        {
            "type": "vcs",
            "url": "git@github.com:liksoft/drewlabs-php-value.git"
        }
    ]
```

**Note**
The generated code for controllers, database and Services handlers depends on some package that are internal to drewlabs namespaces and therefore must be added as well to your composer.json file. Please consult the github repository below to see the configuration for each of these packages:

* https://github.com/liksoft/drewlabs-php-packages-database -> Provide classes that enhance the default Laravel Eloquent ORM
* https://github.com/liksoft/drewlabs-laravel-http/tree/v2-dev-branch -> Add classes and interfaces for working with HTTP Requests
* https://github.com/liksoft/drewlabs-php-support/tree/v2-dev-branch -> Provides utility classes required by the generated code

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

The package offers some laravel framework commands for easily creating component from your terminal application. Those commands are:

* `php artisan drewlabs:mvc:create`
  Generate web service components (Model, Service, Controller, ViewModel, Data Transfert Object) from a database configuration.
* `php artisan drewlabs:mvc:make:class`
  Generates a new PHP class definition.
* `php artisan drewlabs:mvc:make:controller`
  Generates a controller class with a predefined structure.
* `php artisan drewlabs:mvc:make:dto`
  Generate a Data transfert object class definition.
* `php artisan drewlabs:mvc:make:model`
  Generates a database model class definition.
* `php artisan drewlabs:mvc:make:service`
  Generates a Service component class definition.
* `php artisan drewlabs:mvc:make:viewmodel`
  Generate a validation view model class defininition.

### Examples

```sh
# Create a database model
php artisan drewlabs:mvc:make:model --table=comments --columns="label|string" --columns="description|text" --columns="likes|number" --hidden=likes --path=src --namespace="\\Application\\Models" --appends=posts

# Create a php class
php artisan drewlabs:mvc:make:class --path='src' --name=Post

# Create a data transfert class
php artisan drewlabs:mvc:make:dto --path=src --namespace=\\Application\\Dto

# Create a MVC service
php artisan drewlabs:mvc:make:service --model=\\Application\\Models\\Comment --asCRUD --path=src

# Create a MVC view model
php artisan drewlabs:mvc:make:viewmodel --model=\\Application\\Models\\Comment --single --path=src

# Create a MVC view model
php artisan drewlabs:mvc:make:viewmodel --name=Post --single --path=src

# Create a MVC controller
php artisan drewlabs:mvc:make:controller --name=Posts --path=src --model="\\Application\\Models\\Comment"
```
