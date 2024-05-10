<?php

declare(strict_types=1);

namespace Cmrad\EbSQS\Tests;

use Cmrad\EbSQS\EventBridgeSqsConsumer;
use Cmrad\EbSQS\EventBridgeSqsContext;
use Cmrad\EbSQS\EventBridgeSqsMessage;
use Cmrad\EbSQS\EventBridgeSqsQueue;
use Enqueue\Sqs\SqsConsumer;
use Enqueue\Sqs\SqsMessage;
use PHPUnit\Framework\TestCase;

final class EventBridgeSqsConsumerTest extends TestCase
{
    public function testReceivesEventBridgeMessage(): void
    {
        $context = $this->createMock(EventBridgeSqsContext::class);
        $context->expects($this->once())
            ->method('createMessage')
            ->willReturn(new EventBridgeSqsMessage());

        $sqsConsumer = $this->createMock(SqsConsumer::class);
        $sqsConsumer->expects($this->once())
            ->method('receive')
            ->willReturn(
                new SqsMessage(
                    json_encode([
                        'source' => 'source',
                        'detail-type' => 'detail-type',
                        'detail' => 'The Body',
                    ])
                )
            );

        $consumer = new EventBridgeSqsConsumer($context, $sqsConsumer, new EventBridgeSqsQueue('queue'));
        $result = $consumer->receive();

        $this->assertInstanceOf(EventBridgeSqsMessage::class, $result);
        $this->assertSame('The Body', $result->getBody());
        $this->assertSame([], $result->getHeaders());
        $this->assertSame(['source' => 'source', 'detail-type' => 'detail-type'], $result->getProperties());
    }

    public function testReceivesSqsMessage(): void
    {
        $context = $this->createMock(EventBridgeSqsContext::class);
        $context->expects($this->once())
            ->method('createMessage')
            ->willReturn(new EventBridgeSqsMessage());

        $sqsConsumer = $this->createMock(SqsConsumer::class);
        $sqsConsumer->expects($this->once())
            ->method('receive')
            ->willReturn(
                new SqsMessage(
                    'The Body',
                    ['propKey' => 'propVal'],
                    ['headerKey' => 'headerVal'],
                )
            );

        $consumer = new EventBridgeSqsConsumer($context, $sqsConsumer, new EventBridgeSqsQueue('queue'));
        $result = $consumer->receive();

        $this->assertInstanceOf(EventBridgeSqsMessage::class, $result);
        $this->assertSame('The Body', $result->getBody());
        $this->assertSame(['headerKey' => 'headerVal'], $result->getHeaders());
        $this->assertSame(['propKey' => 'propVal'], $result->getProperties());
    }
}
