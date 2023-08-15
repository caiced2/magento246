<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftRegistry\Observer;

use Magento\Framework\Event\ObserverInterface;

class AddressFormatAdmin implements ObserverInterface
{
    /**
     * @var AddressFormat
     */
    protected $addressFormat;

    /**
     * Design package instance
     *
     * @var \Magento\Framework\View\DesignInterface
     */
    protected $_design;

    /**
     * @param AddressFormat $addressFormat
     * @param \Magento\Framework\View\DesignInterface $design
     */
    public function __construct(AddressFormat $addressFormat, \Magento\Framework\View\DesignInterface $design)
    {
        $this->addressFormat = $addressFormat;
        $this->_design = $design;
    }

    /**
     * Hide customer address in admin panel if it is gift registry shipping address
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->_design->getArea() == \Magento\Framework\App\Area::AREA_FRONTEND) {
            $this->addressFormat->format($observer);
        }
        return $this;
    }
}
