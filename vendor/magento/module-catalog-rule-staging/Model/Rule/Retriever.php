<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogRuleStaging\Model\Rule;

use Magento\Staging\Model\Entity\RetrieverInterface;
use Magento\CatalogRule\Model\CatalogRuleRepository;

class Retriever implements RetrieverInterface
{
    /**
     * @var CatalogRuleRepository
     */
    protected $ruleRepository;

    /**
     * @param CatalogRuleRepository $ruleRepository
     */
    public function __construct(
        CatalogRuleRepository $ruleRepository
    ) {
        $this->ruleRepository = $ruleRepository;
    }

    /**
     * @inheritDoc
     */
    public function getEntity($entityId)
    {
        return $this->ruleRepository->get($entityId);
    }
}
