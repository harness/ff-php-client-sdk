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


### General Dependencies


## Quickstart
The Feature Flag SDK provides a client that connects to the feature flag service, and fetches the value
of feature flags. The following section provides an example of how to install the SDK and initialize it from an application.

This quickstart assumes you have followed the instructions to [setup a Feature Flag project and have created a flag called `harnessappdemodarkmode` and created a server API Key](https://ngdocs.harness.io/article/1j7pdkqh7j-create-a-feature-flag#step_1_create_a_project).

### Install the FF SDK Dependency

The first step is to install the FF SDK as a dependency in your application using your application's dependency manager. 

### A Simple Example

After installing the SDK, enter the SDK keys that you created for your environment. The SDK keys authorize your application to connect to the FF client. 

```php
<?php echo "simple example"; ?>
```


### Additional Reading

Further examples and config options are in the further reading section:

[Further Reading](docs/further_reading.md)


-------------------------
[Harness](https://www.harness.io/) is a feature management platform that helps teams to build better software and to
test features quicker.

-------------------------
