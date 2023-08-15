<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesRuleStaging\Model\Rule;

use Magento\Staging\Model\Entity\RetrieverInterface;
use Magento\SalesRule\Model\RuleFactory;
use Magento\SalesRule\Model\Rule;

class Retriever implements RetrieverInterface
{
    /**
     * @var RuleFactory
     */
    protected $ruleFactory;

    /**
     * @param RuleFactory $ruleFactory
     */
    public function __construct(
        RuleFactory $ruleFactory
    ) {
        $this->ruleFactory = $ruleFactory;
    }

    /**
     * @inheritDoc
     */
    public function getEntity($entityId)
    {
        /** @var Rule $entity */
        $entity = $this->ruleFactory->create();
        $entity->load($entityId);
        return $entity;
    }
}
