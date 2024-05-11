<?php

namespace Cmrad\EbSQS\Tests;

use Cmrad\EbSQS\EventBridgeContext;
use Cmrad\EbSQS\EventBridgeMessage;
use Cmrad\EbSQS\EventBridgeProducer;
use Enqueue\Sns\SnsContext;
use Enqueue\Sns\SnsMessage;
use Enqueue\Sns\SnsProducer;
use Cmrad\EbSQS\EventBridgeSqsMessage;
use Cmrad\EbSQS\EventBridgeSqsProducer;
use Cmrad\EbSQS\EventBridgeSqsQueue;
use Cmrad\EbSQS\EventBridgeSqsTopic;
use Enqueue\Sqs\SqsContext;
use Enqueue\Sqs\SqsMessage;
use Enqueue\Sqs\SqsProducer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class EventBridgeSqsProducerTest extends TestCase
{
    use ClassExtensionTrait;
    use ProphecyTrait;

    public function testShouldImplementProducerInterface()
    {
        $this->assertClassImplements(Producer::class, EventBridgeSqsProducer::class);
    }

//    public function testCouldBeConstructedWithRequiredArguments()
//    {
//        $this->markTestSkipped('The test is not valid anymore.');
//        new EventBridgeSqsProducer($this->createEventBridgeContextMock(), $this->createSqsContextMock());
//    }

    public function testShouldThrowIfMessageIsInvalidType()
    {
        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message must be an instance of Cmrad\EbSQS\EventBridgeSqsMessage but it is Double\Message\P1.');

        $producer = new EventBridgeSqsProducer($this->createEventBridgeContextMock(), $this->createSqsContextMock());

        $message = $this->prophesize(Message::class)->reveal();
        $producer->send(new EventBridgeSqsTopic(''), $message);
    }

    public function testShouldThrowIfDestinationOfInvalidType()
    {
        $this->expectException(InvalidDestinationException::class);

        $producer = new EventBridgeSqsProducer($this->createEventBridgeContextMock(), $this->createSqsContextMock());

        $destination = $this->prophesize(Destination::class)->reveal();

        $producer->send($destination, new EventBridgeSqsMessage());
    }

    public function testShouldSetDeliveryDelayToSQSProducer()
    {
        $delay = 10;

        $sqsProducerStub = $this->prophesize(SqsProducer::class);
        $sqsProducerStub->setDeliveryDelay(Argument::is($delay))->shouldBeCalledTimes(1);

        $sqsMock = $this->createSqsContextMock();
        $sqsMock->method('createProducer')->willReturn($sqsProducerStub->reveal());

        $producer = new EventBridgeSqsProducer($this->createEventBridgeContextMock(), $sqsMock);

        $producer->setDeliveryDelay($delay);
    }

    public function testShouldGetDeliveryDelayFromSQSProducer()
    {
        $delay = 10;

        $sqsProducerStub = $this->prophesize(SqsProducer::class);
        $sqsProducerStub->getDeliveryDelay()->willReturn($delay);

        $sqsMock = $this->createSqsContextMock();
        $sqsMock->method('createProducer')->willReturn($sqsProducerStub->reveal());

        $producer = new EventBridgeSqsProducer($this->createEventBridgeContextMock(), $sqsMock);

        $this->assertEquals($delay, $producer->getDeliveryDelay());
    }

    public function testShouldSendSnsTopicMessageToSnsProducer()
    {
        $snsMock = $this->createEventBridgeContextMock();
        $snsMock->method('createMessage')->willReturn(new EventBridgeMessage());
        $destination = new EventBridgeSqsTopic('');

        $snsProducerStub = $this->prophesize(EventBridgeProducer::class);
        $snsProducerStub->send($destination, Argument::any())->shouldBeCalledOnce();

        $snsMock->method('createProducer')->willReturn($snsProducerStub->reveal());

        $producer = new EventBridgeSqsProducer($snsMock, $this->createSqsContextMock());
        $producer->send($destination, new EventBridgeSqsMessage());
    }

    public function testShouldSendSnsTopicMessageWithAttributesToSnsProducer()
    {
        $eventBridgeContextMock = $this->createEventBridgeContextMock();
        $eventBridgeContextMock->method('createMessage')->willReturn(new EventBridgeMessage());
        $destination = new EventBridgeSqsTopic('');

        $eventBridgeProducerStub = $this->prophesize(EventBridgeProducer::class);
        $eventBridgeProducerStub->send(
            $destination,
            Argument::that(function (EventBridgeMessage $eventBridgeMessage) {
                return $eventBridgeMessage->getMessageAttributes() === ['foo' => 'bar'];
            })
        )->shouldBeCalledOnce();

        $eventBridgeContextMock->method('createProducer')->willReturn($eventBridgeProducerStub->reveal());

        $producer = new EventBridgeSqsProducer($eventBridgeContextMock, $this->createSqsContextMock());
        $producer->send($destination, new EventBridgeSqsMessage('', [], [], ['foo' => 'bar']));
    }

    public function testShouldSendToSnsTopicMessageWithGroupIdAndDeduplicationId()
    {
        $snsMock = $this->createEventBridgeContextMock();
        $snsMock->method('createMessage')->willReturn(new EventBridgeMessage());
        $destination = new EventBridgeSqsTopic('');

        $snsProducerStub = $this->prophesize(EventBridgeProducer::class);
        $snsProducerStub->send(
            $destination,
            Argument::that(function (EventBridgeMessage $snsMessage) {
                return 'group-id' === $snsMessage->getMessageGroupId()
                    && 'deduplication-id' === $snsMessage->getMessageDeduplicationId();
            })
        )->shouldBeCalledOnce();

        $snsMock->method('createProducer')->willReturn($snsProducerStub->reveal());

        $snsMessage = new EventBridgeSqsMessage();
        $snsMessage->setMessageGroupId('group-id');
        $snsMessage->setMessageDeduplicationId('deduplication-id');

        $producer = new EventBridgeSqsProducer($snsMock, $this->createSqsContextMock());
        $producer->send($destination, $snsMessage);
    }

    public function testShouldSendSqsMessageToSqsProducer()
    {
        $sqsMock = $this->createSqsContextMock();
        $sqsMock->method('createMessage')->willReturn(new SqsMessage());
        $destination = new EventBridgeSqsQueue('');

        $sqsProducerStub = $this->prophesize(SqsProducer::class);
        $sqsProducerStub->send($destination, Argument::any())->shouldBeCalledOnce();

        $sqsMock->method('createProducer')->willReturn($sqsProducerStub->reveal());

        $producer = new EventBridgeSqsProducer($this->createEventBridgeContextMock(), $sqsMock);
        $producer->send($destination, new EventBridgeSqsMessage());
    }

    public function testShouldSendToSqsProducerMessageWithGroupIdAndDeduplicationId()
    {
        $sqsMock = $this->createSqsContextMock();
        $sqsMock->method('createMessage')->willReturn(new SqsMessage());
        $destination = new EventBridgeSqsQueue('');

        $sqsProducerStub = $this->prophesize(SqsProducer::class);
        $sqsProducerStub->send(
            $destination,
            Argument::that(function (SqsMessage $sqsMessage) {
                return 'group-id' === $sqsMessage->getMessageGroupId()
                    && 'deduplication-id' === $sqsMessage->getMessageDeduplicationId();
            })
        )->shouldBeCalledOnce();

        $sqsMock->method('createProducer')->willReturn($sqsProducerStub->reveal());

        $sqsMessage = new EventBridgeSqsMessage();
        $sqsMessage->setMessageGroupId('group-id');
        $sqsMessage->setMessageDeduplicationId('deduplication-id');

        $producer = new EventBridgeSqsProducer($this->createEventBridgeContextMock(), $sqsMock);
        $producer->send($destination, $sqsMessage);
    }

    private function createEventBridgeContextMock(): EventBridgeContext|MockObject
    {
        return $this->createMock(EventBridgeContext::class);
    }

    private function createSqsContextMock(): SqsContext
    {
        return $this->createMock(SqsContext::class);
    }
}
