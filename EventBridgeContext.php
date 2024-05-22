<?php

namespace Cmrad\EbSQS;

use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Exception\PurgeQueueNotSupportedException;
use Interop\Queue\Exception\SubscriptionConsumerNotSupportedException;
use Interop\Queue\Exception\TemporaryQueueNotSupportedException;
use Interop\Queue\Queue;
use Interop\Queue\SubscriptionConsumer;

class EventBridgeContext implements Context
{
    private mixed $eventBusArn;

    public function __construct(private EventBridgeClient $client, private array $config)
    {
    }

    public function createMessage(string $body = '', array $properties = [], array $headers = []): EventBridgeMessage
    {
        return new EventBridgeMessage($body, $properties, $headers);
    }


    public function createTopic(string $topicName): EventBridgeEventBus
    {
        return new EventBridgeEventBus($topicName);
    }

    public function createQueue(string $queueName): EventBridgeEventBus
    {
        return new EventBridgeEventBus($queueName);
    }

    public function createTemporaryQueue(): Queue
    {
        throw TemporaryQueueNotSupportedException::providerDoestNotSupportIt();
    }

    public function createProducer(): EventBridgeProducer
    {
        return new EventBridgeProducer($this);
    }

    public function createConsumer(Destination $destination): Consumer
    {
        throw new \LogicException(
            'EventBridge transport does not support consumption. You should consider using SQS instead.'
        );
    }

    public function close(): void
    {
    }

    public function purgeQueue(Queue $queue): void
    {
        PurgeQueueNotSupportedException::providerDoestNotSupportIt();
    }

    public function createSubscriptionConsumer(): SubscriptionConsumer
    {
        throw SubscriptionConsumerNotSupportedException::providerDoestNotSupportIt();
    }

    public function getClient(): EventBridgeClient
    {
        return $this->client;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getSource(): array
    {
        return [$this->config['source']] ?? [];
    }

    public function declareEventBus(EventBridgeEventBus $eventBus): void
    {
        $result = $this->client->createEventBus(
          [
            'Name' => $eventBus->getEventBusName(),
          ]
        );

        if(false === $result->hasKey('EventBusArn')) {
            throw new \RuntimeException(sprintf('Cannot create event bus: %s', $eventBus->getEventBusName()));
        }

        $this->eventBusArn[$eventBus->getEventBusName()] = $result['EventBusArn'];
    }

    public function getEventBusArn(EventBridgeEventBus $eventBus): string
    {
        if (!array_key_exists($eventBus->getEventBusName(), $this->eventBusArn)) {
            $this->declareEventBus($eventBus);
        }

        return $this->eventBusArn[$eventBus->getEventBusName()];
    }

    public function setEventBusArn(EventBridgeEventBus $eventBus, string $arn): void
    {
        $this->eventBusArn[$eventBus->getEventBusName()] = $arn;
    }

    public function deleteEventBus(EventBridgeEventBus $eventBus): void
    {
        $this->client->deleteEventBus(
          [
            'Name' => $eventBus->getEventBusName(),
          ]
        );

        unset($this->eventBusArn[$eventBus->getEventBusName()]);
    }



    public function createRule(EventBridgeRule $rule): void
    {
        foreach ($this->getRules($rule->getEventBus()) as $ruleAWS) {
            if ($ruleAWS['Name'] === $rule->getName()) {
                return;
            }
        }
        $pattern = [
            'source' => $rule->getSource(),
        ];
        if ($rule->getDetailType()) {
            $pattern['detail-type'] = $rule->getDetailType();
        }
        var_dump($rule->getName());
        $args = [
            'Name' => $rule->getName(),
            'EventPattern' => json_encode($pattern),
            'EventBusName' => $rule->getEventBus()->getEventBusName(),
        ];


        if (false === empty($rule->getAttributes())) {
            $args['Tags'] = $rule->getAttributes();
        }

        $this->client->putRule($args);
    }

    public function deleteRule(EventBridgeRule $rule): void
    {
        $this->client->deleteRule(
          [
            'Name' => $rule->getName(),
            'EventBusName' => $rule->getEventBus()->getEventBusName(),
          ]
        );
    }

    public function createTarget(EventBridgeRuleTarget $target): void
    {
        $this->client->putTargets(
          [
            'Rule' => $target->getRule()->getName(),
            'Targets' => [
              [
                'Id' => $target->getId(),
                'Arn' => $target->getArn(),
              ],
            ],
            'EventBusName' => $target->getRule()->getEventBus()->getEventBusName(),
          ]
        );
    }

    public function createTargets(EventBridgeRule $rule, array $targets): void
    {
        $args = [
            'Rule' => $rule->getName(),
            'Targets' => array_map(
                fn(EventBridgeRuleTarget $target) => [
                    'Id' => $target->getId(),
                    'Arn' => $target->getArn(),
                ],
                $targets
            ),
            'EventBusName' => $rule->getEventBus()->getEventBusName(),
        ];

        $this->client->putTargets($args);
    }

    public function getRules(EventBridgeEventBus $eventBus): array
    {
        $args = [
            'EventBusName' => $this->getEventBusArn($eventBus),
        ];
        $rules = [];
        while (true) {
            $result = $this->client->listRules($args);

            $rules = array_merge($rules, $result->get('Rules'));

            if (false === $result->hasKey('NextToken')) {
                break;
            }

            $args['NextToken'] = $result->get('NextToken');
        }

        return $rules;
    }

    public function deleteTargets(EventBridgeRule $rule): void
    {
        $listTargets = $this->client->listTargetsByRule(
            [
                'Rule' => $rule->getName(),
                'EventBusName' => $rule->getEventBus()->getEventBusName(),
            ]
        );
        $args = [
            'Rule' => $rule->getName(),
            'EventBusName' => $rule->getEventBus()->getEventBusName(),
            'Ids' => array_map(
                fn(array $target) => $target['Id'],
                $listTargets->get('Targets')
            ),
        ];

        $this->client->removeTargets($args);
    }

    public function getRuleArn(EventBridgeRule $rule): ?string
    {
        $rules = $this->getRules($rule->getEventBus());
        foreach ($rules as $ruleAWS) {
            if ($ruleAWS['Name'] === $rule->getName()) {
                return $ruleAWS['Arn'];
            }
        }

        return null;
    }
}
