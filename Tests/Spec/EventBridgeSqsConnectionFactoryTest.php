<?php

namespace Cmrad\EbSQS\Tests\Spec;

use Cmrad\EbSQS\EventBridgeSqsConnectionFactory;
use Interop\Queue\ConnectionFactory;
use Interop\Queue\Spec\ConnectionFactorySpec;

class EventBridgeSqsConnectionFactoryTest extends ConnectionFactorySpec
{
    /**
     * @return ConnectionFactory
     */
    protected function createConnectionFactory()
    {
        return new EventBridgeSqsConnectionFactory('ebsqs:');
    }
}
