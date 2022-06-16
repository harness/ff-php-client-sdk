<?php

require_once realpath("vendor/autoload.php");

use Harness\CFClient;
use OpenAPI\Client\Model\Target;

$SDK_KEY = getenv("SDK_KEY") ?: "";  // you can put your key in env variable or you can provide in the code

$cfClient = new CFClient($SDK_KEY, new Target(["name" => "harness", "identifier" => "harness"]));

echo "Evaluation value for flag 'flag1' with target 'enver': " . $cfClient->evaluate("flag1", false);

$cfClient->close();
