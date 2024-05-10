<?php

declare(strict_types=1);

namespace Cmrad\EbSQS;

use Enqueue\Sns\SnsContext;
use Enqueue\Sns\SnsProducer;
use Enqueue\Sqs\SqsContext;
use Enqueue\Sqs\SqsProducer;
use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Producer;

class EventBridgeSqsProducer implements Producer
{
    private ?EventBridgeContext $eventBridgeContext;
    private ?EventBridgeProducer $eventBridgeProducer = null;
    private ?SqsContext $sqsContext;
    private ?SqsProducer $sqsProducer = null;

    public function __construct(EventBridgeContext $eventBridgeContext, SqsContext $sqsContext)
    {
        $this->eventBridgeContext = $eventBridgeContext;
        $this->sqsContext = $sqsContext;
    }

    public function send(Destination|EventBridgeSqsTopic $destination, Message|EventBridgeSqsMessage $message): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, EventBridgeSqsMessage::class);

        if (!$destination instanceof EventBridgeSqsTopic && !$destination instanceof EventBridgeSqsQueue) {
            throw new InvalidDestinationException(
                sprintf(
                    'The destination must be an instance of [%s|%s] but got %s.',
                    EventBridgeSqsTopic::class,
                    EventBridgeSqsQueue::class,
                    get_class($destination)
                )
            );
        }

        if ($destination instanceof EventBridgeSqsTopic) {
            $snsMessage = $this->eventBridgeContext->createMessage(
                $message->getBody(),
                $message->getProperties(),
                $message->getHeaders()
            );
            $snsMessage->setMessageAttributes($message->getMessageAttributes());
            $snsMessage->setMessageGroupId($message->getMessageGroupId());
            $snsMessage->setMessageDeduplicationId($message->getMessageDeduplicationId());

            $this->getEventBridgeProducer()->send($destination, $snsMessage);
        } else {
            $sqsMessage = $this->sqsContext->createMessage(
                $message->getBody(),
                $message->getProperties(),
                $message->getHeaders()
            );

            $sqsMessage->setMessageGroupId($message->getMessageGroupId());
            $sqsMessage->setMessageDeduplicationId($message->getMessageDeduplicationId());

            $this->getSqsProducer()->send($destination, $sqsMessage);
        }
    }

    /**
     * Delivery delay is supported by SQSProducer.
     */
    public function setDeliveryDelay(int $deliveryDelay = null): Producer
    {
        $this->getSqsProducer()->setDeliveryDelay($deliveryDelay);

        return $this;
    }

    /**
     * Delivery delay is supported by SQSProducer.
     */
    public function getDeliveryDelay(): ?int
    {
        return $this->getSqsProducer()->getDeliveryDelay();
    }

    public function setPriority(int $priority = null): Producer
    {
        $this->getEventBridgeProducer()->setPriority($priority);
        $this->getSqsProducer()->setPriority($priority);

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->getEventBridgeProducer()->getPriority();
    }

    public function setTimeToLive(int $timeToLive = null): Producer
    {
        $this->getEventBridgeProducer()->setTimeToLive($timeToLive);
        $this->getSqsProducer()->setTimeToLive($timeToLive);

        return $this;
    }

    public function getTimeToLive(): ?int
    {
        return $this->getEventBridgeProducer()->getTimeToLive();
    }

    private function getEventBridgeProducer(): EventBridgeProducer
    {
        if (null === $this->eventBridgeProducer) {
            $this->eventBridgeProducer = $this->eventBridgeContext->createProducer();
        }

        return $this->eventBridgeProducer;
    }

    private function getSqsProducer(): SqsProducer
    {
        if (null === $this->sqsProducer) {
            $this->sqsProducer = $this->sqsContext->createProducer();
        }

        return $this->sqsProducer;
    }
}
