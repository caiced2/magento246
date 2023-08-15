<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Reminder grid and edit controller
 */
abstract class Reminder extends \Magento\Backend\App\Action
{
    /**
     * Core registry
     *
     * @var Registry|null
     */
    protected $_coreRegistry = null;

    /**
     * Remainder Rule Factory
     *
     * @var \Magento\Reminder\Model\RuleFactory
     */
    protected $_ruleFactory;

    /**
     * Rule Condition Factory
     *
     * @var \Magento\Reminder\Model\Rule\ConditionFactory
     */
    protected $_conditionFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Filter\DateTime
     */
    protected $_dateFilter;

    /**
     * @var TimezoneInterface
     */
    protected $timeZoneResolver;

    /**
     * @param Action\Context $context
     * @param Registry $coreRegistry
     * @param \Magento\Reminder\Model\RuleFactory $ruleFactory
     * @param \Magento\Reminder\Model\Rule\ConditionFactory $conditionFactory
     * @param \Magento\Framework\Stdlib\DateTime\Filter\DateTime $dateFilter
     * @param TimezoneInterface $timeZoneResolver
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Registry $coreRegistry,
        \Magento\Reminder\Model\RuleFactory $ruleFactory,
        \Magento\Reminder\Model\Rule\ConditionFactory $conditionFactory,
        \Magento\Framework\Stdlib\DateTime\Filter\DateTime $dateFilter,
        TimezoneInterface $timeZoneResolver
    ) {
        parent::__construct($context);
        $this->_coreRegistry = $coreRegistry;
        $this->_ruleFactory = $ruleFactory;
        $this->_conditionFactory = $conditionFactory;
        $this->_dateFilter = $dateFilter;
        $this->timeZoneResolver = $timeZoneResolver;
    }

    /**
     * Initialize proper rule model
     *
     * @param string $requestParam
     * @return \Magento\Reminder\Model\Rule
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _initRule($requestParam = 'id')
    {
        $ruleId = $this->getRequest()->getParam($requestParam, 0);
        /* @var $rule \Magento\Reminder\Model\Rule */
        $rule = $this->_ruleFactory->create();
        if ($ruleId) {
            $rule->load($ruleId);
            if (!$rule->getId()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Please correct the reminder rule you requested.')
                );
            }
            if ($rule->getData('from_date')) {
                $rule->setData(
                    'from_date',
                    $this->timeZoneResolver->formatDateTime($rule->getData('from_date'))
                );
            }

            if ($rule->getData('to_date')) {
                $rule->setData(
                    'to_date',
                    $this->timeZoneResolver->formatDateTime($rule->getData('to_date'))
                );
            }
        }
        $this->_coreRegistry->register('current_reminder_rule', $rule);
        return $rule;
    }

    /**
     * Check the permission to run it
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(
            'Magento_Reminder::magento_reminder'
        ) && $this->_objectManager->get(
            \Magento\Reminder\Helper\Data::class
        )->isEnabled();
    }
}
