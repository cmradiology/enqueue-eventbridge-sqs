# Amazon EventBridge-SQS Transport

This is an implementation of Queue Interop specification. It allows you to send and consume message using Amazon [EventBridge](https://aws.amazon.com/es/eventbridge)-[SQS](https://aws.amazon.com/es/sqs) service.

* [Installation](#installation)
* [Create context](#create-context)
* [Declare topic, queue and bind them together](#declare-topic-queue-and-bind-them-together)
* [Send message to topic](#send-message-to-topic)
* [Send message to queue](#send-message-to-queue)
* [Consume message](#consume-message)
* [Purge queue messages](#purge-queue-messages)
* [Queue from another AWS account](#queue-from-another-aws-account)

## Installation

```bash
$ composer require cmradiology/ebsqs
```

## Create context

```php
<?php
use Cmrad\EbSQS\EventBridgeSqsConnectionFactory;

$factory = new EventBridgeSqsConnectionFactory([
    'key' => 'aKey',
    'secret' => 'aSecret',
    'region' => 'aRegion',
    'source' => 'aSource', // default source for Event Bridge

    // or you can segregate options using prefixes "eb_", "sqs_"
    'key' => 'aKey',              // common option for both EventBridge and SQS
    'eb_region' => 'aEbRegion', // EventBridge transport option
    'sqs_region' => 'aSqsRegion', // SQS transport option
]);

// same as above but given as DSN string. You may need to url encode secret if it contains special char (like +)
$factory = new EventBridgeSqsConnectionFactory('ebsqs:?key=aKey&secret=aSecret&region=aRegion');

$context = $factory->createContext();

// if you have enqueue/enqueue library installed you can use a factory to build context from DSN
$context = (new \Enqueue\ConnectionFactoryFactory())->create('ebsqs:')->createContext();
```

## Declare topic, queue and bind them together

Declare topic, queue operation creates a topic, queue on a broker side.
Bind creates connection between topic and queue. You publish message to
the topic and topic sends message to each queue connected to the topic.


```php
<?php
/** @var \Cmrad\EbSQS\EventBridgeSqsContext $context */
// Declare Event Bus
$inTopic = $context->createTopic('in');
// Create on infrastructure the event bus as EventBridge
$context->declareTopic($inTopic);
// Declare Queue
$out1Queue = $context->createQueue('out1');
// Create on infrastructure the queue as SQS
$context->declareQueue($out1Queue);

$out2Queue = $context->createQueue('out2');
$context->declareQueue($out2Queue);

$source = ['source']
// Create a Rule on EventBridge to filter messages with a pattern of source
// targeting the queue
$context->bind(
    topic: $inTopic,
    queue: $out1Queue,
    source: $source
);
$actions = ['action']
// Source is not set will use source sent on DNS as source
// Create a Rule on EventBridge to filter messages with a pattern of action
// targeting the queue using default source 
$context->bind(
    topic: $inTopic,
    queue: $out2Queue,
    actions: $actions
)

// to remove topic/queue use deleteTopic/deleteQueue method
//$context->deleteTopic($inTopic);
//$context->deleteQueue($out1Queue);
//$context->unbind(inTopic, $out1Queue);
```

## Send message to topic

```php
<?php
/** @var \Cmrad\EbSQS\EventBridgeSqsContext $context */

$inTopic = $context->createTopic('in');
// Body Should always be a valid JSON STRING, a not valid json message will not be delivered
$message = $context->createMessage(json_encode(['hello' => 'world'

$context->createProducer()->send($inTopic, $message);
```

## Send message to queue

You can bypass topic and publish message directly to the queue

```php
<?php
/** @var \Cmrad\EbSQS\EventBridgeSqsContext $context */

$fooQueue = $context->createQueue('foo');
$message = $context->createMessage('Hello world!');

$context->createProducer()->send($fooQueue, $message);
```


## Consume message:

```php
<?php
/** @var  \Cmrad\EbSQS\EventBridgeSqsContext $context */

$out1Queue = $context->createQueue('out1');
$consumer = $context->createConsumer($out1Queue);

$message = $consumer->receive();

// process a message

$consumer->acknowledge($message);
// $consumer->reject($message);
```

## Purge queue messages:

```php
<?php
/** @var  \Cmrad\EbSQS\EventBridgeSqsContext $context */

$fooQueue = $context->createQueue('foo');

$context->purgeQueue($fooQueue);
```

## Queue from another AWS account

SQS allows to use queues from another account. You could set it globally for all queues via option `queue_owner_aws_account_id` or
per queue using `EventBridgeSqsQueue::setQueueOwnerAWSAccountId` method.

```php
<?php
use Cmrad\EbSQS\EventBridgeSqsConnectionFactory;

// globally for all queues
$factory = new EventBridgeSqsConnectionFactory('ebsqs::?sqs_queue_owner_aws_account_id=awsAccountId');

$context = (new EventBridgeSqsConnectionFactory('ebsqs:'))->createContext();

// per queue.
$queue = $context->createQueue('foo');
$queue->setQueueOwnerAWSAccountId('awsAccountId');
```

## Multi region examples

Enqueue EventBridgeSqs provides a generic multi-region support. This enables users to specify which AWS Region to send a command to by setting region on EventBridgeSqsQueue.
If not specified the default region is used.

```php
<?php
use Cmrad\EbSQS\EventBridgeSqsConnectionFactory;

$context = (new EventBridgeSqsConnectionFactory('ebsqs:?region=eu-west-2'))->createContext();

$queue = $context->createQueue('foo');
$queue->setRegion('us-west-2');

// the request goes to US West (Oregon) Region
$context->declareQueue($queue);
```

<h2 align="center">Supporting Enqueue</h2>

Enqueue is an MIT-licensed open source project with its ongoing development made possible entirely by the support of community and our customers. If you'd like to join them, please consider:

- [Become a sponsor](https://www.patreon.com/makasim)
- [Become our client](http://forma-pro.com/)

---

## Resources

* [Site Enqueue](https://enqueue.forma-pro.com/)
* [Questions](https://gitter.im/php-enqueue/Lobby)
* [Issue Tracker](https://github.com/php-enqueue/enqueue-dev/issues)
