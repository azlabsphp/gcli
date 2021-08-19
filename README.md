# drewlabs-mvc-components-generator

This project uses the code generator package to create components like controllers, services, models etc...

## Generate a controller class

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

## Generate a service class

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

## Generate a Validation  view model

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

## Creating a database model

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
