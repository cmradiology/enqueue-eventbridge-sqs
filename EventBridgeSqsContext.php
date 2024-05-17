<?php

declare(strict_types=1);

namespace Cmrad\EbSQS;

use Closure;
use Enqueue\Sqs\SqsContext;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\SubscriptionConsumerNotSupportedException;
use Interop\Queue\Exception\TemporaryQueueNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Interop\Queue\Queue;
use Interop\Queue\SubscriptionConsumer;
use Interop\Queue\Topic;
use Ramsey\Uuid\Uuid;

class EventBridgeSqsContext implements Context
{
    private ?EventBridgeContext $ebContext;

    private ?Closure $ebContextFactory;

    private ?SqsContext $sqsContext = null;

    private ?Closure $sqsContextFactory = null;

    public function __construct(EventBridgeContext|Closure $ebContext, SqsContext|Closure $sqsContext)
    {
        if ($ebContext instanceof EventBridgeContext) {
            $this->ebContext = $ebContext;
        } elseif (is_callable($ebContext)) {
            $this->ebContextFactory = $ebContext;
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'The $ebContext argument must be either %s or callable that returns %s once called.',
                    EventBridgeContext::class,
                    EventBridgeContext::class
                )
            );
        }

        if ($sqsContext instanceof SqsContext) {
            $this->sqsContext = $sqsContext;
        } elseif (is_callable($sqsContext)) {
            $this->sqsContextFactory = $sqsContext;
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'The $sqsContext argument must be either %s or callable that returns %s once called.',
                    SqsContext::class,
                    SqsContext::class
                )
            );
        }
    }

    public function createMessage(string $body = '', array $properties = [], array $headers = []): Message|EventBridgeSqsMessage
    {
        return new EventBridgeSqsMessage($body, $properties, $headers);
    }

    public function createTopic(string $topicName): Topic|EventBridgeSqsTopic
    {
        return new EventBridgeSqsTopic($topicName);
    }

    /**
     * @return EventBridgeSqsQueue
     */
    public function createQueue(string $queueName): Queue
    {
        return new EventBridgeSqsQueue($queueName);
    }

    public function createTemporaryQueue(): Queue
    {
        throw TemporaryQueueNotSupportedException::providerDoestNotSupportIt();
    }

    public function createProducer(): Producer
    {
        return new EventBridgeSqsProducer($this->getEventBridgeContext(), $this->getSqsContext());
    }

    /**
     * @throws InvalidDestinationException
     */
    public function createConsumer(Destination|EventBridgeSqsQueue $destination): Consumer
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, EventBridgeSqsQueue::class);

        return new EventBridgeSqsConsumer($this, $this->getSqsContext()->createConsumer($destination), $destination);
    }

    /**
     * @throws InvalidDestinationException
     */
    public function purgeQueue(Queue|EventBridgeSqsQueue $queue): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($queue, EventBridgeSqsQueue::class);

        $this->getSqsContext()->purgeQueue($queue);
    }

    public function createSubscriptionConsumer(): SubscriptionConsumer
    {
        throw SubscriptionConsumerNotSupportedException::providerDoestNotSupportIt();
    }

    public function declareTopic(EventBridgeSqsTopic $topic): void
    {
        $this->getEventBridgeContext()->declareEventBus($topic);
    }

    public function setTopicArn(EventBridgeSqsTopic $topic, string $arn): void
    {
        $this->getEventBridgeContext()->setEventBusArn($topic, $arn);
    }

    public function deleteTopic(EventBridgeSqsTopic $topic): void
    {
        $this->getEventBridgeContext()->deleteEventBus($topic);
    }

    public function declareQueue(EventBridgeSqsQueue $queue): void
    {
        $this->getSqsContext()->declareQueue($queue);
    }

    public function deleteQueue(EventBridgeSqsQueue $queue): void
    {
        $this->getSqsContext()->deleteQueue($queue);
    }

    public function bind(EventBridgeSqsTopic $topic, EventBridgeSqsQueue $queue, ?array $detailType = null, ?array $source = null): void
    {
        $context = $this->getEventBridgeContext();
        $rule = new EventBridgeRule(
            $topic,
            $queue->getQueueName(),
            $source ?? $context->getSource(),
            $detailType,
        );
        $context->createRule($rule);
        $queueArn = $this->getSqsContext()->getQueueArn($queue);
        $context->createTarget(
            new EventBridgeRuleTarget(
                Uuid::uuid4()->toString(),
                $rule,
                $queueArn,
            )
        );
        $contextSQS = $this->getSqsContext();
        $arnRule = $context->getRuleArn($rule);
        $contextSQS->getAwsSqsClient()->setQueueAttributes(
            [
                'QueueUrl' => $contextSQS->getQueueUrl($queue),
                'Attributes' => [
                    'Policy' => json_encode([
                        'Version' => '2012-10-17',
                        'Statement' => [
                            [
                                'Effect' => 'Allow',
                                'Principal' => ["Service" => "events.amazonaws.com"],
                                'Action' => 'sqs:SendMessage',
                                'Resource' => $queueArn,
                                'Condition' => [
                                    'ArnEquals' => [
                                        'aws:SourceArn' => $arnRule,
                                    ],
                                ]
                            ],
                        ],
                    ]),
                ],
            ]
        );
    }

    public function unbind(EventBridgeSqsTopic $topic, EventBridgeSqsQueue $queue): void
    {
        $context = $this->getEventBridgeContext();
        $rule = new EventBridgeRule(
            $topic,
            $queue->getQueueName(),
            $context->getSource(),
            [],
        );
        $context->deleteTargets($rule);
        $context->deleteRule($rule);
    }

    public function close(): void
    {
        $this->getEventBridgeContext()->close();
        $this->getSqsContext()->close();
    }

    private function getEventBridgeContext(): EventBridgeContext
    {
        return $this->ebContext ?? $this->ebContext = $this->instanceEventBridgeContext();
    }

    private function getSqsContext(): SqsContext
    {
        if (null === $this->sqsContext) {
            $context = call_user_func($this->sqsContextFactory);
            if (!$context instanceof SqsContext) {
                throw new \LogicException(
                    sprintf(
                        'The factory must return instance of %s. It returned %s',
                        SqsContext::class,
                        is_object($context) ? get_class($context) : gettype($context)
                    )
                );
            }

            $this->sqsContext = $context;
        }

        return $this->sqsContext;
    }

    private function instanceEventBridgeContext(): EventBridgeContext
    {
        $context = ($this->ebContextFactory)();

        if (!$context instanceof EventBridgeContext) {
            throw new \LogicException(
                sprintf(
                    'The factory must return instance of %s. It returned %s',
                    EventBridgeContext::class,
                    is_object($context) ? get_class($context) : gettype($context)
                )
            );
        }

        return $context;
    }
}
