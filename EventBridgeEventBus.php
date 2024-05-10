<?php

namespace Cmrad\EbSQS;

use Interop\Queue\Destination;
use Interop\Queue\Queue;
use Interop\Queue\Topic;

class EventBridgeEventBus implements Destination, Queue, Topic
{
    public function __construct(
        private string $eventBusName
    )
    {
    }

    public function getEventBusName(): string
    {
        return $this->eventBusName;
    }

    public function setEventBusName(string $eventBusName): void
    {
        $this->eventBusName = $eventBusName;
    }

    public function getQueueName(): string
    {
        return $this->eventBusName;
    }

    public function getTopicName(): string
    {
        return $this->eventBusName;
    }

    public function __toString(): string
    {
        return $this->eventBusName;
    }
}
