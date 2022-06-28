<?php

require_once realpath("vendor/autoload.php");

use Harness\Client;
use OpenAPI\Client\Model\Target;

$SDK_KEY = getenv("SDK_KEY") ?: "";  // you can put your key in env variable or you can provide in the code
$flag_name = "harnessappdemodarkmode";

$client = new Client($SDK_KEY, new Target(["name" => "harness", "identifier" => "harness"]));
$result = $client->evaluate($flag_name, false);

echo "Evaluation value for flag '{$flag_name}' with target 'harness': " .json_encode($result);
