<?php

namespace Cmrad\EbSQS;

use Interop\Queue\Message;

class EventBridgeMessage implements Message
{

    /**
     * @var false
     */
    private bool $redelivered;

    public function __construct(
        private string $body = '',
        private array $properties = [],
        private array $headers = [],
        private ?array $messageAttributes = null
    ) {
        $this->redelivered = false;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function setProperty(string $name, $value): void
    {
        $this->properties[$name] = $value;
    }

    public function getProperty(string $name, $default = null)
    {
        return $this->properties[$name] ?? $default;
    }

    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeader(string $name, $value): void
    {
        $this->headers[$name] = $value;
    }

    public function getHeader(string $name, $default = null)
    {
        return $this->headers[$name] ?? $default;
    }

    public function setRedelivered(bool $redelivered): void
    {
        $this->redelivered = $redelivered;
    }

    public function isRedelivered(): bool
    {
        return $this->redelivered;
    }

    public function setCorrelationId(string $correlationId = null): void
    {
        $this->properties['correlation_id'] = $correlationId;
    }

    public function getCorrelationId(): ?string
    {
        return $this->properties['correlation_id'] ?? null;
    }

    public function setMessageId(string $messageId = null): void
    {
        $this->properties['id'] = $messageId;
    }

    public function getMessageId(): ?string
    {
        return $this->properties['id'] ?? null;
    }

    public function getTimestamp(): ?int
    {
        return $this->properties['time'] ?? null;
    }

    public function setTimestamp(int $timestamp = null): void
    {
        $this->properties['time'] = $timestamp;
    }

    public function setReplyTo(string $replyTo = null): void
    {
        $this->properties['reply_to'] = $replyTo;
    }

    public function getReplyTo(): ?string
    {
        return $this->properties['reply_to'] ?? null;
    }

    public function getMessageAttributes(): ?array
    {
        return $this->messageAttributes;
    }

    public function setMessageAttributes(?array $messageAttributes): void
    {
        $this->messageAttributes = $messageAttributes;
    }

    public function getSource(): ?string
    {
        return $this->properties['source'] ?? null;
    }

    public function getDetailType(): ?string
    {
        return $this->properties['detail-type'] ?? null;
    }

    public function setMessageGroupId(?string $getMessageGroupId)
    {
        $this->properties['message-group-id'] = $getMessageGroupId;
    }

    public function setMessageDeduplicationId(?string $getMessageDeduplicationId)
    {
        $this->properties['message-deduplication-id'] = $getMessageDeduplicationId;
    }

    public function getMessageGroupId()
    {
        return $this->properties['message-group-id'] ?? null;
    }

    public function getMessageDeduplicationId()
    {
        return $this->properties['message-deduplication-id'] ?? null;
    }
}
