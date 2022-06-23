<?php

namespace Harness;

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