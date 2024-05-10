<?php

namespace Cmrad\EbSQS\Tests\Spec;

use Cmrad\EbSQS\EventBridgeSqsMessage;
use Interop\Queue\Spec\MessageSpec;

class SnsQsMessageTest extends MessageSpec
{
    protected function createMessage()
    {
        return new EventBridgeSqsMessage();
    }
}
