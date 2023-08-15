<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PricePermissions\Ui\Component\Listing\Columns\Price;

use Magento\PricePermissions\Observer\ObserverData;
use Magento\Catalog\Ui\Component\Listing\Columns\Price;

class Plugin
{
    /**
     * @var ObserverData
     */
    protected $observerData;

    /**
     * @param ObserverData $observerData
     */
    public function __construct(ObserverData $observerData)
    {
        $this->observerData = $observerData;
    }

    /**
     * @param Price $subject
     * @param \Closure $proceed
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundPrepare(Price $subject, $proceed)
    {
        if (!$this->observerData->isCanReadProductPrice()) {
            return;
        }
        $proceed();
    }
}
