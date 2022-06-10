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
use GuzzleHttp\HandlerStack;
use GuzzleLogMiddleware\LogMiddleware;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Cache\Adapter\Filesystem\FilesystemCachePool;
use OpenAPI\Client\Api\MetricsApi;
use OpenAPI\Client\Model\Evaluation;
use OpenAPI\Client\Model\KeyValue;
use OpenAPI\Client\Model\Metrics;
use OpenAPI\Client\Model\MetricsData;

const VERSION = "0.0.1";
const METRICS_KEY = "metrics";

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
    protected MetricsApi $_metricsApi;
    protected string $_environment;
    protected string $_cluster;

    protected Configuration $_configuration;

    protected $_logger;
    protected $_cache;

    protected Target $_target;

    public function __construct(string $sdkKey, Target $target, array $options = [])
    {
        $this->_sdkKey = $sdkKey;
        $this->_target = $target;
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
        $stack = HandlerStack::create();
        $logMiddleware = new LogMiddleware($this->_logger);
        $stack->push($logMiddleware);
        $client = new Client([
            'handler' => $stack,
        ]);
        $this->_apiInstance = new ClientApi($client, $this->_configuration);
        $this->_metricsApi = new MetricsApi($client, $this->_configuration);

        $item = $this->_cache->getItem("cf_data__{$target->getIdentifier()}");
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
    }

    protected function authenticate($item)
    {
        try {
            $request = new AuthenticationRequest(["api_key" => $this->_sdkKey]);
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
            if (!isset($this->_environment)) {
                $this->_logger->error("environment UUID not found in JWT claims");
                return;
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
        try {
            $result = $this->_apiInstance->getEvaluations($this->_environment, $this->_target->getIdentifier(), $this->_cluster);
            foreach ($result as $evaluation) {
                $item = $this->_cache->getItem("evaluations__{$evaluation->getIdentifier()}__{$this->_target->getIdentifier()}");
                $item->set($this->convertValue($evaluation));
                $item->expiresAfter(60);
                $this->_cache->save($item);
                $this->pushToMetricsCache($evaluation);
            }
        } catch (ApiException $e) {
            $this->_logger->error('Exception when calling ClientApi->getEvaluations: {$e->getMessage()}');
        } catch (Exception $e) {
            $this->_logger->error("Caught $e");
        }
    }

    public function evaluate(string $identifier, $defaultValue) {
        $item = $this->_cache->getItem("evaluations__{$identifier}__{$this->_target->getIdentifier()}");
        if ($value = $item->get()) {
            $this->_logger->debug("Loading {$identifier} from cache with value {$value}");
            return $value;
        }
        try {
            $response = $this->_apiInstance->getEvaluationByIdentifier($this->_environment, $identifier, $this->_target->getIdentifier(), $this->_cluster);
            $value = $this->convertValue($response);
            $item->set($value);
            $item->expiresAfter(60);
            $this->_cache->save($item);
            $this->_logger->debug("Put {$identifier} in the cache");
            $this->pushToMetricsCache($response);
            return $value;
        } catch (ApiException $e) {
            $this->_logger->error("Caught $e");
            return $defaultValue;
        }
    }

    public function sendMetrics() {
        try {
            $item = $this->_cache->getItem(METRICS_KEY);
            $entries = $item->get();
            if (!isset($entries)) {
                $this->_logger->info("No metrics data");
                return;
            }
            $metricsData = [];
            /* @var $entry MetricItem */
            foreach ($entries as $entry) {
                $data = new MetricsData();
                $data->setTimestamp(time());
                $data->setCount($entry->count);
                $data->setMetricsType("FFMETRICS");
                $data->setAttributes([
                    new KeyValue(["key" => "featureIdentifier", "value" => $entry->featureIdentifier]),
                    new KeyValue(["key" => "featureName", "value" => $entry->featureIdentifier]),
                    new KeyValue(["key" => "variationIdentifier", "value" => $entry->variationIdentifier]),
                    new KeyValue(["key" => "target", "value" => $entry->targetIdentifier]),
                    new KeyValue(["key" => "SDK_NAME", "value" => "PHP"]),
                    new KeyValue(["key" => "SDK_LANGUAGE", "value" => "PHP"]),
                    new KeyValue(["key" => "SDK_TYPE", "value" => "Server"]),
                    new KeyValue(["key" => "SDK_VERSION", "value" => VERSION]),
                ]);
                $metricsData[] = $data;
            }
            $metrics = new Metrics();
            $metrics->setMetricsData($metricsData);
            print_r($metrics);
            $this->_metricsApi->postMetrics($this->_environment, $this->_cluster, $metrics);
            $this->_cache->deleteItem(METRICS_KEY);
        } catch (ApiException $e) {
            $this->_logger->error('Exception when calling MetricsApi->postMetrics: {$e->getMessage()}');
        } catch (Exception $e) {
            $this->_logger->error("Caught $e in MetricsApi->postMetrics");
        }
    }

    protected function pushToMetricsCache(Evaluation $evaluation) {
        $item = $this->_cache->getItem(METRICS_KEY);
        $queue = $item->get();
        if (!isset($queue)) {
           $queue = [];
        }
        array_push($queue, new MetricItem(
            $evaluation->getFlag(), $evaluation->getValue(), $evaluation->getIdentifier(), 1, time(),
            $this->_target->getIdentifier())
        );
        $item->set($queue);
        $this->_cache->save($item);
    }

    protected function convertValue(Evaluation $evaluation) {
        $value = $evaluation->getValue();
        try {
            switch ($evaluation->getKind()) {
                case "int":
                    $value = (int)$value;
                case "number":
                    $value = (float)$value;
                    break;
                case "boolean":
                    $value === "true";
                    break;
                case "json":
                    $value = json_decode($value, true);
                    break;
            }
        } catch (Exception $e) {
            $this->_logger->error("Caught $e");
        }

        return $value;
    }
}

class MetricItem {
    public string $featureIdentifier;
    public string $featureValue;
    public string $variationIdentifier;
    public int $count;
    public int $lastAccessed;
    public string $targetIdentifier;

    public function __construct(string $featureIdentifier, string $featureValue, string $variationIdentifier, int $count, int $lastAccessed,
                                string $targetIdentifier)
    {
        $this->featureIdentifier = $featureIdentifier;
        $this->featureValue = $featureValue;
        $this->variationIdentifier = $variationIdentifier;
        $this->count = $count;
        $this->lastAccessed = $lastAccessed;
        $this->targetIdentifier = $targetIdentifier;
    }
}