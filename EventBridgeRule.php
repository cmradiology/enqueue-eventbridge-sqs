<?php

namespace Cmrad\EbSQS;

class EventBridgeRule
{
    const PROTOCOL_SQS = 'sqs';

    public function __construct(
        private EventBridgeEventBus $eventBus,
        private string $name,
        private array $source,
        private array $detailType,
        private array $attributes = []
    )
    {
    }

    public function getEventBus(): EventBridgeEventBus
    {
        return $this->eventBus;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSource(): array
    {
        return $this->source;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getDetailType(): array
    {
        return $this->detailType;
    }
}
