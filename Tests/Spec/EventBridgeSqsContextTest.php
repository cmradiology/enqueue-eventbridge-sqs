<?php

namespace Cmrad\EbSQS\Tests\Spec;

use Cmrad\EbSQS\EventBridgeContext;
use Cmrad\EbSQS\EventBridgeRule;
use Enqueue\Sns\SnsContext;
use Enqueue\Sns\SnsSubscribe;
use Cmrad\EbSQS\EventBridgeSqsContext;
use Cmrad\EbSQS\EventBridgeSqsQueue;
use Cmrad\EbSQS\EventBridgeSqsTopic;
use Enqueue\Sqs\SqsConsumer;
use Enqueue\Sqs\SqsContext;
use Interop\Queue\Spec\ContextSpec;

class EventBridgeSqsContextTest extends ContextSpec
{
    public function testSetsSubscriptionAttributes(): void
    {
        $topic = new EventBridgeSqsTopic('topic1');

        $ebContext = $this->createMock(EventBridgeContext::class);
        $ebContext->expects($this->once())
            ->method('createRule')
            ->with($this->equalTo(new EventBridgeRule(
                $topic,
                'queueArn1',
                ['source1'],
                ['Action'],
                [],
            )));
        $ebContext->expects($this->any())
            ->method('getSource')
            ->willReturn(['source1']);

        $sqsContext = $this->createMock(SqsContext::class);
        $sqsContext->expects($this->any())
            ->method('createConsumer')
            ->willReturn($this->createMock(SqsConsumer::class));
        $sqsContext->expects($this->any())
            ->method('getQueueArn')
            ->willReturn('queueArn1');

        $context = new EventBridgeSqsContext($ebContext, $sqsContext);
        $context->bind(
            $topic,
            new EventBridgeSqsQueue('queueArn1'),
            ['Action'],
        );
    }

    protected function createContext()
    {
        $sqsContext = $this->createMock(SqsContext::class);
        $sqsContext
            ->expects($this->any())
            ->method('createConsumer')
            ->willReturn($this->createMock(SqsConsumer::class))
        ;

        return new EventBridgeSqsContext(
            $this->createMock(EventBridgeContext::class),
            $sqsContext
        );
    }
}
