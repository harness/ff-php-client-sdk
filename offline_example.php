<?php

require_once realpath("vendor/autoload.php");

use Harness\Client;
use OpenAPI\Client\Model\Target;

$client = new Client("c25e3f4e-9d2d-42d6-a85c-6fb3af062732", new Target(array("name" => "James", "identifier" => "james")));

echo "Evaluation value " . $client->evaluate("harnessappdemocetriallimit", "7");
