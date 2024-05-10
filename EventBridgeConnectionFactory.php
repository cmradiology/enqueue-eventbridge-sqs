<?php

namespace Cmrad\EbSQS;

use Aws\Sdk;
use Enqueue\Dsn\Dsn;
use Enqueue\Sns\SnsClient;
use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;

class EventBridgeConnectionFactory implements ConnectionFactory
{
    private ?EventBridgeClient $client = null;
    /**
     * @var array|false[]
     */
    private array $config;

    public function __construct($config = 'eb:')
    {
        if ($config instanceof EventBridgeClient) {
            $this->client = $config;
            $this->config = ['lazy' => false] + $this->defaultConfig();

            return;
        }

        if (empty($config)) {
            $config = [];
        } elseif (\is_string($config)) {
            $config = $this->parseDsn($config);
        } elseif (\is_array($config)) {
            if (\array_key_exists('dsn', $config)) {
                $config = \array_replace_recursive($config, $this->parseDsn($config['dsn']));

                unset($config['dsn']);
            }
        } else {
            throw new \LogicException(\sprintf('The config must be either an array of options, a DSN string, null or instance of %s', EventBridgeClient::class));
        }

        $this->config = \array_replace($this->defaultConfig(), $config);
    }

    public function createContext(): Context
    {
        return new EventBridgeContext(
            $this->establishConnection(),
            $this->config
        );
    }

    private function establishConnection(): EventBridgeClient
    {
        if ($this->client) {
            return $this->client;
        }

        $config = [
            'version' => $this->config['version'],
            'region' => $this->config['region'],
        ];

        if (isset($this->config['endpoint'])) {
            $config['endpoint'] = $this->config['endpoint'];
        }

        if (isset($this->config['profile'])) {
            $config['profile'] = $this->config['profile'];
        }

        if ($this->config['key'] && $this->config['secret']) {
            $config['credentials'] = [
                'key' => $this->config['key'],
                'secret' => $this->config['secret'],
            ];

            if ($this->config['token']) {
                $config['credentials']['token'] = $this->config['token'];
            }
        }

        if (isset($this->config['http'])) {
            $config['http'] = $this->config['http'];
        }

        $establishConnection = function () use ($config) {
            return (new Sdk(['EventBridge' => $config]))->createMultiRegionEventBridge();
        };

        $this->client = $this->config['lazy'] ?
            new EventBridgeClient($establishConnection) :
            new EventBridgeClient($establishConnection())
        ;

        return $this->client;
    }

    private function parseDsn(string $dsn): array
    {
        $dsn = Dsn::parseFirst($dsn);

        if ('eb' !== $dsn->getSchemeProtocol()) {
            throw new \LogicException(\sprintf('The given scheme protocol "%s" is not supported. It must be "eb"', $dsn->getSchemeProtocol()));
        }

        return \array_filter(\array_replace($dsn->getQuery(), [
            'key' => $dsn->getString('key'),
            'secret' => $dsn->getString('secret'),
            'token' => $dsn->getString('token'),
            'region' => $dsn->getString('region'),
            'version' => $dsn->getString('version'),
            'lazy' => $dsn->getBool('lazy'),
            'endpoint' => $dsn->getString('endpoint'),
            'topic_arns' => $dsn->getArray('topic_arns', [])->toArray(),
            'http' => $dsn->getArray('http', [])->toArray(),
            'source' => $dsn->getString('source'),
        ]), function ($value) { return null !== $value; });
    }

    private function defaultConfig(): array
    {
        return [
            'key' => null,
            'secret' => null,
            'token' => null,
            'region' => null,
            'version' => '2015-10-07',
            'lazy' => true,
            'endpoint' => null,
            'topic_arns' => [],
            'http' => [],
            'source' => '',
        ];
    }
}
