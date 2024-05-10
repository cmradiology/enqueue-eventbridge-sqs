<?php

namespace Cmrad\EbSQS;

class EventBridgeRuleTarget
{
    public function __construct(
        private string $id,
        private EventBridgeRule $rule,
        private string $arn
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getRule(): EventBridgeRule
    {
        return $this->rule;
    }

    public function getArn(): string
    {
        return $this->arn;
    }
}
