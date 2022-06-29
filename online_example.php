<?php

require_once realpath("vendor/autoload.php");

use Harness\Client;
use OpenAPI\Client\Model\Target;

$SDK_KEY = getenv("SDK_KEY") ?: "";  // you can put your key in env variable or you can provide in the code
$FLAG_NAME = "harnessappdemodarkmode";

$client = new Client($SDK_KEY, new Target(["name" => "harness", "identifier" => "harness"]));
$result = $client->evaluate($FLAG_NAME, false);

echo "Evaluation value for flag '".$FLAG_NAME."' with target 'harness': ".json_encode($result);
