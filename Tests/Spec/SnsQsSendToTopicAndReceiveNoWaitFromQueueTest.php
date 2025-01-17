<?php

namespace Cmrad\EbSQS\Tests\Spec;

use Enqueue\Test\RetryTrait;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Spec\SendToTopicAndReceiveNoWaitFromQueueSpec;

/**
 * @group functional
 * @retry 5
 */
class SnsQsSendToTopicAndReceiveNoWaitFromQueueTest extends SendToTopicAndReceiveNoWaitFromQueueSpec
{
    use RetryTrait;
    use EventBridgeSqsFactoryTrait;

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->cleanUpSnsQs();
    }

    protected function createContext()
    {
        return $this->createSnsQsContext();
    }

    protected function createTopic(Context $context, $topicName)
    {
        return $this->createSnsQsTopic($topicName);
    }

    protected function createQueue(Context $context, $queueName)
    {
        return $this->createSnsQsQueue($queueName);
    }

    public function test()
    {
        $this->context = $context = $this->createContext();
        $topic = $this->createTopic($context, 'send_to_bus_and_receive_from_queue_spec');
        $queue = $this->createQueue($context, 'send_to_bus_and_receive_from_queue_spec');

        $consumer = $context->createConsumer($queue);

        // guard
        $this->assertNull($consumer->receiveNoWait());

        $expectedBody = json_encode(["message" => __CLASS__.time()]);

        $context->createProducer()->send($topic, $context->createMessage(
            $expectedBody,
            [
                'source' => 'source',
                'detail-type' => 'action',
            ]
        ));
        sleep(2); // wait for message to be delivered
        $startTime = microtime(true);
        $message = $consumer->receiveNoWait();

        $this->assertLessThan(2, microtime(true) - $startTime);

        $this->assertInstanceOf(Message::class, $message);
        $consumer->acknowledge($message);

        $this->assertSame($expectedBody, $message->getBody());
    }
}
