<?php

namespace Cmrad\EbSQS\Tests\Spec;

use Cmrad\EbSQS\EventBridgeSqsTopic;
use Interop\Queue\Spec\TopicSpec;

class SnsQsTopicTest extends TopicSpec
{
    protected function createTopic()
    {
        return new EventBridgeSqsTopic(self::EXPECTED_TOPIC_NAME);
    }
}
