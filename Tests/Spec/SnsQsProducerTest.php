<?php

namespace Cmrad\EbSQS\Tests\Spec;

use Cmrad\EbSQS\EventBridgeContext;
use Enqueue\Sns\SnsContext;
use Cmrad\EbSQS\EventBridgeSqsProducer;
use Enqueue\Sqs\SqsContext;
use Interop\Queue\Spec\ProducerSpec;

class SnsQsProducerTest extends ProducerSpec
{
    protected function createProducer()
    {
        return new EventBridgeSqsProducer(
            $this->createMock(EventBridgeContext::class),
            $this->createMock(SqsContext::class)
        );
    }
}
