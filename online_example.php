<?php

require_once realpath("vendor/autoload.php");

use Harness\CFClient;
use OpenAPI\Client\Model\Target;

$cfClient = new CFClient(getenv("SDK_KEY"));

echo "Evaluation value " . $cfClient->evaluate("flag1", new Target(array("name" => "enver", "identifier" => "enver")), false);
