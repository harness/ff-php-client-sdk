<?php

require_once realpath("vendor/autoload.php");

use Harness\CFClient;
use OpenAPI\Client\Model\Target;

$cfClient = new CFClient(getenv("SDK_KEY"), new Target(["name" => "enver", "identifier" => "enver"]));

echo "Evaluation value for flag 'flag1' with target 'enver': " . $cfClient->evaluate("flag1", false);

$cfClient->sendMetrics();
