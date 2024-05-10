<?php

namespace Cmrad\EbSQS\Tests\Spec;

use Cmrad\EbSQS\EventBridgeSqsQueue;
use Interop\Queue\Spec\QueueSpec;

class SnsQsQueueTest extends QueueSpec
{
    protected function createQueue()
    {
        return new EventBridgeSqsQueue(self::EXPECTED_QUEUE_NAME);
    }
}
