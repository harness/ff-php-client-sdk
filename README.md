Harness Feature Flag PHP SDK
========================

## Table of Contents
**[Intro](#Intro)**<br>
**[Requirements](#Requirements)**<br>
**[Quickstart](#Quickstart)**<br>
**[Further Reading](docs/further_reading.md)**<br>
**[Development](docs/development.md)**<br>


## Intro

Harness Feature Flags (FF) is a feature management solution that enables users to change the software’s functionality, without deploying new code. FF uses feature flags to hide code or behaviours without having to ship new versions of the software. A feature flag is like a powerful if statement.
* For more information, see https://harness.io/products/feature-flags/
* To read more, see https://ngdocs.harness.io/category/vjolt35atg-feature-flags
* To sign up, https://app.harness.io/auth/#/signup/

![FeatureFlags](https://github.com/harness/ff-php-server-sdk/raw/main/docs/images/ff-gui.png)

## Requirements
To use this SDK, make sure you’ve:
- installed [PHP](https://www.php.net/) 7.4 or a newer version
- installed [Composer](https://getcomposer.org/)
### General Dependencies
- [Relay Proxy](https://github.com/harness/ff-proxy)
- [Redis](https://redis.io/)
## Quickstart
The Feature Flag SDK provides a client that connects to the feature flag service, and fetches the value
of feature flags. The following section provides an example of how to install the SDK and initialize it from an application.

This quickstart assumes you have followed the instructions to [setup a Feature Flag project and have created a flag called `harnessappdemodarkmode` and created a server API Key](https://ngdocs.harness.io/article/1j7pdkqh7j-create-a-feature-flag#step_1_create_a_project).

### Install the SDK Dependency

The first step is to install the SDK as a dependency in your application using Composer.

```shell
composer require harness/ff-server-sdk
```
### A Simple Example

After installing the SDK, enter the SDK keys that you created for your environment. The SDK keys authorize your application to connect to the FF client. 

```php
<?php

require_once realpath("vendor/autoload.php");

use Harness\Client;
use OpenAPI\Client\Model\Target;

$FLAG_KEY = "harnessappdemodarkmode";
$SDK_KEY = getenv("SDK_KEY") ?: "";  // you can put your key in env variable or you can provide in the code

$client = new Client($SDK_KEY, new Target(["name" => "Harness", "identifier" => "harness"]), [
    "base_url": "http://proxy-url",
    "events_url": "http://proxy-url",
]);

echo "Evaluation value for flag $FLAG_KEY with target 'harness': " . $client->evaluate($FLAG_KEY, false);
```

## Running the example

To run the example, open the browser and type:

```
http://localhost/online_example.php
```

### Additional Reading

Further examples and config options are in the further reading section:

[Further Reading](docs/further_reading.md)


-------------------------
[Harness](https://www.harness.io/) is a feature management platform that helps teams to build better software and to
test features quicker.

-------------------------
