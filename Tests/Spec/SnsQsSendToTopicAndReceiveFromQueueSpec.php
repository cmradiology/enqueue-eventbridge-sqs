<?php

namespace Cmrad\EbSQS\Tests\Spec;

use Enqueue\Test\RetryTrait;
use Interop\Queue\Context;
use Interop\Queue\Spec\SendToTopicAndReceiveFromQueueSpec;

/**
 * @group functional
 * @retry 5
 */
class SnsQsSendToTopicAndReceiveFromQueueSpec extends SendToTopicAndReceiveFromQueueSpec
{
    use RetryTrait;
    use EventBridgeSqsFactoryTrait;

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->cleanUpSnsQs();
    }

    protected function createContext()
    {
        return $this->createSnsQsContext();
    }

    protected function createTopic(Context $context, $topicName)
    {
        return $this->createSnsQsTopic($topicName);
    }

    protected function createQueue(Context $context, $queueName)
    {
        return $this->createSnsQsQueue($queueName);
    }
}
