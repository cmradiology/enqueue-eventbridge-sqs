<?php

namespace Cmrad\EbSQS;

use Enqueue\Sns\SnsDestination;
use Enqueue\Sns\SnsMessage;
use Interop\Queue\Destination;
use Interop\Queue\Exception\DeliveryDelayNotSupportedException;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Exception\PriorityNotSupportedException;
use Interop\Queue\Exception\TimeToLiveNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Producer;

class EventBridgeProducer implements Producer
{

    public function __construct(private EventBridgeContext $context)
    {
    }

    public function send(Destination $destination, Message $message): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, EventBridgeEventBus::class);
        InvalidMessageException::assertMessageInstanceOf($message, EventBridgeMessage::class);

        $this->context->getClient()->putEvents(
            [
                "Entries" => [
                    self::putEventBodyRequest(
                        $message,
                        $destination->getEventBusName(),
                        $message->getSource()
                    )
                ]
            ]
        );
    }

    public function setDeliveryDelay(int $deliveryDelay = null): Producer
    {
        if (null === $deliveryDelay) {
            return $this;
        }

        throw DeliveryDelayNotSupportedException::providerDoestNotSupportIt();
    }

    public function getDeliveryDelay(): ?int
    {
        return null;
    }

    public function setPriority(int $priority = null): Producer
    {
        if (null === $priority) {
            return $this;
        }

        throw PriorityNotSupportedException::providerDoestNotSupportIt();
    }

    public function getPriority(): ?int
    {
        return null;
    }

    public function setTimeToLive(int $timeToLive = null): Producer
    {
        if (null === $timeToLive) {
            return $this;
        }

        throw TimeToLiveNotSupportedException::providerDoestNotSupportIt();
    }

    public function getTimeToLive(): ?int
    {
        return null;
    }

    public static function putEventBodyRequest(
        Message $message,
        string $eventBusName,
        string $source
    ): array {
        return [
            "Detail" => $message->getBody(),
            "DetailType" => $message->getDetailType() ?? "EventBridgeEvent",
            "EventBusName" => $eventBusName,
            "Resources" => [],
            "Source" => $source
        ];
    }
}
