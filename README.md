# PHP SDK for Harness Feature Flags
========================

## Table of Contents
**[Intro](#Intro)**<br>
**[Requirements](#Requirements)**<br>
**[Quickstart](#Quickstart)**<br>
**[Further Reading](docs/further_reading.md)**<br>
**[Development](docs/development.md)**<br>


## Intro

Use this README to get started with our Feature Flags (FF) SDK for PHP. This guide outlines the basics of getting started with the SDK and provides a full code sample for you to try out. 
This sample doesn’t include configuration options, for in depth steps and configuring the SDK, for example, disabling streaming or using our Relay Proxy, see the PHP SDK Reference.

![FeatureFlags](https://github.com/harness/ff-php-server-sdk/raw/main/docs/images/ff-gui.png)

## Requirements
To use this SDK, make sure you’ve:
- installed [PHP](https://www.php.net/) 7.4 or a newer version
- installed [Composer](https://getcomposer.org/)
### General Dependencies
- [Relay Proxy](https://github.com/harness/ff-proxy)
- [Redis](https://redis.io/)

## Quickstart
To follow along with our test code sample, make sure you’ve:

- [Created a Feature Flag on the Harness Platform](https://ngdocs.harness.io/article/1j7pdkqh7j-create-a-feature-flag) called `harnessappdemodarkmode`
- [Created a server SDK key and made a copy of it](https://ngdocs.harness.io/article/1j7pdkqh7j-create-a-feature-flag#step_3_create_an_sdk_key)
- 

### Install the SDK Dependency

The first step is to install the SDK as a dependency in your application using Composer.

```shell
composer require harness/ff-server-sdk
```

### Code Sample

The following is a complete code example that you can use to test the `harnessappdemodarkmode` Flag you created on the Harness Platform. When you run the code it will:
- Connect to the FF service.
- Report the value of the Flag on the webpage. Every time the `harnessappdemodarkmode` Flag is toggled on or off on the Harness Platform, the updated value will be updated when the cache refreshes, then refresh the webpage to see the new value. 
- Close the SDK.

The example below can also be found in [online_example.php](https://github.com/harness/ff-php-server-sdk/raw/main/online_example.php).
```php
<?php

require_once realpath("vendor/autoload.php");

use Harness\Client;
use OpenAPI\Client\Model\Target;

$SDK_KEY = getenv("SDK_KEY") ?: "";  // you can put your key in env variable or you can provide in the code
$FLAG_NAME = "harnessappdemodarkmode";

$client = new Client($SDK_KEY, new Target(["name" => "harness", "identifier" => "harness"]));
$result = $client->evaluate($FLAG_NAME, false);

echo "Evaluation value for flag '".$FLAG_NAME."' with target 'harness': ".json_encode($result);
```

## Running the example with docker

This project contains the resources to quickly run this code example with Docker. To do this, you will need:
- docker
- docker-compose
- make

First set up the environment configuration for both the FF Relay Proxy and the SDK.

Copy the `.online.example.env` to `.online.env`.
```shell
cp .online.example.env .online.env
```

Then edit `.online.env` and add the following values from your Harness configuration.
```
ACCOUNT_IDENTIFIER=<Add your Account Identifier>
ORG_IDENTIFIER=default
ADMIN_SERVICE=https://app.harness.io/gateway/cf
# You need to generate an ADMIN_SERVICE_TOKEN yourself and add it here
ADMIN_SERVICE_TOKEN=<Add your Admin Service Token>
CLIENT_SERVICE=https://config.ff.harness.io/api/1.0
AUTH_SECRET=<Auth Secret string to sign JWT>
SDK_BASE_URL=https://config.ff.harness.io/api/1.0
SDK_EVENTS_URL=https://event.ff.harness.io/api/1.0
REDIS_ADDRESS=redis:6379
REDIS_PASSWORD=
REDIS_DB=0
# These two keys are the client and server SDK keys for your FF Environment
API_KEYS=<Add Server SDK Key>,<Add Client SDK Key>
```

For More information on how to get the values for these fields, refer to:
[Relay Proxy Configuration](https://ngdocs.harness.io/article/rae6uk12hk-deploy-relay-proxy#configure_relay_proxy)
[Feature Flags SDK Keys](https://ngdocs.harness.io/article/rvqprvbq8f-client-side-and-server-side-sdks)

Once these are configured, start the docker containers with:
```shell
make start
```

Once the docker containers are running, open the URL in a browser window to view the Feature Flag value.

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
