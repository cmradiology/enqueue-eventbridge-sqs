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
    'eb' => getenv('EB_DSN'),
    'sqs' => getenv('SQS_DSN'),
]))->createContext();

$topic = $context->createTopic('topic');
$queue = $context->createQueue('queue');

$context->declareTopic($topic);
$context->declareQueue($queue);
$context->bind($topic, $queue, ['action'], ['source']);

$message = $context->createMessage('Hello Bar!', ['key' => 'value'], ['key2' => 'value2']);

while (true) {
    $context->createProducer()->send($topic, $message);
    echo 'Sent message: '.$message->getBody().PHP_EOL;
    sleep(1);
}

echo 'Done'."\n";
