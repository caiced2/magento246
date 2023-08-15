<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Model\Plugin;

use Magento\SalesRule\Model\Rule as SalesRule;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\ObjectManager;

class Rule
{
    /**
     * @var \Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Processor
     */
    protected $indexerProcessor;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param \Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Processor $indexerProcessor
     * @param Json $serializer Optional parameter for backward compatibility
     */
    public function __construct(
        \Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Processor $indexerProcessor,
        Json $serializer = null
    ) {
        $this->indexerProcessor = $indexerProcessor;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
    }

    /**
     * Around plugin needed for existing and new rules alike
     * @param SalesRule $subject
     * @param \Closure $proceed
     * @return SalesRule
     */
    public function aroundSave(SalesRule $subject, \Closure $proceed)
    {
        if (!$this->isRuleConditionChanged($subject)) {
            return $proceed();
        }

        $result = $proceed();

        $this->indexerProcessor->reindexRow($subject->getId());

        return $result;
    }

    /**
     * @param SalesRule $subject
     * @return bool
     */
    protected function isRuleConditionChanged(SalesRule $subject)
    {
        if ($subject->getData('skip_save_filter') == true) {
            return false;
        } elseif ($subject->isObjectNew() || $subject->getData('force_save_filter') == true) {
            return true;
        }
        $conditionsSerialized = $subject->getConditionsSerialized();
        if ($conditionsSerialized == null) {
            $conditionsSerialized = $this->serializer->serialize($subject->getConditions()->asArray());
        }
        $original = $subject->getOrigData('conditions_serialized');
        if ($conditionsSerialized != $original) {
            return true;
        }
        return false;
    }
}
