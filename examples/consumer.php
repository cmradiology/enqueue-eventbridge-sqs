<?php

$autoload = null;
foreach ([__DIR__.'/../vendor/autoload.php', __DIR__.'/../../../vendor/autoload.php'] as $file) {
    if (file_exists($file)) {
        $autoload = $file;

        break;
    }
}

if ($autoload) {
    require_once $autoload;
} else {
    throw new \LogicException('Composer autoload was not found');
}

use Cmrad\EbSQS\EventBridgeSqsConnectionFactory;

$context = (new EventBridgeSqsConnectionFactory([
    'sns' => getenv('SNS_DSN'),
    'sqs' => getenv('SQS_DSN'),
]))->createContext();

$topic = $context->createTopic('topic');
$queue = $context->createQueue('queue');

$context->declareTopic($topic);
$context->declareQueue($queue);
$context->bind($topic, $queue, ['action'], ['source']);

$consumer = $context->createConsumer($queue);

while (true) {
    if ($m = $consumer->receive(20000)) {
        $consumer->acknowledge($m);
        echo 'Received message: '.$m->getBody().' '.json_encode($m->getHeaders()).' '.json_encode($m->getProperties()).PHP_EOL;
    }
}
echo 'Done'."\n";
