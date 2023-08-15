<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Observer;

use Magento\Framework\Event\ObserverInterface;

class EnterpiseCustomerAttributeEditPrepareFormObserver implements ObserverInterface
{
    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    protected $_configSourceYesno;

    /**
     * @param \Magento\Config\Model\Config\Source\Yesno $configSourceYesno
     */
    public function __construct(
        \Magento\Config\Model\Config\Source\Yesno $configSourceYesno
    ) {
        $this->_configSourceYesno = $configSourceYesno;
    }

    /**
     * Add field "Use in Customer Segment" for Customer and Customer Address attribute edit form
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $form = $observer->getEvent()->getForm();
        $fieldset = $form->getElement('base_fieldset');
        $fieldset->addField(
            'is_used_for_customer_segment',
            'select',
            [
                'name' => 'is_used_for_customer_segment',
                'label' => __('Use in Customer Segment'),
                'title' => __('Use in Customer Segment'),
                'values' => $this->_configSourceYesno->toOptionArray()
            ]
        );
    }
}
