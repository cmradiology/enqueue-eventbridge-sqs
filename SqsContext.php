<?php

namespace Cmrad\EbSQS;

use Enqueue\Sqs\SqsDestination;

class SqsContext extends \Enqueue\Sqs\SqsContext
{
    public function allowPublishMessageFromArn(SqsDestination $destination, string $arn): void
    {
        $this->getAwsSqsClient()->setQueueAttributes(
            [
                'QueueUrl' => $this->getQueueUrl($destination),
                'Attributes' => [
                    'Policy' => json_encode([
                        'Version' => '2012-10-17',
                        'Statement' => [
                            [
                                'Effect' => 'Allow',
                                'Principal' => '*',
                                'Action' => 'SQS:SendMessage',
                                'Resource' => $arn,
                            ],
                        ],
                    ]),
                ],
            ]
        );
    }
}
