<?php

namespace Harness\Tests;

use PHPUnit\Framework\TestCase;

use Harness\Client;
use OpenAPI\Client\Model\Target;
use Psr\Cache\CacheItemPoolInterface;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Cache\Adapter\Filesystem\FilesystemCachePool;

use const Harness\METRICS_KEY;

const OFFLINE_SDK_KEY = "c25e3f4e-9d2d-42d6-a85c-6fb3af062732";
const EXPIRE_AFTER = 2; //seconds
const BOOL_FLAG_IDENTIFIER = "harnessappdemocfribbon";
const STRING_FLAG_IDENTIFIER = "harnessappdemocetriallimit";
const HARNESS_IDENTIFIER = "harness";

class ClientTest extends TestCase {

    private Client $client;
    private CacheItemPoolInterface $cache;

    protected function setUp(): void {
        $filesystemAdapter = new Local(sys_get_temp_dir());
        $filesystem        = new Filesystem($filesystemAdapter);

        $this->cache = new FilesystemCachePool($filesystem);
        $this->client = new Client(
            OFFLINE_SDK_KEY,
            new Target(["name" => HARNESS_IDENTIFIER, "identifier" => HARNESS_IDENTIFIER]),
            [
                "cache" => $this->cache,
                "expireAfter" => EXPIRE_AFTER,
            ]
        );
    }

    protected function tearDown(): void
    {
        // clear all items in cache after every test case
        $this->cache->clear();
    }

    public function testDefaultCtor(): void {
        // test default constructor
        $this->assertInstanceOf(Client::class, $this->client);
    }

    public function testOffEvaluation(): void {
        // in this test we will try to evaluate harnessappdemocetriallimit flag
        // because this flag is in config folder and state is off so it means it is
        // deactivated and should return offVariation with value of "20"
        $expect = "20";

        $got = $this->client->evaluate(STRING_FLAG_IDENTIFIER, "7");

        $this->assertEquals($expect, $got);
    }

    public function testOnEvaluation(): void {
        // in this test we will try to evaluate BOOL_FLAG_IDENTIFIER flag
        // because this flag is in config folder and state is on so it means it is
        // activated and should return true value
        $expect = true;

        $got = $this->client->evaluate(BOOL_FLAG_IDENTIFIER, false);

        $this->assertEquals($expect, $got);
    }

    public function testFetchEvaluations(): void {
        // this integration test will check if evaluations are successfully stored to the cache
        // and check non empty return from the function

        $got = $this->client->fetchEvaluations();
        $item = $this->cache->getItem($this->client->getCacheKeyName(STRING_FLAG_IDENTIFIER, HARNESS_IDENTIFIER))->get();

        $this->assertNotEmpty($got);
        $this->assertNotEmpty(
            $item
        );
        $this->assertEquals($item, "20");
    }

    public function testCacheExpireOnAllEvaluations(): void {
        // item from cache should be empty because we wait to expire + 1s

        $this->client->fetchEvaluations();
        sleep(EXPIRE_AFTER + 1);
        $item = $this->cache->getItem($this->client->getCacheKeyName(STRING_FLAG_IDENTIFIER, HARNESS_IDENTIFIER))->get();
        $this->assertEmpty(
            $item
        );
        $this->assertNull($item);
    }

    public function testEvaluationOnCacheExpiredFlag(): void {
        // this integration test will check if evaluation are successfully stored to the cache
        // and check non empty return from the function

        $got = $this->client->evaluate(BOOL_FLAG_IDENTIFIER, false);
        // wait to expire
        sleep(EXPIRE_AFTER + 1);
        // expired, no item in cache
        $item = $this->cache->getItem($this->client->getCacheKeyName(BOOL_FLAG_IDENTIFIER, HARNESS_IDENTIFIER))->get();
        $this->assertEmpty(
            $item
        );
        $this->assertNull($item);
    }

    public function testExpiredEvaluationShouldReturnFromService(): void {
        // evaluate BOOL_FLAG_IDENTIFIER and wait cache to expire
        // item should be empty

        $got = $this->client->evaluate(BOOL_FLAG_IDENTIFIER, false);
        // wait to expire
        sleep(EXPIRE_AFTER + 1);
        // expired, no item in cache
        $item = $this->cache->getItem($this->client->getCacheKeyName(BOOL_FLAG_IDENTIFIER, HARNESS_IDENTIFIER))->get();
        $this->assertEmpty(
            $item
        );
        $this->assertNull($item);
    }

    public function testEvaluationShouldReturnValueFromCache(): void {
        // it should take value from the cache and serve it

        // prepare the cache (mock) item value to false
        $this->cache->clear();
        $item = $this->cache->getItem($this->client->getCacheKeyName(BOOL_FLAG_IDENTIFIER, HARNESS_IDENTIFIER));
        $item->set(false);
        $this->cache->save($item);

        // when
        $got = $this->client->evaluate(BOOL_FLAG_IDENTIFIER, true);
        $this->assertNotEmpty(
            $item
        );
        // here assertion will check with the value from cache
        // because real value is true for this flag
        $this->assertEquals($got, $item->get());
    }

    public function testPushToMetricsOnEvaluation(): void {

        $got = $this->client->evaluate(BOOL_FLAG_IDENTIFIER, true);
        /* @var $metricsData []MetricItem */
        $metricsData = $this->cache->getItem(METRICS_KEY)->get();
        $metricItem = $metricsData[0];

        $this->assertNotEmpty($metricsData);
        $this->assertEquals(BOOL_FLAG_IDENTIFIER, $metricItem->featureIdentifier);
    }
}