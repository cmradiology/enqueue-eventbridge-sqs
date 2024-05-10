<?php
namespace Cmrad\EbSQS\Tests;

use Cmrad\EbSQS\EventBridgeSqsConnectionFactory;
use Cmrad\EbSQS\EventBridgeSqsContext;
use PHPUnit\Framework\SkippedTestError;

trait EventBridgeSqsExtension
{
    private function buildEventBridgeSqsContext(): EventBridgeSqsContext
    {
        if (!$dsn = getenv('EBSQS_DSN')) {
            throw new SkippedTestError('Functional tests are not allowed in this environment');
        }

        return (new EventBridgeSqsConnectionFactory($dsn))->createContext();
    }
}
