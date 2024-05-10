<?php

namespace Cmrad\EbSQS;

use Aws\EventBridge\EventBridgeClient as AwsEventBridgeClient;
use Aws\MultiRegionClient;
use Aws\Result;
use Aws\Sqs\SqsClient as AwsSqsClient;
use Closure;

class EventBridgeClient
{
    private ?AwsEventBridgeClient $singleClient = null;
    private ?MultiRegionClient $multiClient = null;
    private AwsEventBridgeClient|MultiRegionClient|Closure $inputClient;

    public function __construct(AwsEventBridgeClient|MultiRegionClient|Closure $inputClient)
    {
        $this->inputClient = $inputClient;
    }

    public function createEventBus(array $args): Result
    {
        return $this->callApi('createEventBus', $args);
    }

    public function deleteEventBus(array $args): Result
    {
        return $this->callApi('deleteEventBus', $args);
    }

    public function putEvents(array $args): Result
    {
        return $this->callApi('putEvents', $args);
    }

    public function putRule(array $args): Result
    {
        return $this->callApi('putRule', $args);
    }

    public function deleteRule(array $args): Result
    {
        return $this->callApi('deleteRule', $args);
    }

    public function enableRule(array $args): Result
    {
        return $this->callApi('enableRule', $args);
    }

    public function disableRule(array $args): Result
    {
        return $this->callApi('disableRule', $args);
    }

    public function listRuleNamesByTarget(array $args): Result
    {
        return $this->callApi('listRuleNamesByTarget', $args);
    }

    public function listRules(array $args): Result
    {
        return $this->callApi('listRules', $args);
    }

    public function getAWSClient(): AwsEventBridgeClient
    {
        $this->resolveClient();

        if ($this->singleClient) {
            return $this->singleClient;
        }

        if ($this->multiClient) {
            $mr = new \ReflectionMethod($this->multiClient, 'getClientFromPool');
            $mr->setAccessible(true);
            $singleClient = $mr->invoke($this->multiClient, $this->multiClient->getRegion());
            $mr->setAccessible(false);

            return $singleClient;
        }

        throw new \LogicException('The multi or single client must be set');
    }

    private function callApi(string $name, array $args): Result
    {
        $this->resolveClient();

        if ($this->singleClient) {
            if (false == empty($args['@region'])) {
                throw new \LogicException('Cannot send message to another region because transport is configured with single aws client');
            }

            unset($args['@region']);

            return call_user_func([$this->singleClient, $name], $args);
        }

        if ($this->multiClient) {
            return call_user_func([$this->multiClient, $name], $args);
        }

        throw new \LogicException('The multi or single client must be set');
    }

    private function resolveClient(): void
    {
        if ($this->singleClient || $this->multiClient) {
            return;
        }

        $client = $this->inputClient;
        if ($client instanceof MultiRegionClient) {
            $this->multiClient = $client;

            return;
        } elseif ($client instanceof AwsEventBridgeClient) {
            $this->singleClient = $client;

            return;
        } elseif (is_callable($client)) {
            $client = call_user_func($client);
            if ($client instanceof MultiRegionClient) {
                $this->multiClient = $client;

                return;
            }
            if ($client instanceof AwsEventBridgeClient) {
                $this->singleClient = $client;

                return;
            }
        }

        throw new \LogicException(sprintf(
            'The input client must be an instance of "%s" or "%s" or a callable that returns one of those. Got "%s"',
            AwsSqsClient::class,
            MultiRegionClient::class,
            is_object($client) ? get_class($client) : gettype($client)
        ));
    }

    public function putTargets(array $array)
    {
        return $this->callApi('putTargets', $array);
    }

    public function removeTargets(array $args)
    {
        return $this->callApi('removeTargets', $args);
    }

    public function listTargetsByRule(array $args)
    {
        return $this->callApi('listTargetsByRule', $args);
    }
}
