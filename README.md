# Salesforce SOQL Builder

SOQL builder that simplifies the process of constructing complex queries to retrieve data from Salesforce databases.

## Installation

Ensure you have [composer](http://getcomposer.org) installed, then run the following command:

    composer require lyonstahl/soql-builder

That will fetch the library and its dependencies inside your vendor folder. Then you need to use the relevant class, for example:

```php
use LyonStahl\SoqlBuilder\SoqlBuilder;
```

## Features

-   Select
-   Conditionals (where)
-   Conditionals for date values
-   Grouped conditional statements
-   Where in
-   Where not in
-   Limit
-   Offset
-   Order By

## Usage

Builder has two entry points for comfortable static usage: `SoqlBuilder::select()` and `SoqlBuilder::from()`. Both methods return a `SoqlBuilder` instance.  
In any other context, you **must** call `addSelect()` and `setFrom()` to add the "SELECT" and "FROM" statements. See examples below.

```php
SoqlBuilder::select(['Id', 'Name', 'created_at'])
    ->setFrom('Account')
    ->where('Name', '=', 'Test')
    ->limit(20)
    ->orderBy('created_at', 'DESC')
    ->toSoql();
```

`> SELECT Id, Name, created_at FROM Account WHERE Name = 'Test' ORDER BY created_at DESC LIMIT 20`

```php
SoqlBuilder::from('Account')
    ->addSelect(['Id', 'Name'])
    ->where('Name', '=', 'Test')
    ->orWhere('Name', '=', 'Testing')
    ->toSoql();
```

`> SELECT Id, Name FROM Account WHERE Name = 'Test' OR Name = 'Testing'`

```php
SoqlBuilder::select(['Id', 'Name'])
    ->setFrom('Account')
    ->startWhere()
    ->where('Name', '=', 'Test')
    ->where('Testing__c', '=', true)
    ->endWhere()
    ->orWhere('Email__c', '=', 'test@test.com')
    ->toSoql();
```

`> SELECT Id, Name FROM Account WHERE (Name = 'Test' AND Testing__c = true) OR Email__c = 'test@test.com'`

## Requirements

-   [PHP 7.3+](https://www.php.net)
-   [Composer 2.0+](https://getcomposer.org)
-   PHPUnit is required to run the unit tests

## Running for development with Docker

We have included a Dockerfile to make it easy to run the tests and debug the code. You must have Docker installed. The following commands will build the image and run the container:

1. `docker build -t lyonstahl/soql-builder --build-arg PHP_VERSION=8 .`
2. `docker run -it --rm -v ${PWD}:/var/www/soql lyonstahl/soql-builder sh`

## Debugging with XDebug in VSCode

Docker image is configured with XDebug. To debug the code with VSCode, follow these steps:

1.  Install the [PHP Debug extension](https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug) in VSCode
2.  Add a new PHP Debug configuration in VSCode:

        {
            "name": "XDebug Docker",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/var/www/soql/": "${workspaceRoot}/"
            }
        }

3.  `docker run -it --rm -v ${PWD}:/var/www/soql --add-host host.docker.internal:host-gateway lyonstahl/soql-builder sh`
4.  Start debugging in VSCode with the 'XDebug Docker' configuration.

## Testing

This library ships with PHPUnit for development. Composer file has been configured with some scripts, run the following command to run the tests:

    composer test
