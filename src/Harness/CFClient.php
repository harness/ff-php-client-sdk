<?php

namespace Harness;

use Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use OpenAPI\Client\Api\ClientApi;
use OpenAPI\Client\Configuration;
use OpenAPI\Client\Model\AuthenticationRequest;
use OpenAPI\Client\ApiException;
use OpenAPI\Client\Model\Target;
use GuzzleHttp\Client;
use Psr\Log\LogLevel;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Cache\Adapter\Filesystem\FilesystemCachePool;

class CFClient
{
    /** @var string */
    const DEFAULT_BASE_URL = 'http://ff-proxy:7000';
    /** @var string */
    const DEFAULT_EVENTS_URL = 'http://ff-proxy:7000';
    /** @var string */
    const VERSION = '1.0.0';

    protected string $_sdkKey;
    protected string $_baseUrl;
    protected string $_eventsUrl;
    protected ClientApi $_apiInstance;
    protected string $_environment;
    protected string $_cluster;

    protected Configuration $_configuration;

    protected $_logger;
    protected $_cache;

    public function __construct(string $sdkKey, array $options = [])
    {
        $this->_sdkKey = $sdkKey;
        if (!isset($options['base_url'])) {
            $this->_baseUrl = $_ENV["PROXY_BASE_URL"] ?: self::DEFAULT_BASE_URL;
        } else {
            $this->_baseUrl = rtrim($options['base_url'], '/');
        }

        if (!isset($options['events_url'])) {
            $this->_eventsUrl = $_ENV["PROXY_EVENTS_URL"] ?: self::DEFAULT_EVENTS_URL;
        } else {
            $this->_eventsUrl = rtrim($options['events_url'], '/');
        }

        if (!isset($options['logger'])) {
            $this->_logger = new Logger('CfClient');
            $this->_logger->pushHandler(new StreamHandler('php://stderr', LogLevel::DEBUG));
        } else {
            $this->_logger = $options['logger'];
        }

        if (!isset($options['cache'])) {
            $filesystemAdapter = new Local(sys_get_temp_dir());
            $filesystem        = new Filesystem($filesystemAdapter);

            $this->_cache = new FilesystemCachePool($filesystem);
        } else {
            $this->_cache = $options['cache'];
        }


        $this->_configuration = new Configuration();
        $this->_configuration->setHost($this->_baseUrl);
        $this->_apiInstance = new ClientApi(new Client(), $this->_configuration);

        $item = $this->_cache->getItem("cf_data");
        $cfData = $item->get();
        if (isset($cfData)) {
            $this->_logger->info("CF data loaded from the cache");
            $this->_environment = $cfData["environment"];
            $this->_cluster = $cfData["clusterIdentifier"];
            $this->_configuration->setAccessToken($cfData["JWT"]);
        } else {
            $this->_logger->info("CF data not found in cache, authenticating...");
            $this->authenticate($item);
        }

        if (isset($this->_environment)) {
            $this->fetchEvaluations();
        }
    }

    protected function authenticate($item)
    {
        try {
            $request = new AuthenticationRequest(array("api_key" => $this->_sdkKey));
            $response = $this->_apiInstance->authenticate($request);
            $jwtToken = $response->getAuthToken();
            $parts = explode('.', $jwtToken);
            if (count($parts) !== 3) {
                $this->_logger->error("JWT token not valid!");
                return;
            }
            $decoded = base64_decode($parts[1]);
            $payload = json_decode($decoded, true);
            $this->_environment = $payload['environment'];
            $this->_cluster = "1";
            if (array_key_exists('clusterIdentifier', $payload)) {
                $this->_cluster = $payload->clusterIdentifier;
            }
            $this->_configuration->setAccessToken($jwtToken);
            $this->_logger->info("successfully authenticated");
            $item->set(array("JWT" => $jwtToken, "environment" => $this->_environment, "clusterIdentifier" => $this->_cluster));
            $this->_cache->save($item);
        } catch (ApiException $e) {
            $this->_logger->error("Error while authenticating {$e->getMessage()}");
        } catch (Exception $e) {
             $this->_logger->error("Caught $e");
        }
    }

    public function fetchEvaluations()
    {
        // TBD
        $response = $this->_apiInstance->getFeatureConfig($this->_environment);
        foreach ($response as $key => $value) {
            echo "Key: $key; Value: $value\n";
        }
    }

    public function evaluate(string $identifier, Target $target, $defaultValue) {
        $item = $this->_cache->getItem("evaluations__$identifier");
        if ($value = $item->get()) {
            $this->_logger->debug("Loading {$identifier} from cache with value {$value}");
            return $value;
        }
        try {
            $response = $this->_apiInstance->getEvaluationByIdentifier($this->_environment, $identifier, $target->getIdentifier());
            $item->set($response["value"]);
            $item->expiresAfter(60);
            $this->_cache->save($item);
            $this->_logger->debug("Put {$identifier} in the cache");
            return $response["value"];
        } catch (ApiException $e) {
            $this->_logger->error("Caught $e");
            return $defaultValue;
        }
    }
}
