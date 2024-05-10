<?php

declare(strict_types=1);

namespace Cmrad\EbSQS;

use Enqueue\Dsn\Dsn;
use Enqueue\Sns\SnsConnectionFactory;
use Enqueue\Sqs\SqsConnectionFactory;
use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;

class EventBridgeSqsConnectionFactory implements ConnectionFactory
{
    private string|array|null $eventBridgeConfig = null;

    private string|array|null $sqsConfig = null;

    /**
     * $config = [
     *   'key' => null                AWS credentials. If no credentials are provided, the SDK will attempt to load them from the environment.
     *   'secret' => null,            AWS credentials. If no credentials are provided, the SDK will attempt to load them from the environment.
     *   'token' => null,             AWS credentials. If no credentials are provided, the SDK will attempt to load them from the environment.
     *   'region' => null,            (string, required) Region to connect to. See http://docs.aws.amazon.com/general/latest/gr/rande.html for a list of available regions.
     *   'version' => '2012-11-05',   (string, required) The version of the webservice to utilize
     *   'lazy' => true,              Enable lazy connection (boolean)
     *   'endpoint' => null           (string, default=null) The full URI of the webservice. This is only required when connecting to a custom endpoint e.g. localstack
     * ].
     *
     * or
     *
     * $config = [
     *   'sns_key' => null,           SNS option
     *   'sqs_secret' => null,        SQS option
     *   'token'                      Option for both SNS and SQS
     * ].
     *
     * or
     *
     * ebsqs:
     * ebsqs:?key=aKey&secret=aSecret&eb_token=aEventBridgeToken&sqs_token=aSqsToken
     *
     * @param array|string|null $config
     */
    public function __construct(array|string|null $config = 'ebsqs:')
    {
        if (empty($config)) {
            $this->eventBridgeConfig = [];
            $this->sqsConfig = [];
        } elseif (is_string($config)) {
            $this->parseDsn($config);
        } elseif (is_array($config)) {
            if (array_key_exists('dsn', $config)) {
                $this->parseDsn($config['dsn']);
            } else {
                $this->parseOptions($config);
            }
        } else {
            throw new \LogicException('The config must be either an array of options, a DSN string or null');
        }
    }

    /**
     * @return EventBridgeSqsContext
     */
    public function createContext(): Context
    {
        return new EventBridgeSqsContext(function () {
            return (new EventBridgeConnectionFactory($this->eventBridgeConfig))->createContext();
        }, function () {
            return (new SqsConnectionFactory($this->sqsConfig))->createContext();
        });
    }

    private function parseDsn(string $dsn): void
    {
        $dsn = Dsn::parseFirst($dsn);

        if ('ebsqs' !== $dsn->getSchemeProtocol()) {
            throw new \LogicException(sprintf(
                'The given scheme protocol "%s" is not supported. It must be "ebsqs"',
                $dsn->getSchemeProtocol()
            ));
        }

        $this->parseOptions($dsn->getQuery());
    }

    private function parseOptions(array $options): void
    {
        // set default options
        foreach ($options as $key => $value) {
            if (!str_starts_with($key, 'sqs_') && !str_starts_with($key, 'eb_') && !str_starts_with($key, '_')) {
                $this->eventBridgeConfig[$key] = $value;
                $this->sqsConfig[$key] = $value;
            }
        }
        // set transport specific options
        foreach ($options as $key => $value) {
            if (str_starts_with($key, 'eb_')) {
                $this->eventBridgeConfig[substr($key, 3)] = $value;
            } elseif (str_starts_with($key, 'sqs_')) {
                $this->sqsConfig[substr($key, 4)] = $value;
            }
        }
    }
}
