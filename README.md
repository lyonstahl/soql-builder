# Salesforce SOQL Builder

SOQL builder that simplifies the process of constructing complex queries to retrieve data from Salesforce databases.

## Installation

    composer require lyonstahl/soql-builder

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

## Example usage

```php
$builder
    ->select(['Id', 'Name', 'created_at'])
    ->from('Account')
    ->where('Name', '=', 'Test')
    ->limit(20)
    ->orderBy('created_at', 'DESC')
    ->toSoql();
```

`> SELECT Id, Name, created_at FROM Account WHERE Name = 'Test' ORDER BY created_at DESC LIMIT 20`

```php
$builder
    ->select(['Id', 'Name'])
    ->from('Account')
    ->where('Name', '=', 'Test')
    ->orWhere('Name', '=', 'Testing')
    ->toSoql();
```

`> SELECT Id, Name FROM Account WHERE Name = 'Test' OR Name = 'Testing'`

```php
$builder
    ->select(['Id', 'Name'])
    ->from('Account')
    ->startWhere()
    ->where('Name', '=', 'Test')
    ->where('Testing__c', '=', true)
    ->endWhere()
    ->orWhere('Email__c', '=', 'test@test.com')
    ->toSoql();
```

`> SELECT Id, Name FROM Account WHERE (Name = 'Test' AND Testing__c = true) OR Email__c = 'test@test.com'`

## Running for development with Docker

1. `docker build -t lyonstahl/soql-builder --build-arg PHP_VERSION=8 .`
2. `docker run -it --rm -v ${PWD}:/var/www/soql lyonstahl/soql-builder sh`

## Debugging with XDebug and VSCode

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

This library ships with PHPUnit for development.

    composer test
