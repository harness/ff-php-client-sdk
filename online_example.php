<?php

require_once realpath("vendor/autoload.php");

use Harness\Client;
use OpenAPI\Client\Model\Target;

$SDK_KEY = getenv("SDK_KEY") ?: "";  // you can put your key in env variable or you can provide in the code

$client = new Client($SDK_KEY, new Target(["name" => "harness", "identifier" => "harness"]));

echo "Evaluation value for flag 'flag1' with target 'harness': " . $client->evaluate("flag1", false);
