<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

/**
 * RMA observer
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class AddRmaOptionObserver implements ObserverInterface
{
    /**
     * Rma data
     *
     * @var \Magento\Rma\Helper\Data
     */
    protected $_rmaData = null;

    /**
     * @param \Magento\Rma\Helper\Data $rmaData
     */
    public function __construct(\Magento\Rma\Helper\Data $rmaData)
    {
        $this->_rmaData = $rmaData;
    }

    /**
     * Add rma availability option to options column in customer's order grid
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        $renderer = $observer->getEvent()->getRenderer();
        /** @var $row \Magento\Sales\Model\Order */
        $row = $observer->getEvent()->getRow();

        if ($this->_rmaData->canCreateRma($row, true)) {
            $reorderAction = [
                '@' => ['href' => $renderer->getUrl('adminhtml/rma/new', ['order_id' => $row->getId()])],
                '#' => __('Return'),
            ];
            $renderer->addToActions($reorderAction);
        }
    }
}
