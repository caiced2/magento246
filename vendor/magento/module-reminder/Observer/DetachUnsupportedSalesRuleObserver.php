<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reminder\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

/**
 * Reminder rules observer model
 */
class DetachUnsupportedSalesRuleObserver implements ObserverInterface
{
    /**
     * Remainder Rule Factory
     *
     * @var \Magento\Reminder\Model\RuleFactory
     */
    protected $_ruleFactory;

    /**
     * Constructor
     *
     * @param \Magento\Reminder\Model\RuleFactory $ruleFactory
     */
    public function __construct(
        \Magento\Reminder\Model\RuleFactory $ruleFactory
    ) {
        $this->_ruleFactory = $ruleFactory;
    }

    /**
     * Checks whether Sales Rule can be used in Email Remainder Rules and if it cant -
     * detaches it from Email Remainder Rules
     *
     * @param EventObserver $observer
     *
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        $rule = $observer->getRule();
        $couponType = $rule->getCouponType();
        $autoGeneration = $rule->getUseAutoGeneration();

        if ($couponType == \Magento\SalesRule\Model\Rule::COUPON_TYPE_SPECIFIC && !empty($autoGeneration)) {
            $model = $this->_ruleFactory->create();
            $ruleId = $rule->getId();
            $model->detachSalesRule($ruleId);
        }
    }
}
