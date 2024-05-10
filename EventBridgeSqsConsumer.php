<?php

declare(strict_types=1);

namespace Cmrad\EbSQS;

use Enqueue\Sqs\SqsConsumer;
use Enqueue\Sqs\SqsMessage;
use Interop\Queue\Consumer;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Queue;

class EventBridgeSqsConsumer implements Consumer
{
    /**
     * @var EventBridgeSqsContext
     */
    private $context;

    /**
     * @var SqsConsumer
     */
    private $consumer;

    /**
     * @var EventBridgeSqsQueue
     */
    private $queue;

    public function __construct(EventBridgeSqsContext $context, SqsConsumer $consumer, EventBridgeSqsQueue $queue)
    {
        $this->context = $context;
        $this->consumer = $consumer;
        $this->queue = $queue;
    }

    public function getVisibilityTimeout(): ?int
    {
        return $this->consumer->getVisibilityTimeout();
    }

    /**
     * The duration (in seconds) that the received messages are hidden from subsequent retrieve
     * requests after being retrieved by a ReceiveMessage request.
     */
    public function setVisibilityTimeout(int $visibilityTimeout = null): void
    {
        $this->consumer->setVisibilityTimeout($visibilityTimeout);
    }

    public function getMaxNumberOfMessages(): int
    {
        return $this->consumer->getMaxNumberOfMessages();
    }

    /**
     * The maximum number of messages to return. Amazon SQS never returns more messages than this value
     * (however, fewer messages might be returned). Valid values are 1 to 10. Default is 1.
     */
    public function setMaxNumberOfMessages(int $maxNumberOfMessages): void
    {
        $this->consumer->setMaxNumberOfMessages($maxNumberOfMessages);
    }

    public function getQueue(): Queue
    {
        return $this->queue;
    }

    public function receive(int $timeout = 0): ?Message
    {
        if ($sqsMessage = $this->consumer->receive($timeout)) {
            return $this->convertMessage($sqsMessage);
        }

        return null;
    }

    public function receiveNoWait(): ?Message
    {
        if ($sqsMessage = $this->consumer->receiveNoWait()) {
            return $this->convertMessage($sqsMessage);
        }

        return null;
    }

    /**
     * @param EventBridgeSqsMessage $message
     */
    public function acknowledge(Message $message): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, EventBridgeSqsMessage::class);

        $this->consumer->acknowledge($message->getSqsMessage());
    }

    /**
     * @param EventBridgeSqsMessage $message
     */
    public function reject(Message $message, bool $requeue = false): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, EventBridgeSqsMessage::class);

        $this->consumer->reject($message->getSqsMessage(), $requeue);
    }

    private function convertMessage(SqsMessage $sqsMessage)
    {

        $message = $this->context->createMessage();
        $message->setRedelivered($sqsMessage->isRedelivered());
        $message->setSqsMessage($sqsMessage);

        $body = $sqsMessage->getBody();

        if (!isset($body[0]) || '{' !== $body[0]) {
            $message->setBody($sqsMessage->getBody());
            $message->setHeaders($sqsMessage->getHeaders());
            $message->setProperties($sqsMessage->getProperties());

            return $message;
        }

        $data = json_decode($sqsMessage->getBody(), true);


        if (!isset($data['source']) || !isset($data['detail-type'])) {
            $message->setBody($data['detail']);
            $message->setHeaders($data);
            $message->setProperties($data);

            return $message;
        }
        $detail = $data['detail'] ?? null;
        // SNS message conversion
        $message->setBody(json_encode($detail));
        unset($data['detail']);
        $message->setHeaders([]);
        $message->setProperties($data);

        return $message;
    }
}
