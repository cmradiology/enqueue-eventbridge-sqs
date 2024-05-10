<?php

namespace Cmrad\EbSQS\Tests\Spec;

use Cmrad\EbSQS\EventBridgeSqsContext;
use Cmrad\EbSQS\EventBridgeSqsQueue;
use Cmrad\EbSQS\EventBridgeSqsTopic;
use Cmrad\EbSQS\Tests\EventBridgeSqsExtension;

trait EventBridgeSqsFactoryTrait
{
    use EventBridgeSqsExtension;

    /**
     * @var EventBridgeSqsContext
     */
    private $eventBridgeSqsContext;

    /**
     * @var EventBridgeSqsTopic
     */
    private $eventBridgeSqsTopic;

    /**
     * @var EventBridgeSqsQueue
     */
    private $eventBridgeSqsQueue;

    private $times = 0;

    protected function createSnsQsContext(): EventBridgeSqsContext
    {
        return $this->eventBridgeSqsContext = $this->buildEventBridgeSqsContext();
    }

    protected function createSnsQsQueue(string $queueName): EventBridgeSqsQueue
    {
        $queueName = $queueName.time();

        $this->eventBridgeSqsQueue = $this->eventBridgeSqsContext->createQueue($queueName);
        $this->eventBridgeSqsContext->declareQueue($this->eventBridgeSqsQueue);

        if ($this->eventBridgeSqsTopic) {
            $this->eventBridgeSqsContext->bind($this->eventBridgeSqsTopic, $this->eventBridgeSqsQueue, ['action'], ['source']);
        }

        return $this->eventBridgeSqsQueue;
    }

    protected function createSnsQsTopic(string $topicName): EventBridgeSqsTopic
    {
        $topicName = $topicName.time();

        $this->eventBridgeSqsTopic = $this->eventBridgeSqsContext->createTopic($topicName);
        $this->eventBridgeSqsContext->declareTopic($this->eventBridgeSqsTopic);

        return $this->eventBridgeSqsTopic;
    }

    protected function cleanUpSnsQs(): void
    {
        if ($this->eventBridgeSqsTopic) {
            $this->eventBridgeSqsContext->unbind($this->eventBridgeSqsTopic, $this->eventBridgeSqsQueue);
            $this->eventBridgeSqsContext->deleteTopic($this->eventBridgeSqsTopic);
        }

        if ($this->eventBridgeSqsQueue) {
            $this->eventBridgeSqsContext->deleteQueue($this->eventBridgeSqsQueue);
        }
    }
}
